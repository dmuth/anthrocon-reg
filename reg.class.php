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
	* How many items displayed per pager in a pager?
	*/
	const ITEMS_PER_PAGE = 20;


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
	* This function checks to see if a proposed badge number is valid.
	*/
	function is_badge_num_valid($badge_num) {

		//
		// Handle an empty string and a zero.  They are both valid.
		//
		if (empty($badge_num)) {
			return(true);
		}

		$badge_num_int = intval($badge_num);

		if ($badge_num != (string)$badge_num_int
			) {
			$error = t("Badge number '%num%' is not a number!",
				array("%num%" => $badge_num)
				);
			form_set_error("badge_num", $error);
		}

		if ($badge_num_int < 0) {
			$error = t("Badge number cannot be negative!");
			form_set_error("badge_num", $error);
		}

	} // End of is_badge_num_valid()


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
		// If no badge number was entered, one will be assigned automatically.
		//
		if ($badge_num == "") {
			return(true);
		}

		//
		// Create a query to check and see if the badge number is in use.
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
			$error = t("Badge number '%num%' is already in use!",
				array("%num%" => $badge_num)
				);
			form_set_error("badge_num", $error);
			return(false);
		}

		//
		// Now, check to see if we have exceeded the highest assigned number.
		// We don't want this to happen, because said number will eventually
		// get stomped on sooner or later.
		//
		$year = self::YEAR;
		$query = "SELECT * "
			. "FROM {reg_badge_num} "
			. "WHERE "
			. "year=%s "
			;
		$cursor = db_query($query, array($year));
		$row = db_fetch_array($cursor);

		if ($badge_num > $row["badge_num"]) {
			$error = t("Badge number '%num%' exceeds highest assigned "
				. "number of '%assigned%'.  Please pick a lower number "
				. "or leave blank to automatically assign a number.",
				array(
					"%num%" => $badge_num,
					"%assigned%" => $row["badge_num"],
				)
				);
			form_set_error("bdage_num", $error);

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
		$data["badge_cost"] = reg_data::get_reg_cost($data["reg_level_id"]);
		$data["total_cost"] = $data["badge_cost"] + $data["donation"];

		if (!self::is_test_mode()) {
			//
			// TODO: Code to actually charge the card goes here.
			// On failure, call form_set_error(), log it, and return false.
			//
			// I need to make sure that non-numerics are filtered out here.
			$error = "CC Charging not implemented yet.";
			form_set_error("cc_num", $error);
			reg_log::log($error, "", WATCHDOG_ERROR);
			return(false);

		} else {

			$message = "We are in testing mode.  Automatically allow this "
				. "'credit card'";
			reg_log::log($message);

		}

		$reg_trans_id = reg_log::log_trans($data);

		return($reg_trans_id);

	} // End of charge_cc()


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

		$badge_num = reg_data::get_badge_num();

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
			$data["reg_type_id"] = reg_data::get_reg_type_id(
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

		$reg_id = reg_data::get_insert_id();

		$message = t("Added registration for badge number '%num%'",
			array("%num%" => $badge_num)
			);
		reg_log::log($message, $reg_id);

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
		if ($data["badge_num"] == "") {
			$data["badge_num"] = reg_data::get_badge_num();
			$message = t("New badge number generated");
			drupal_set_message($message);
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
		reg_log::log($message, $data["reg_id"]);

		return($data["badge_num"]);

	} // End of update_member()


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


} // End of reg class

