<?php
/**
* This file holds our reg class, which contains functions for our 
*	registration system.  Most of the functions here are called from
*	the hooks in the registration module.
* The reason for having this class is so that I can have private variables,
*	private functions, and even do a class hierarchy if necessary, and not
*	worry about polluting global variables or function names that might
*	cause a clash with Drupal.
* Note that all of the functions this class should be used statically.
* Do NOT attempt to instrantiate this class.
*/

class reg {

	/**
	* The current year and the lowest possible badge number.
	* At some point, I should store these values externally.
	*/
	const YEAR = "2009";
	const START_BADGE_NUM = "250";

	/**
	* Define constants for our permission names.
	*/
	const PERM_ADMIN = "admin reg system";
	const PERM_REGISTER = "register for a membership";

	/**
	* Define constants for form values
	*/
	const FORM_ADMIN_FAKE_CC = "reg_fake_cc";
	const FORM_ADMIN_CONDUCT_PATH = "reg_conduct_path";

	/**
	* Define other constants
	*/
	const FORM_TEXT_SIZE = 40;
	const FORM_TEXT_SIZE_SMALL = 20;

	/**
	* Temporarily stores our badge number between form validation 
	*	and submission.
	*/
	static private $badge_num;

	/**
	* Also store our data for going between validation and 
	*	submission functions.
	*/ 
	static private $data;

	/**
	* Our constructor.  This should never be called.
	*/
	function __construct() {
		$error = "You tried to instantiate this class even after I told "
			. "you not to!";
		throw new Exception($error);
	}


	/**
	* Return our permissions.
	*	
	* @return array Scalar array of permissions for this module.
	*/
	static function perm() {

		$retval = array();
		$retval[] = self::PERM_ADMIN;
		$retval[] = self::PERM_REGISTER;
		return($retval);

	}


	/**
	* Get the next badge number for the current year.
	*
	* @return integer The badge number.  This number can be assigned
	*	to a specific user.
	*/
	static function get_badge_num() {

		$year = self::YEAR;
		$query = "UPDATE reg_badge_num "
			. "SET badge_num = @val := badge_num+1 "
			. "WHERE year=%s ";
		db_query($query, array($year));
		$cursor = db_query("SELECT @val AS badge_num");
		$results = db_fetch_array($cursor);

		return($results["badge_num"]);

	} // End of get_badge_num()


