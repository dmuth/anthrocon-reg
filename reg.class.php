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
	* The maximum number we'll allow for donations from users.  
	*	This is to limit damages in the case that we get hit with a 
	*	fraudulent charge.
	*/
	const DONATION_MAX = 1000;

	/**
	* The name of the variable that holds the "contact" email address for
	* the reg system.  This may change in the future, once I create a setup
	* screen where the Registration Director can enter a custom email 
	* address. :-)
	*/
	const VAR_EMAIL = "site_mail";


	function __construct() {
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
	* Check to see if a specific number is valid.
	*
	* @param mixed $num The value to check
	*
	* @return boolean True if valid.  False if otherwise.
	*/
	static function is_valid_number($num) {

		$num_int = intval($num);

		if ($num != (string)$num_int) {
			return(false);
		}

		return(true);

	} // End of is_valid_number()


	/**
	* Check to see if this is a valid floating point number.
	*
	* @param mixed $num The value to check.
	*
	* @return boolean True if valid.  False if otherwise.
	*/
	static function is_valid_float($num) {

		$num_float = floatval($num);

		if ($num != (string)$num_float) {
			return(false);
		}

		return(true);

	} // End of is_valid_float()


	/**
	* Check to see if a number is negative.
	*
	* @param mixed $num The value to check.
	*
	* Note: It would be a good idea to call is_valid_number() before this
	*	function.
	*
	* @return boolean.  True if negative. False if otherwise (negative 
	*	OR not a number).
	*
	*/
	static function is_negative_number($num) {

		//
		// Definitely not a negative number.
		//
		if (!self::is_valid_number($num)) {
			return(false);
		}

		if ($num < 0) {
			return(true);
		}

		//
		// This is a postive number.
		//
		return(false);

	} // End of is_negative_number()


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

		if (!self::is_valid_number($badge_num)) {
			$error = t("Badge number '%num%' is not a number!",
				array("%num%" => $badge_num)
				);
			form_set_error("badge_num", $error);
		}

		//if ($badge_num_int < 0) {
		if (self::is_negative_number($badge_num)) {
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

	} // End of is_badge_num_available()


	/**
	* This function actually charges our credit card.
	*
	* @param boolean $log_only Only log the transaction, do NOT charge 
	* the card.  This is used for when an admin enters a registration 
	* manually.
	* 
	* @return boolean True if the card is charged successfully.  
	*	False otherwise.
	*/
	static function charge_cc(&$data, $log_only = false) {

		//
		// Only calculate our badge cost if it wasn't already specified by 
		// a manual entry.
		//
		if (empty($data["badge_cost"])) {
			$data["badge_cost"] = reg_data::get_reg_cost($data["reg_level_id"]);
		}

		//
		// This is redundant to the total_cost code in log_trans(), but is
		// necessary since we need to figure out how much we'll charge the 
		// credit card before we charge the credit card.  It's an annoying
		// chicken and egg problem, I know.
		//
		$data["total_cost"] = $data["badge_cost"] + $data["donation"];

		if (!$log_only) {

			//
			// We're always using authorize.net for now.
			//
			$data["reg_trans_gateway_id"] = 1;

			if (!self::is_test_mode()) {
				//
				// TODO: Code to actually charge the card goes here.
				// On failure, call form_set_error(), log it, and return false.
				//
				// I need to make sure that non-numerics are filtered out here.
				$error = t("CC Charging not implemented yet.");
				form_set_error("cc_num", $error);
				reg_log::log($error, "", WATCHDOG_ERROR);
				return(false);

			} else {
	
				$message = t("We are in testing mode.  Automatically allow this "
					. "'credit card'");
				$data["reg_log_id"] = reg_log::log($message);

				//
				// Generate random gateway data.
				//
				$data["gateway_auth_code"] = reg_fake::get_string(6);
				$data["gateway_transaction_id"] = reg_fake::get_number(
					0, (pow(10, 9)));

				//
				// Create a fairly random invoice number with our timestamp
				// and a random number applied to it.
				//
				// This doesn't have to be 100% unique, since the main purpose
				// of this is to keep authorize.net from thinking two separate
				// memberships purchased with the same card is a "duplicate".
				//
				$data["invoice_number"] = time() . "-" 
					. mt_rand(100000, 999999);

				$avs_codes = array("Y", "N", "D", "X", "Z");
				$data["gateway_avs"] = reg_fake::get_item($avs_codes);

				$cvv_codes = array("Y", "N", "X", "F");
				$data["gateway_cvv"] = reg_fake::get_item($cvv_codes);

			}
	
		} else {
			$message = t("Logging a manually made transaction/comp/etc. ")
				. t("No card charging took place just now.");
			$data["reg_log_id"] = reg_log::log($message);

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

		//
		// Get our current membership levels and make sure that we
		//
		$levels = reg_data::get_valid_levels();

		if (empty($levels)) {
			$data = array(
				"!email" => variable_get(reg::VAR_EMAIL, ""),
				);
			$retval = reg_message::load_display("no-levels-available", $data);

			$message = t("A user tried to visit the public registration page, "
				. "but there were no membership levels available.");
			reg_log::log($message, "", WATCHDOG_WARNING);

			return($retval);

		}

		//
		// Load our custom message, if we have done.
		//
		$retval = reg_message::load_display("header");

		$retval .= drupal_get_form("reg_registration_form");

		return($retval);

	} // End of registration()


	/**
	* Are we currently in SSL?
	*
	* @return boolean True if we are.  False otherwise.
	*/
	static function is_ssl() {
		if ($_SERVER["SERVER_PORT"] == 443
			|| $_SERVER["SERVER_PORT"] == 8443
			) {
			return(true);
		}

		return(false);

	} // End of is_ssl()


	/**
	* Force the current page to be reloaded securely.
	*/
	static function force_ssl() {

		if (!self::is_ssl()) {
			$uri = request_uri();
			$_SERVER["SERVER_PORT"]= 443;
			self::goto_url($uri);
		}

	} // End of force_ssl()


	/**
	* Wrapper for drupal_goto().
	*
	* Normally in Drupal 5 when a "submit" function is fired for a form,
	*	the optional return value can be a URI to redirect the user to.
	*	Unfortunately, the drupal_goto() function makes use of the $base_url
	*	variable, which does not include the current SSL setting.
	*	So an HTTPS conneciton will be redirected to an HTTP connection.
	*	That is bad.
	*
	* This function gets around that by creating a full URL based on the
	*	URI that is passed in, and preserving HTTPS URLs, *then* redirecting
	*	the user with drupal_goto().
	*
	* @param string $uri The URL we want to send the user to.
	*/
	static function goto_url($uri) {

		$url = url($uri, null, null, true);

		//
		// If we are currently in SSL mode, change the target URL 
		// to also be in SSL.
		//
		// It seems that the url() function preprends a slash to the URL,
		// and since request_uri(), also does that in force_ssl(), we can
		// get redirected to URLs like http://www.anthrocon.org//reg.
		// Believe it or not, that's perfectly okay.  What is NOT okay is
		// passing such a URL into this function later, as it completely
		// breaks drupal_goto().  So we get rid of any instances of "//"
		// here.
		//
		if (self::is_ssl()) {
			$url = eregi_replace("//", "/", $url);
			//
			// Oh yeah, switch to secure mode too. :-)
			//
			$url = str_replace("http:/", "https://", $url);
		}

		drupal_goto($url);

	} // End of goto_url()


	/**
	* Return true if the user is currently an admin for the registration
	* system.  False otherwise.
	*/
	function is_admin() {
		return(user_access(reg::PERM_ADMIN));
	}


	/**
	* Our exit hook.  Note that we cannot print anything out from here.
	* If we absolutely need to send a message back to the user, it can
	* be done with drupal_set_message() or similar.
	*/
	function hook_exit() {

		//
		// If we are in any of the public pages AND an anonymous user, 
		// clear out the cache for this page.  
		//
		if ($_GET["q"] == "reg"
			|| $_GET["q"] == "reg/verify"
			) {
			if ($GLOBALS["user"]->uid == 0) {
				$url = $GLOBALS["base_root"] . base_path() . $_GET['q'];
				cache_clear_all($url, "cache_page");
			}
		}

	} // End of exit()


	/**
	* This function gets the base path to Drupal installation.
	*
	* @return string a URL in the format of http://host/drupal,
	*	where host is the hostname and drupal is the path to the Drupal
	*	installation (which may be /).
	*/
	function get_base() {

		$retval = $GLOBALS["base_root"] . base_path();

		return($retval);

	} // End of get_base()


} // End of reg class

