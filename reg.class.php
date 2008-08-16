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