	/**
	* Generate our menu items and callbacks for this module.
	*
	* @return array Scalar array of menu data.
	*/
	static function menu() {

		$retval = array();

		//
		// Public link
		//
		$retval[] = array(
			"path" => "reg",
			"title" => t("Registration"),
			"callback" => "reg_registration",
			"access" => user_access(self::PERM_REGISTER),
			"type" => MENU_NORMAL_ITEM,
			);

		//
		// Admin section
		//
		$retval[] = array(
			"path" => "admin/reg",
			"title" => t("Registration Admin"),
			"callback" => "reg_admin_stats",
			"access" => user_access(self::PERM_ADMIN),
			"type" => MENU_NORMAL_ITEM,
			);

		$retval[] = array(
			"path" => "admin/reg/stats",
			"title" => t("Stats"),
			"callback" => "reg_admin_stats",
			"type" => MENU_DEFAULT_LOCAL_TASK,
			"weight" => -10,
			);

		$retval[] = array(
			"path" => "admin/reg/settings",
			"title" => t("Settings"),
			"callback" => "reg_admin_settings",
			"type" => MENU_LOCAL_TASK,
			"weight" => 4,
			);

		$retval[] = array(
			"path" => "admin/reg/levels",
			"title" => t("Membership Levels"),
			"callback" => "reg_admin_levels",
			"type" => MENU_LOCAL_TASK,
			"weight" => 3,
			);

		$retval[] = array(
			"path" => "admin/reg/levels/list",
			"title" => t("List"),
			"callback" => "reg_admin_levels",
			"type" => MENU_DEFAULT_LOCAL_TASK,
			"weight" => -10,
			);

		$retval[] = array(
			"path" => "admin/reg/levels/add",
			"title" => t("Add"),
			"callback" => "reg_admin_levels_edit",
			"type" => MENU_LOCAL_TASK,
			);

		//
		// Used for editing a membership level.
		//
		$retval[] = array(
			"path" => "admin/reg/levels/edit",
			"title" => t("Add"),
			"callback" => "reg_admin_levels_edit",
			"callback_arguments" => array(arg(4)),
			"type" => MENU_CALLBACK,
			);

		//
		// Used for interacting with registrations
		//
		$retval[] = array(
			"path" => "admin/reg/registrations",
			"title" => t("Registrations"),
			"callback" => "reg_admin_registrations",
			"type" => MENU_LOCAL_TASK,
			"weight" => 1,
			);

		$retval[] = array(
			"path" => "admin/reg/registrations/recent",
			"title" => t("Recent"),
			"type" => MENU_DEFAULT_LOCAL_TASK,
			"weight" => -10,
			);

		$retval[] = array(
			"path" => "admin/reg/registrations/search",
			"title" => t("Search"),
			"callback" => "reg_admin_registrations_search",
			"type" => MENU_LOCAL_TASK,
			"weight" => 1,
			);

		$retval[] = array(
			"path" => "admin/reg/registrations/add",
			"title" => t("Add"),
			"callback" => "reg_admin_registrations_add",
			"type" => MENU_LOCAL_TASK,
			"weight" => 2,
			);

		//
		// Viewing registration-related logs.
		//
		$retval[] = array(
			"path" => "admin/reg/logs",
			"title" => t("Logs"),
			"callback" => "reg_admin_logs",
			"type" => MENU_LOCAL_TASK,
			"weight" => 2,
			);

		return($retval);

	} // End of menu()


	/**
	* This function creates the data structure for our main registration form.
	*
	* @return array Associative array of registration form.
	*/
	static function registration_form() {

		$retval = array();

		$retval["member"] = self::_registration_form();
		$retval["cc"] = self::_registration_form_cc();

		return($retval);

	} // End of registration_form()


	/**
	* This function is called to validate the form data.
	* If there are any issues, form_set_error() should be called so
	* that form processing does not continue.
	*/
	static function registration_form_validate(&$form_id, &$data) {

		if ($data["email"] != $data["email2"]) {
			$error = "Email addresses do not match!";
			form_set_error("email2", $error);
		}

		//
		// Sanity checking on our donation amount.
		//
		$donation_float = floatval($data["donation"]);
		if ($data["donation"] != (string)$donation_float) {
			$error = "Donation '" . $data["donation"] . "' is not a number!";
			form_set_error("donation", $error);

		} else if ($data["donation"] < 0) {
			form_set_error("donation", "Donation cannot be a negative amount!");

		}
        
		//
		// Sanity checking on the credit card expiration.
		//
		$month = date("n");
		$year = date("Y");

		if ($data["cc_exp"]["year"] == $year) {
			if ($data["cc_exp"]["month"] <= $month) {
				form_set_error("cc_exp][month", "Credit card is expired");
			}
		}

//
// TODO:
// We eventually need to ask for a registration level on the reg form.
//
$data["reg_level_id"] = 3;

		//
		// Make the transaction.  If it is successful, then add a new member.
		//
		$reg_trans_id = self::charge_cc($data);

		if ($reg_trans_id) {
			$badge_num = self::add_member($data, $reg_trans_id);
			//
			// Store our badge number, since we'll be referencing it again in
			// the submit funcition.
			//
			self::$badge_num = $badge_num;

			//
			// Heck, store our data too
			//
			self::$data = $data;
		}

	} // End of registration_form_validate()


	/**
	* All the registration form data checks out.  
	*/
	static function registration_form_submit(&$form_id, &$data) {

		$message = t("Congratulations!  Your registration was successful, and your badge number is %badge_num%.  ",
			array("%badge_num%" => self::$badge_num)
			);
		drupal_set_message($message);

		$message = t("Your credit card (%cc_name%) was successfully charged for %total_cost%.",
		array("%cc_name%" => self::$data["cc_name"],
			"%total_cost%" => "$" . self::$data["total_cost"],
			));
		drupal_set_message($message);

		$message = t("You will receive a conformation email sent to %email% shortly.",
			array("%email%" => self::$data["email"])
			);
		drupal_set_message($message);

		//
		// Send the user back to the front page.
		//
		return("");

	} // End of registration_form_submit()


