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
	* Check to see if a certain badge number is available for a certain
	*	member.
	*
	* @param integer $reg_id The ID of the registration
	*
	* @param integer $badge_num The badge number to check for.
	*
	* Note that this only works for the CURRENT year.  We should NOT
	*	be updating memberships for previous years EVER.
	*
	* @return boolean True if this badge number is available for the member.
	*	False if it is in use elsewhere.
	* 
	*/
	static function is_badge_num_available($reg_id, $badge_num) {

		//
		// Create a query to check and see if the badge nubmer is in use.
		//
		$year = self::YEAR;
		$query = "SELECT id "
			. "FROM reg "
			. "WHERE "
			. "year='%s%' "
			. "AND badge_num='%s' "
			. "AND id!='%s'"
			;
		$query_args = array($year, $badge_num, $reg_id);
		$cursor = db_query($query, $query_args);
		$row = db_fetch_array($cursor);

		if (!empty($row)) {
			return(false);
		}

		return(true);

	} // End of check_badge_num()


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

		global $user;

		//
		// Save the successful charge in reg_trans.
		//
		$query = "INSERT INTO reg_trans ("
			. "uid, "
			. "date, reg_trans_type_id, reg_payment_type_id, "
			. "first, middle, last, address1, address2, "
			. "city, state, zip, country, "
			. "reg_cc_type_id, cc_num, card_expire, "
			. "badge_cost, donation, total_cost "
			. ") VALUES ("
			. "'%s', "
			. "'%s', '%s', '%s', "
			. "'%s', '%s', '%s', '%s', '%s', "
			. "'%s', '%s', '%s', '%s', "
			. "'%s', '%s', '%s', "
			. "'%s', '%s', '%s' "
			. ")"
			;
		$exp = $data["cc_exp"];
		$exp_string = $exp["year"] . "-" . $exp["month"] ."-0";

		$data["cc_name"] = self::get_cc_name($data["cc_type"], $data["cc_num"]);
		$query_args = array(
			$user->uid, 
			time(), 1, 1,
			$data["first"], $data["middle"], $data["last"], 
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
				. "badge_num, "
				. "badge_name, first, middle, last, "
				. "birthdate, "
				. "address1, address2, city, state, zip, country, email, "
				. "phone, shirt_size_id "
			. ") "
			. "VALUES "
			. "(UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), '%s', '%s', '%s', "
				. "'%s', "
				. "'%s', '%s', '%s', '%s', "
				. "'%s', "
				. "'%s', '%s', '%s', '%s', '%s', '%s', '%s', "
				. "'%s', '%s')"
			;
		$birth = $data["birthday"];
		$date_string = $birth["year"] . "-" . $birth["month"] 
			. "-" . $birth["day"];
		if (empty($data["reg_type_id"])) {
			$data["reg_type_id"] = self::get_reg_type_id(
				$data["reg_level_id"]);
		}

		$query_args = array(self::YEAR, $data["reg_type_id"], 1, $badge_num, 
			$data["badge_name"], $data["first"], $data["middle"], 
			$data["last"], $date_string, $data["address1"], 
			$data["address2"], $data["city"], $data["state"], $data["zip"],
			$data["country"], $data["email"], $data["phone"],
			$data["shirt_size_id"]
			);
		db_query($query, $query_args);

		$reg_id = self::get_insert_id();

		$message = t("Added registration for badge number '%num%'",
			array("%num%" => $badge_num)
			);
		self::log($message, $reg_id);

		if (!empty($reg_trans_id)) {
			$query = "UPDATE reg_trans "
				. "SET "
				. "reg_id='%s' "
				. "WHERE "
				. "id='%s'";
			$query_args = array($reg_id, $reg_trans_id);
			db_query($query, $query_args);

		}

		return($badge_num);

	} // End of add_member()


	/**
	* This function updates an existing membership, and is only used by 
	* an admin.
	*
	* 
	* @return integer The badge number of the member that we just updated.
	*/
	static function update_member($data) {

		//
		// Assign a badge number if one was not entered.
		//
		if (empty($data["badge_num"])) {
			$data["badge_num"] = self::get_badge_num();
		}

		$query = "UPDATE {reg} "
			. "SET "
			. "modified=UNIX_TIMESTAMP(), reg_type_id='%s', reg_status_id='%s', "
			. "badge_num='%s', badge_name='%s', "
			. "first='%s', middle='%s', last='%s', birthdate='%s', "
			. "address1='%s', address2='%s', city='%s', state='%s', "
			. "zip='%s', country='%s', email='%s', phone='%s', "
			. "shirt_size_id='%s' "
			."WHERE id=%d ";

		$birth = $data["birthday"];
		$date_string = $birth["year"] . "-" . $birth["month"] 
			. "-" . $birth["day"];

		$query_args = array(
			$data["reg_type_id"], $data["reg_status_id"],
			$data["badge_num"], $data["badge_name"],
			$data["first"], $data["middle"], $data["last"], $date_string,
			$data["address1"], $data["address2"], $data["city"], 
				$data["state"],
			$data["zip"], $data["country"], $data["email"], $data["phone"],
			$data["shirt_size_id"],
			$data["reg_id"]
			);
		db_query($query, $query_args);

		$message = t("Updated registration for badge number '%num%'",
				array("%num%" => $data["badge_num"])
				);
		self::log($message, $data["reg_id"]);

		return($data["badge_num"]);

	} // End of update_member()


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
			. "('%s', '%s', '%s', '%s', '%s', '%s', '%s') "
			;
		$query_args = array($reg_id, $user->uid, time(), $url, 
			referer_uri(), $_SERVER["REMOTE_ADDR"], $message
			);
		db_query($query, $query_args);

	} // End of log()


	/**
	* Are we running in test mode?  If so, then we're not charging the
	*	credit card.
	*
	* @retval boolean True if we are running in test mode.  False otherwise.
	*/
	static function is_test_mode() {

		$retval = variable_get(reg_form::FORM_ADMIN_FAKE_CC, false);

		return($retval);

	} // End of is_test_mode()


	/**
	* Our main registration page.
	*/
	static function registration() {

		$retval = "";
		$retval .= drupal_get_form("reg_registration_form");

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

		$query = "SELECT * FROM reg_type ORDER BY weight";
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

		$query = "SELECT * FROM reg_status ORDER BY weight";
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
	* This function gets our possible shirt sizes.
	*
	* @param boolean $disabled Do we want to include disabled shirt sizes?
	*
	* @return array Array where the key is the unique ID and the value is
	*	the shirt size.
	*/
	static function get_shirt_sizes($disabled = false) {

		//
		// Cache our values between calls
		//
		static $retval = array();
		
		if (!empty($retval)) {
			return($retval);
		}

		$query = "SELECT * FROM reg_shirt_sizes";
		if (empty($disabled)) {
			$query .= " WHERE disabled IS NULL";
		}
		$cursor = db_query($query);

		while ($row = db_fetch_array($cursor)) {
			$id = $row["id"];
			$size = $row["shirt_size"];
			$retval[$id] = $size;
		}

		return($retval);

	} // End of get_shirt_sizes()


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


} // End of reg class