	/**
	* This function actually charges our credit card.
	*
	* @return boolean True if the card is charged successfully.  
	*	False otherwise.
	*/
	static function charge_cc(&$data) {

		//
		// Calculate our costs.
		//
		$data["badge_cost"] = self::get_reg_cost($data["reg_level_id"]);
		$data["total_cost"] = $data["badge_cost"] + $data["donation"];

		if (!self::is_test_mode()) {
			//
			// TODO: Code to actually charge the card goes here.
			// On failure, call form_set_error(), log it, and return false.
			//
			// I need to make sure that non-numerics are filtered out here.
			$error = "CC Charging not implemented yet.";
			form_set_error("cc_num", $error);
			self::log($error, "", WATCHDOG_ERROR);
			return(false);

		} else {

			$message = "We are in testing mode.  Automatically allow this "
				. "'credit card'";
			self::log($message);

		}

		$reg_trans_id = self::log_trans($data);

		return($reg_trans_id);

	} // End of charge_cc()


	/**
	* This function logs a successful transaction.
	*
	* @TODO Support for different transaction types?
	*
	* @return integer the ID of the row that was inserted into the database.
	*/
	static function log_trans(&$data) {

		//
		// Save the successful charge in reg_trans.
		//
		$query = "INSERT INTO reg_trans ("
			. "created, reg_trans_type_id, reg_payment_type_id, "
			. "first, middle, last, birthdate, address1, address2, "
			. "city, state, zip, country, "
			. "reg_cc_type_id, cc_num, card_expire, "
			. "badge_cost, donation, total_cost "
			. ") VALUES ("
			. "NOW(), '%s', '%s', "
			. "'%s', '%s', '%s', '%s', '%s', '%s', "
			. "'%s', '%s', '%s', '%s', "
			. "'%s', '%s', '%s', "
			. "'%s', '%s', '%s' "
			. ")"
			;
		$birth = $data["birthday"];
		$birth_string = $birth["year"] . "-" . $birth["month"] 
			. "-" . $birth["day"];
		$exp = $data["cc_exp"];
		$exp_string = $exp["year"] . "-" . $exp["month"] ."-0";
		$data["cc_name"] = self::get_cc_name($data["cc_type"], $data["cc_num"]);
		$query_args = array(
			1, 1,
			$data["first"], $data["middle"], $data["last"], $birth_string,
				$data["address1"], $data["address2"],
			$data["city"], $data["state"], $data["zip"], $data["country"],
			$data["cc_type"], $data["cc_name"], $exp_string,
			$data["badge_cost"], $data["donation"], $data["total_cost"]
			);

		db_query($query, $query_args);

		$id = self::get_insert_id();

		return($id);

	} // End of log_trans()


	/**
	* Retrieve the most recent insert ID from a database insert.
	*
	* @return integer The most recent insert ID.
	*/
	static function get_insert_id() {

		$cursor = db_query("SELECT LAST_INSERT_ID() AS id");
		$row = db_fetch_array($cursor);
		$retval = $row["id"];
		return($retval);

	} // End of get_insert_id()


	/**
	* This function actually does the dirty work of adding a new member to
	* the system.  It is assumed that any credit card charging has been done.
	*
	* @param integer $reg_trans_id An option ID of the associated transaction
	*	stored in the reg_trans table.  This is so that the transaction can
	*	be updated with the ID from the reg table.
	*
	* @return integer The badge number of the member that we just added.
	*/
	static function add_member($data, $reg_trans_id = "") {

		$badge_num = self::get_badge_num();

		$query = "INSERT INTO {reg} "
			. "(created, modified, year, reg_type_id, reg_status_id, "
				. "badge_num, badge_name, first, middle, last, birthdate, "
				. "address1, address2, city, state, zip, country, email, "
				. "phone "
			. ") "
			. "VALUES "
			. "(NOW(), NOW(), '%s', '%s', '%s', '%s', '%s', '%s', '%s', "
				. "'%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', "
				. "'%s', '%s')"
			;
		$birth = $data["birthday"];
		$date_string = $birth["year"] . "-" . $birth["month"] 
			. "-" . $birth["day"];
		$reg_type_id = self::get_reg_type_id($data["reg_level_id"]);
		$query_args = array(self::YEAR, $reg_type_id, 1, $badge_num, 
			$data["badge_name"], $data["first"], $data["middle"], 
			$data["last"], $date_string, $data["address1"], 
			$data["address2"], $data["city"], $data["state"], $data["zip"],
			$data["country"], $data["email"], $data["phone"]
			);
		db_query($query, $query_args);

		$reg_id = self::get_insert_id();

		$message = "Added registration for badge number '$badge_num'";
		self::log($message, $reg_id);

		$query = "UPDATE reg_trans "
			. "SET "
			. "reg_id='%s' "
			. "WHERE "
			. "id='%s'";
		$query_args = array($reg_id, $reg_trans_id);
		db_query($query, $query_args);

		return($badge_num);

	} // End of add_member()


	/**
	* This is our registration log function.  It contains a wrapper for
	* the Drupal watchdog() facility, but also logs entries via our own logging
	* table.  This way, we can keep track of log entries in the registration 
	* system for months, or even years if necessary.
	*/
	static function log($message, $reg_id = "", $severity = WATCHDOG_NOTICE) {

		global $user, $base_root;

		watchdog("reg", $message, $severity);

		$url = $base_root . request_uri();
		$query = "INSERT INTO {reg_log} "
			. "(reg_id, uid, date, url, referrer, remote_addr, message) "
			. "VALUES "
			. "('%s', '%s', NOW(), '%s', '%s', '%s', '%s') "
			;
		$query_args = array($reg_id, $user->uid, $url, referer_uri(), 
			$_SERVER["REMOTE_ADDR"], $message
			);
		db_query($query, $query_args);

	} // End of log()


	/**
	* This function function creates the membership section of our 
	*	registration form.
	*/
	static private function _registration_form() {

		$retval = array(
			"#type" => "fieldset",
			"#title" => "Membership Information",
			//
			// Render elements with our custom theme code
			//
			"#theme" => "reg_theme"
			);

		$retval["badge_name"] = array(
			"#title" => "Badge Name",
			"#type" => "textfield",
			"#description" => "The name printed on your conbadge.  "
				. "This may be your real name or a nickname.<br>"
				. "It may be blank. ",
			"#size" => reg::FORM_TEXT_SIZE_SMALL,
			);
		$retval["first"] = array(
			"#type" => "textfield",
			"#title" => "First Name",
			"#description" => "Your real first name",
			"#size" => reg::FORM_TEXT_SIZE,
			"#required" => true,
			);
		$retval["middle"] = array(
			"#type" => "textfield",
			"#title" => "Middle Name",
			"#description" => "Your real middle name",
			"#size" => reg::FORM_TEXT_SIZE,
			);
		$retval["last"] = array(
			"#type" => "textfield",
			"#title" => "Last Name",
			"#description" => "Your real last name",
			"#size" => reg::FORM_TEXT_SIZE,
			"#required" => true,
			);
		$retval["birthday"] = array(
			"#type" => "date",
			"#title" => "Date of Birth",
			"#description" => "Your date of birth",
			"#size" => reg::FORM_TEXT_SIZE,
			"#required" => true,
			);
		$retval["address1"] = array(
			"#type" => "textfield",
			"#title" => "Address Line 1",
			"#description" => "Your mailing address",
			"#size" => reg::FORM_TEXT_SIZE,
			"#required" => true,
			);
		$retval["address2"] = array(
			"#type" => "textfield",
			"#title" => "Address Line 2",
			"#description" => "Additional address information, "
				. "such as P.O Box number",
			"#size" => reg::FORM_TEXT_SIZE,
			);
		$retval["city"] = array(
			"#type" => "textfield",
			"#title" => "City",
			"#description" => "Your city",
			"#size" => reg::FORM_TEXT_SIZE,
			"#required" => true,
			);
		$retval["state"] = array(
			"#type" => "textfield",
			"#title" => "State",
			"#description" => "Your state",
			"#size" => reg::FORM_TEXT_SIZE,
			"#required" => true,
			);
		$retval["zip"] = array(
			"#type" => "textfield",
			"#title" => "Zip Code",
			"#description" => "Your Zip/Postal code",
			"#size" => reg::FORM_TEXT_SIZE,
			"#required" => true,
			);
		$retval["country"] = array(
			"#type" => "textfield",
			"#title" => "Country",
			"#description" => "Your country",
			"#default_value" => "USA",
			"#size" => reg::FORM_TEXT_SIZE,
			"#required" => true,
			);
		$retval["email"] = array(
			"#type" => "textfield",
			"#title" => "Your email address",
			"#description" => "",
			"#size" => reg::FORM_TEXT_SIZE,
			"#required" => true,
			);
		$retval["email2"] = array(
			"#type" => "textfield",
			"#title" => "Confirm email address",
			"#description" => "Please re-type your email address to ensure there "
				. "were no typos.",
			"#size" => reg::FORM_TEXT_SIZE,
			"#required" => true,
			);
		$retval["phone"] = array(
			"#type" => "textfield",
			"#title" => "Your phone number",
			"#description" => "A phone number where we can reach you.",
			"#size" => reg::FORM_TEXT_SIZE,
			"#required" => true,
			);

		$path = variable_get(self::FORM_ADMIN_CONDUCT_PATH, "");
		if (!empty($path)) {
			$retval["conduct"] = array(
				"#type" => "checkbox",
				"#title" => "I agree with the<br>" 
					. l("Standards of Conduct", $path),
				"#description" => "You must agree with the " 
					. l("Standards of Conduct", $path) 
					. " in order to purchase a membership.",
				"#required" => true,
			);
		}

		return($retval);

	} // End of _registration_form()


	/**
	* Are we running in test mode?  If so, then we're not charging the
	*	credit card.
	*
	* @retval boolean True if we are running in test mode.  False otherwise.
	*/
	static function is_test_mode() {

		$retval = variable_get(reg::FORM_ADMIN_FAKE_CC, false);

		return($retval);

	} // End of is_test_mode()


	/**
	* This internal function creates the credit card portion of the 
	*	registration form.
	*/
	static private function _registration_form_cc() {

		$retval = array(
			"#type" => "fieldset",
			"#title" => "Payment Information",
			//
			// Render elements with our custom theme code
			//
			"#theme" => "reg_theme"
			);

		$retval["cc_type"] = array(
			"#title" => "Credit Card Type",
			"#type" => "select",
			"#options" => self::get_cc_types(),
			"#required" => true,
			);

		$retval["cc_num"] = array(
			"#title" => "Credit Card Number",
			"#description" => "Your Credit Card Number",
			"#type" => "textfield",
			"#size" => reg::FORM_TEXT_SIZE,
			"#required" => true,
			);

		if (self::is_test_mode()) {
			$retval["cc_num"]["#description"] = "Running in test mode.  "
				. "Just enter any old number.";
		}

		$retval["cc_exp"] = array(
			"#title" => "Credit Card Expiration",
			//
			// This is set so that when the child elements are processed,
			// they know they have a parent, and hence get stored
			// properly in the resulting array.
			//
			"#type" => "cc_exp",
			"#tree" => "true",
			);
		$retval["cc_exp"]["month"] = array(
			"#options" => self::get_cc_exp_months(),
			"#type" => "select",
			"#default_value" => date("n"),
			);

		$retval["cc_exp"]["year"] = array(
			"#options" => self::get_cc_exp_years(),
			"#type" => "select",
			"#default_value" => date("Y"),
			);


		$retval["donation"] = array(
			"#title" => "Donation (USD)",
			"#type" => "textfield",
			"#description" => "Would you like to make an additional donation?",
			"#default_value" => "0.00",
			"#size" => reg::FORM_TEXT_SIZE_SMALL,
			);

		$retval["submit"] = array(
			"#type" => "submit",
			"#value" => "Register"
			);

		return($retval);

	} // End of _registration_form_cc()


	/**
	* Our main registration page.
	*/
	static function registration() {

		$retval = "";
		$retval = drupal_get_form("reg_registration_form");

/**
* Fields:
// Javascript to change the price that's displayed for the card?
shirt_size (staff and super sponsors)
	- Could I do this in Javascript through a handler of some sort?

// TODO:
// Eventually add a confirmation page with the amount to be charged to the credit card?
*/
		return($retval);

	} // End of registration()


	/**
	* This function gets the human-readable name for a specific card.
	*
	* @param mixed $cc_type This can be a string such as "Visa", or a value 
	*	from the reg_cc_type table.
	*
	* @param string $cc_num The full credit card number.  This is assumed to 
	*	consist only of integers.
	*
	* @return string A human-readable string, such as "Visa ending in '1234'"
	*
	*/
	static function get_cc_name($cc_type, $cc_num) {

		//
		// Get the string type for a card if we don't already have it.
		//
		$cc_type_int = intval($cc_type);
		if ($cc_type == (string)$cc_type_int) {
			$types = self::get_cc_types();
			$cc_type = $types[$cc_type];
		}

		$retval = "${cc_type} ending in '" . substr($cc_num, -4) . "'";

		return($retval);

	} // End of get_cc_name()


	/**
	* Determine the cost of a registration based on the reg_level_id.
	*
	* @return integer The cost of the registration.
	*/
	function get_reg_cost($level_id) {

		$query = "SELECT price FROM reg_level "
			. "WHERE id='%s'";
		$query_args = array($level_id);
		$cursor = db_query($query, $query_args);
		$row = db_fetch_array($cursor);
		$retval = $row["price"];

		return($retval);

	} // End of get_reg_cost()


	/**
	* Get the registration type ID, based on the level.
	*/
	static function get_reg_type_id($level_id) {

		$query = "SELECT reg_type_id FROM reg_level "
			. "WHERE id='%s'";
		$query_args = array($level_id);
		$cursor = db_query($query, $query_args);
		$row = db_fetch_array($cursor);
		$retval = $row["reg_type_id"];

		return($retval);

	} // End of get_reg_type_id()


	/**
	* This function retrieves our different types of membership from
	* the database.
	*
	* @return array Array where the key is the unique ID and the value is
	*	the membership type.
	*/
	static function get_types() {

		//
		// Cache our rows between calls
		//
		static $retval = array();

		if (!empty($retval)) {
			return($retval);
		}

		$query = "SELECT * FROM reg_type ";
		$cursor = db_query($query);

		while ($row = db_fetch_array($cursor)) {
			$id = $row["id"];
			$name = $row["member_type"];
			$retval[$id] = $name;
		}

		return($retval);

	} // End of get_types()


	/**
	* Retrieve our different transaction types from the database.
	*
	* @return array Array where the key is the unique ID and the value is
	*	the transaction type.
	*/
	function get_trans_types() {

		//
		// Cache our rows between calls
		//
		static $retval = array();

		if (!empty($retval)) {
			return($retval);
		}

		$query = "SELECT * FROM reg_trans_type ";
		$cursor = db_query($query);

		while ($row = db_fetch_array($cursor)) {
			$id = $row["id"];
			$name = $row["trans_type"];
			$retval[$id] = $name;
		}

		return($retval);

	} // End of get_trans_types()


	/**
	* Retrieve our different payment types from the database.
	*
	* @return array Array where the key is the unique ID and the value is
	*	the payment type.
	*/
	function get_payment_types() {

		//
		// Cache our rows between calls
		//
		static $retval = array();

		if (!empty($retval)) {
			return($retval);
		}

		$query = "SELECT * FROM reg_payment_type ";
		$cursor = db_query($query);

		while ($row = db_fetch_array($cursor)) {
			$id = $row["id"];
			$name = $row["payment_type"];
			$retval[$id] = $name;
		}

		return($retval);

	} // End of get_payment_types()


	/**
	* This function retrieves our different statuses from the database.
	*
	* @return array Array where the key is the unique ID and the value is
	*	the status.
	*
	* @todo I need to do something about the "detail" field at some point...
	*/
	static function get_statuses() {

		//
		// Cache our rows between calls
		//
		static $retval = array();

		if (!empty($retval)) {
			return($retval);
		}

		$query = "SELECT * FROM reg_status ";
		$cursor = db_query($query);

		while ($row = db_fetch_array($cursor)) {
			$id = $row["id"];
			$name = $row["status"];
			$retval[$id] = $name;
		}

		return($retval);

	} // End of get_statuses()


	/**
	* This function retrieves our different credit card types from the
	*	database.
	*
	* @return array Array where the key is the unique ID and the value is
	*	the credit card type.
	*/
	static function get_cc_types() {

		//
		// Cache our values between calls
		//
		static $retval = array();

		if (!empty($retval)) {
			return($retval);
		}

		$query = "SELECT * FROM reg_cc_type";
		$cursor = db_query($query);

		while ($row = db_fetch_array($cursor)) {
			$id = $row["id"];
			$name = $row["cc_type"];
			$retval[$id] = $name;
		}

		return($retval);

	} // End of get_statuses()

	/**
	* Return an array of credit card expiration months.
	*/
	static function get_cc_exp_months() {
		//
		// We put a bogus first element in so that the rest of the elements
		// will match their keys.
		//
		$retval = array(
			"1" => "Jan",
			"2" => "Feb",
			"3" => "Mar",
			"4" => "Apr",
			"5" => "May",
			"6" => "Jun",
			"7" => "Jul",
			"8" => "Aug",
			"9" => "Sep",
			"10" => "Oct",
			"11" => "Nov",
			"12" => "Dec",
			);
		return($retval);
	}


	/**
	* Return an array of credit card expiration years.
	*/
	static function get_cc_exp_years() {
		$retval = array();

		$start = date("Y");
		$end = $start + 7;
		for ($i = $start; $i<$end; $i++) {
			$retval[$i] = $i;
		}

		return($retval);
	}


	/**
	* Process a form in our own registration theme. This will allow
	* us to print out certain form elements differently.
	*/ 
	static function theme(&$form) {

		$retval = "";

		//
		// Include our CSS
		//
		$path = drupal_get_path("module", "reg") . "/reg.css";
		drupal_add_css($path, "module", "all", false);

		$retval .= "<table >";

		$retval .= self::theme_children($form);

		$retval .= "</table>";

		return($retval);

	} // End of theme()


	/**
	* Render a text field
	*
	* @param array $item Associative array of the item to render
	*
	* @return string HTML code for the form element.
	*/
	function theme_textfield(&$item) {
		$class = array('form-text');
		_form_set_class($item, $class);
		$retval = '<input type="text" maxlength="' 
			. $item['#maxlength'] . '" name="' . $item['#name'] 
			. '" id="'. $item['#id'] . '" ' . $size .' value="' 
			. check_plain($item['#value']) . '"' . 
			drupal_attributes($item['#attributes']) . ' />'
			;

		return($retval);

	} // End of theme_textfield()


	/**
	* Render a select element
	*
	* @param array $item Associative array of the item to render
	*
	* @return string HTML code for the form element.
	*/
	function theme_select(&$item) {

		$class = array('form-select');
		_form_set_class($item, $class);

		$size = $item['#size'] ? ' size="' . $item['#size'] . '"' : '';
		$multiple = isset($item['#multiple']) && $item['#multiple'];

		$retval .= '<select name="'
			. $item['#name'] . ''. ($multiple ? '[]' : '') . '"' 
			. ($multiple ? ' multiple="multiple" ' : '') 
			. drupal_attributes($item['#attributes']) 
			. ' id="' . $item['#id'] . '" ' . $size . '>' 
			. form_select_options($item) . '</select>';
		return($retval);

	} // End of theme_select()


	/**
	* Render CC expiration form widgets
	*
	* @param array $item Associative array of form items
	*
	* @return string HTML code for the form elements
	*/
	function theme_cc_exp(&$item) {

		$retval = "";
		foreach (element_children($item) as $key => $value) {
			if (!empty($retval)) {
				$retval .= " ";
			}
			$retval .= self::theme_select($item[$value]);
		}

		return($retval);

	} // End of theme_select_list()


	/**
	* Render a checkbox
	*
	* @param array $item Associative array of the item to render
	*
	* @return string HTML code for the form element.
	*/
	function theme_checkbox(&$item) {

		$class = array('form-checkbox');
		_form_set_class($item, $class);

		$checked = "";
		if (!empty($item["#value"])) {
			$checked = "checked=\"checked\" ";
		}

		$retval = '<input '
			. 'type="checkbox" '
			. 'name="'. $item['#name'] .'" '
			. 'id="'. $item['#id'].'" ' 
			. 'value="'. $item['#return_value'] .'" '
			. $checked
			. drupal_attributes($item['#attributes']) 
			. ' />';

		return($retval);

	} // End of theme_checkbox()


	/**
	* Render a date field
	*
	* @param array $item Associative array of the item to render
	*
	* @return string HTML code for the form element.
	*/
	function theme_date(&$item) {

		$retval = self::theme_select($item["year"])
			. " "
			. self::theme_select($item["month"])
			. " "
			. self::theme_select($item["day"])
			. " "
			;

		return($retval);

	} // End of theme_date()


	/**
	* Process the childen of a particular form element.
	*
	* @param array $form Associatiave array of one or more form elements
	*	that hold children.  Any direct form elements in them will NOT 
	*	be processed.
	*
	* @return string HTML code for the form, along with table row and column
	*	code.
	*/
	function theme_children(&$form) {

		foreach (element_children($form) as $key => $value) {

			$item = $form[$value];
	
			$required = !empty($item['#required']) ? 
				'<span class="reg-form-required" title="' 
				. t('This field is required.') . '"> *</span>' : '';

			$retval .= "<tr><td align=\"right\" class=\"reg-name\">";
			if ($item["#type"] != "textfield"
				&& $item["#type"] != "select"
				&& $item["#type"] != "checkbox"
				&& $item["#type"] != "date"
				&& $item["#type"] != "cc_exp"
				) {
				$retval .= drupal_render($item);

			} else {
				$retval .= '<div class="reg-form-item">' . "\n";
				$retval .= "<label>" . $item["#title"] . ": " . "</label>";
				$retval .= "</div>";
		
				$retval .= "</td>\n";

				//
				// This form generation code was ripped from theme_textfield().
				// If you need more functionality for generating text fields, 
				// you'll have to rip it from there. :-P
				//
				$retval .= "<td class=\"reg-value\">";
				$retval .= '<div class="reg-form-item">' . "\n";

				if ($item["#type"] == "textfield") {
					$retval .= self::theme_textfield($item);

				} else if ($item["#type"] == "select") {
					$retval .= self::theme_select($item);

				} else if ($item["#type"] == "checkbox") {
					$retval .= self::theme_checkbox($item);

				} else if ($item["#type"] == "date") {
					$retval .= self::theme_date($item);

				} else if ($item["#type"] == "cc_exp") {
					$retval .= self::theme_cc_exp($item);

				} else {
					//
					// This only gets executed if I screwed up the outer
					// if statement. :-P
					//
					$retval .= "No code for item type: " . $item["#type"];

				}

				$retval .= $required;

				//
				// Ripped from theme_form_element
				//
				if (!empty($item['#description'])) {
					$retval .= ' <div class="description">' 
						. $item['#description'] . "</div>\n";
				} 

				$retval .= "</div>";
 
				//
				// Eventually call theme_table() via theme('table', $header, $rows)
				//

			} 

			$retval .= "</td></tr>\n";

		} // End of foreach()

		return($retval);

	} // End of theme_children()

} // End of reg class

