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
	* @var This contains our last error that was raised.  Used mainly
	*	for testing.
	*/
	protected $lastError = "";


	/**
	* @var Do we want to display errors?  This should only be set to false
	*	when we're unit testing, since we'll be retrieving the errors via 
	*	getLastError() in that scenario.
	*/
	protected $errorDisplay = true;


	/**
	* @var Our array of constants for the registration system.
	*	These will only be retrieved through get_constant(), and 
	*	should be considered immutable.
	*	The reason why I am using these instead of real constants is
	*	so that I can wrap access to these in the future if need be.
	*	Encapsulating data and all that. :-)
	*/
	private $constants = array(
		//
		// The current year and the lowest possible badge number.
		// At some point, I should store these values externally.
		//
		"year" => 2009,
		"start_badge_num" => 250,
		//
		// Define constants for our permission names.
		//
		"perm_admin" => "admin reg system",
		"perm_staff" => "registration staff",
		"perm_register" => "register for a membership",
		"perm_onsitereg" => "register onsite",
		//
		// How many items displayed per pager in a pager?
		//
		"items_per_page" => 20,
		//
		// The maximum number we'll allow for donations from users.  
		// This is to limit damages in the case that we get hit with a 
		// fraudulent charge.
		//
		"donation_max" => "1000",
		//
		// The name of the variable that holds the "contact" email address 
		// for the reg system.  This may change in the future, once I 
		// create a setup screen where the Registration Director can 
		// enter a custom email address. :-)
		//
		"var_email" => "site_mail",

		//
		// Name of settings and their Drupal variables
		//
		"form_admin_fake_cc" => "reg_fake_cc",
		"form_admin_conduct_path" => "reg_conduct_path",
		"form_admin_fake_data" => "reg_fake_data",
		"form_admin_fake_email" => "reg_fake_email",
		"form_admin_no_ssl_redirect" => "reg_no_ssl_redirect",
		"form_admin_no_captcha" => "reg_no_captcha",

		//
		// Size of form fields
		//
		"form_text_size" => 40,
		"form_text_size_small" => 20,

		);

	/**
	* @var Place to store our gateway results for later retrieval by 
	*	unit tests.
	*/
	private $gateway_results = array();


	function __construct(&$message, &$fake, &$log) {
		$this->message = $message;
		$this->fake = $fake;
		$this->log = $log;
	}


	/**
	* Return our permissions.
	*	
	* @return array Scalar array of permissions for this module.
	*/
	function perm() {

		$retval = array();
		$retval[] = $this->get_constant("PERM_ADMIN");
		$retval[] = $this->get_constant("perm_staff");
		$retval[] = $this->get_constant("PERM_REGISTER");
		$retval[] = $this->get_constant("perm_onsitereg");

		return($retval);

	}


	/**
	* Check to see if a specific number is valid.
	*
	* @param mixed $num The value to check
	*
	* @return boolean True if valid.  False if otherwise.
	*/
	function is_valid_number($num) {

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
	function is_valid_float($num) {

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
	function is_negative_number($num) {

		//
		// Definitely not a negative number.
		//
		if (!$this->is_valid_number($num)) {
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

		if (!$this->is_valid_number($badge_num)) {
			$error = t("Badge number '%num%' is not a number!",
				array("%num%" => $badge_num)
				);
			$this->setError("badge_num", $error);
			return(false);
		}

		if ($this->is_negative_number($badge_num)) {
			$error = t("Badge number cannot be negative!");
			$this->setError("badge_num", $error);
			return(false);
		}

		// Assume success
		return(true);

	} // End of is_badge_num_valid()


	/**
	* Check to see if a certain badge number is available for a certain
	*	member.
	*
	* @param integer $reg_id The ID of the registration
	*
	* @param integer $badge_num The badge number to check for.
	*
	* @param integer $year The year of the badge number
	*
	* Note that this only works for the CURRENT year.  We should NOT
	*	be updating memberships for previous years EVER.
	*
	* @return boolean True if this badge number is available for the member.
	*	False if it is in use elsewhere.
	* 
	*/
	function is_badge_num_available($reg_id, $badge_num, $year) {

		//
		// If no badge number was entered, one will be assigned automatically.
		//
		if ($badge_num == "") {
			return(true);
		}

		//
		// Create a query to check and see if the badge number is in use.
		//
		$query = "SELECT id "
			. "FROM reg "
			. "WHERE "
			. "year='%s' "
			. "AND badge_num='%s' "
			. "AND id!='%s'"
			;
		$query_args = array($year, $badge_num, $reg_id);
		$cursor = db_query($query, $query_args);
		$row = db_fetch_array($cursor);

		if (!empty($row)) {
			$error = t("Badge number '%num%' is already in use!",
				array("%num%" => $year . "-" . $badge_num)
				);
			$this->setError("badge_num", $error);
			return(false);
		}

		//
		// Now, check to see if we have exceeded the highest assigned number.
		// We don't want this to happen, because said number will eventually
		// get stomped on sooner or later.
		
		$query = "SELECT * "
			. "FROM {reg_badge_num} "
			. "WHERE "
			. "year=%s "
			;
		$cursor = db_query($query, array($year));
		$row = db_fetch_array($cursor);

		if ($badge_num > $row["badge_num"]) {
			$error = t("Badge number '%num%' exceeds highest assigned "
				. "number of '%assigned%' for year '%year%'.  Please pick a lower number "
				. "or leave blank to automatically assign a number.",
				array(
					"%num%" => $badge_num,
					"%year%" => $year,
					"%assigned%" => $row["badge_num"],
				)
				);
			$this->setError("badge_num", $error);

		}

		return(true);

	} // End of is_badge_num_available()


	/**
	* This function actually charges our credit card.
	*
	* @param object $cc_gateway The authorize.net gateway.
	*
	* @param boolean $log_only Only log the transaction, do NOT charge 
	* the card.  This is used for when an admin enters a registration 
	* manually.
	* 
	* @return mixed Return the row ID from the transaction log
	*	if successful, null otherwise.
	*
	*/
	function charge_cc($data, $cc_gateway, $log_only = false) {

		//
		// Eventually I should make this passed into the constructor.
		//
		$reg_message = $this->message;


		//
		// Only calculate our badge cost if it wasn't already specified by 
		// a manual entry.
		//
		if (empty($data["badge_cost"])) {
			$data["badge_cost"] = $this->get_reg_cost($data["reg_level_id"]);
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

			if (!$this->is_test_mode()) {
				//
				// If we are not running in test mode, we are talking to
				// authorize.net in some fashion. (possibly in test mode)
				//

				//
				// Strip non-numerics out of our credit card number
				//
				$data["cc_num"] = ereg_replace("[^0-9]", "", 
					$data["cc_num"]);

				if ($data["cc_num"][0] == "3") {
					$display = $reg_message->load_display("cc-no-amex");
					$error = $display["value"];
					$this->setError("cc_num", $error);
					$this->log->log($error, "", WATCHDOG_WARNING);
					return(null);
				}

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

				if ($cc_gateway->is_test_mode()) {
					$data["test_request"] = 1;
				}

				//$data["total_cost"] = 1; // Debugging

				//
				// Try charging the card.
				//
				$gateway_results = $cc_gateway->charge_cc($data);
				$this->gateway_results = $gateway_results;

				//
				// If the card was declined or there was an error, complain
				// and exit.
				//
				$reg_message = $this->message;
				//$gateway_results["status"] = "success"; // Debugging

				if ($gateway_results["status"] == "declined") {
					$display = $reg_message->load_display("cc-declined");
					$error = $display["value"];
					$this->setError("cc_num", $error);
					$this->log->log($error, "", WATCHDOG_WARNING);
					return(false);

				} else if ($gateway_results["status"] == "bad avs") {
					$display = $reg_message->load_display("cc-declined-avs");
					$error = $display["value"];
					$this->setError("", $error);
					$this->log->log($error, "", WATCHDOG_WARNING);
					return(false);

				} else if ($gateway_results["status"] == "bad cvv") {
					$display = $reg_message->load_display("cc-declined-cvv");
					$error = $display["value"];
					$this->setError("cvv", $error);
					$this->log->log($error, "", WATCHDOG_WARNING);
					return(false);

				} else if ($gateway_results["status"] == "error") {
					$display = $reg_message->load_display("cc-error");
					$error = $display["value"];
					$this->setError("cc_num", $error);
					$this->log->log($error, "", WATCHDOG_WARNING);
					return(false);

				}

				//
				// Otherwise, save the important fields from the transaction
				// for logging at the end of this function.
				//
				$data["gateway_auth_code"] = $gateway_results["auth_code"];
				$data["gateway_transaction_id"] = 
					$gateway_results["transaction_id"];
				$data["gateway_avs"] = $gateway_results["avs_response"];
				$data["gateway_cvv"] = $gateway_results["cvv_response"];
				$data["gateway_transaction_id"] = 
					$gateway_results["transaction_id"];

			} else {
	
				$message = t("We are in testing mode.  Automatically allow this "
					. "'credit card'");
				$data["reg_log_id"] = $this->log->log($message);

				//
				// Generate random gateway data.
				//
				$data["gateway_auth_code"] = $this->fake->get_string(6);
				$data["gateway_transaction_id"] = $this->fake->get_number(
					0, (pow(10, 9)));

				$avs_codes = array("Y", "N", "D", "X", "Z");
				$data["gateway_avs"] = $this->fake->get_item($avs_codes);

				$cvv_codes = array("Y", "N", "X", "F");
				$data["gateway_cvv"] = $this->fake->get_item($cvv_codes);

			}
	
		} else {
			$message = t("Logging a manually made transaction/comp/etc. ")
				. t("No card charging took place just now.");
			$data["reg_log_id"] = $this->log->log($message);

		}

		$reg_trans_id = $this->log->log_trans($data);

		return($reg_trans_id);

	} // End of charge_cc()


	/**
	* This returns our gateway results from the most recent transaction.
	* Mostly this is for testing/debugging purposes.
	*
	* @return array Associative array of details on the transaction
	*/
	function getGatewayResults() {
		return($this->gateway_results);
	} // End of getGatewayResults()


	/**
	* Are we running in test mode?  If so, then we're not charging the
	*	credit card.
	*
	* @retval boolean True if we are running in test mode.  False otherwise.
	*/
	function is_test_mode() {

		$retval = variable_get(
			$this->get_constant("FORM_ADMIN_FAKE_CC"), false);

		return($retval);

	} // End of is_test_mode()


	/**
	* Our main registration page.
	*/
	function registration() {

		$retval = "";

		//
		// Get our current membership levels and make sure that we
		//
		$levels = $this->get_valid_levels();

		$reg_message = $this->message;
		$log = $this->log;

		if (empty($levels)) {
			$data = array(
				"!email" => variable_get($this->get_constant("VAR_EMAIL"), ""),
				);
	
			$message = $reg_message->load_display("no-levels-available", $data);
			$retval = $message["value"];

			$message = t("A user tried to visit the public registration page, "
				. "but there were no membership levels available.");
			$log->log($message, "", WATCHDOG_WARNING);

			return($retval);

		}

		//
		// Load our custom message, if we have done.
		//
		$message = $reg_message->load_display("header");
		$retval .= $message["value"];

		$retval .= drupal_get_form("reg_registration_form");

		$message = $reg_message->load_display("footer");
		$retval .= $message["value"];

		return($retval);

	} // End of registration()


	/**
	* Are we currently in SSL?
	*
	* @return boolean True if we are.  False otherwise.
	*/
	function is_ssl() {
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
	function force_ssl() {

		if (!$this->is_ssl()) {

			$no_ssl = variable_get($this->get_constant(
				"form_admin_no_ssl_redirect"), "");

			//
			// Only do SSL redirection if not explicitly disabled.
			//
			if ($no_ssl) {
				$message = t("DANGER!  You have SSL redirection turned off!  "
					. "If this is a production machine, please change that "
					. "immediately! (If this is a dev machine, you may "
					. "ignore this message. :-)");
				$this->log->log($message, "", WATCHDOG_ERROR);

			} else {
				$uri = request_uri();
				$_SERVER["SERVER_PORT"]= 443;
				$this->goto_url($uri);

			}

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
	function goto_url($uri) {

		$url = url($uri, array("absolute" => true));

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
		if ($this->is_ssl()) {
			$url = eregi_replace("//", "/", $url);

			//
			// One more issue here... when running in SSL under 
			// WAMP, the URL will start with "https:/", which is 
			// bad.  I'm not sure why the URL is different, if 
			// it's because we're running under Apache vs. nginx, 
			// PHP vs PHP-cgi, or something entirely different.  
			// At some point, I really need to write a unit test 
			// for this logic...
			// 
			$url = ereg_replace("^https:/([^/])", "https://\\1", $url);

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
		return(user_access($this->get_constant("PERM_ADMIN")));
	}


	/**
	* Our init hook.
	*/
	function hook_init() {

		//
		// Load our Javascript
		//
		$path = drupal_get_path("module", "reg");
		drupal_add_js($path . "/reg.js", "module");

		//
		// Include our CSS
		//
		$path = drupal_get_path("module", "reg") . "/reg.css";
		drupal_add_css($path, "module", "all", false);

	} // End of hook_init()


	/**
	* Our exit hook.  Note that we cannot print anything out from here.
	* (At least not anything for the app.  Any print() calls will cause
	*	text to be on the bottom of the page.)
	* If we absolutely need to send a message back to the user, it can
	* be done with drupal_set_message() or similar.
	*/
	function hook_exit() {

		//
		// If we are in any of the public pages AND an anonymous user, 
		// clear out the cache for this page.  
		//
		if ($_GET["q"] == "reg"
			|| $_GET["q"] == "reg/fake"
			|| $_GET["q"] == "reg/verify"
			|| $_GET["q"] == "reg/success"
			|| $_GET["q"] == "onsitereg"
			|| $_GET["q"] == "onsitereg/fake"
			|| $_GET["q"] == "onsitereg/success"
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


	/**
	* Get the next badge number for the current year.
	*
	* @return integer The badge number.  This number can be assigned
	*	to a specific user.
	*/
	function get_badge_num($year = "") {

		if (empty($year)) {
			$year = $this->get_constant("YEAR");
		}

		$query = "UPDATE {reg_badge_num} "
			. "SET badge_num = @val := badge_num+1 "
			. "WHERE year=%s ";
		db_query($query, array($year));
		$cursor = db_query("SELECT @val AS badge_num");
		$results = db_fetch_array($cursor);

		return($results["badge_num"]);

	} // End of get_badge_num()


	/**
	* Retrieve the most recent insert ID from a database insert.
	*
	* @return integer The most recent insert ID.
	*/
	function get_insert_id() {

		$cursor = db_query("SELECT LAST_INSERT_ID() AS id");
		$row = db_fetch_array($cursor);
		$retval = $row["id"];
		return($retval);

	} // End of get_insert_id()


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
	function get_cc_name($cc_type, $cc_num) {

		//
		// Get the string type for a card if we don't already have it.
		//
		$cc_type_int = intval($cc_type);
		if ($cc_type == (string)$cc_type_int) {
			$types = $this->get_cc_types();
			$cc_type = $types[$cc_type];
		}

		$retval = "${cc_type} ending in '" 
			. $this->get_cc_last_4($cc_num) . "'";

		return($retval);

	} // End of get_cc_name()


	/**
	* Get the last 4 digits of a credit card number.
	*
	* We do NOT want to store the entire crediit card number in our system.
	* That way, it limits exposure in case of a security comprismise and 
	* it lowers my blood pressure. :-)
	*
	* @return string The last 4 digits from our credit card number.
	*/
	function get_cc_last_4($cc_num) {

		$retval = substr($cc_num, -4);

		return($retval);

	} // End of get_last_4()


	/**
	* Return a list of currently valid registration levels.
	*
	* @return array The key is the membersip ID and the value is
	*	an associative array of member data.
	*/
	function get_valid_levels() {

		static $retval = array();

		if (!empty($retval)) {
			return($retval);
		}

		$timestamp = gmmktime();
		$query = "SELECT * FROM {reg_level} "
			. "WHERE "
			. "start <= '%s' AND end >= '%s' "
			. "ORDER BY price "
			;
		$query_args = array($timestamp, $timestamp);
		$cursor = db_query($query, $query_args);

		while ($row = db_fetch_array($cursor)) {
			$id = $row["id"];
			$retval[$id] = $row;
		}

		return($retval);

	} // End of get_valid_levels()


	/**
	* Determine the cost of a registration based on the reg_level_id.
	*
	* @return integer The cost of the registration.
	*/
	function get_reg_cost($level_id) {

		$query = "SELECT price FROM {reg_level} "
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
	function get_reg_type_id($level_id) {

		$query = "SELECT reg_type_id FROM {reg_level} "
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
	function get_types() {

		//
		// Cache our rows between calls
		//
		static $retval = array();

		if (!empty($retval)) {
			return($retval);
		}

		$query = "SELECT * FROM {reg_type} ORDER BY weight, member_type";
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

		$query = "SELECT * FROM {reg_trans_type} ";
		$cursor = db_query($query);

		while ($row = db_fetch_array($cursor)) {
			$id = $row["id"];
			$name = $row["trans_type"];
			$retval[$id] = $name;
		}

		return($retval);

	} // End of get_trans_types()


	/**
	* Retrieve data on a payment type based on an ID.
	*/
	function get_payment_type($id) {

		$query = "SELECT "
			. "payment_type "
			. "FROM {reg_payment_type} "
			. "WHERE "
			. "id='%s' "
			;
		$query_args = array($id);
		$cursor = db_query($query, $query_args);

		$row = db_fetch_array($cursor);

		return($row["payment_type"]);

	} // End of get_payment_type()


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

		$query = "SELECT * FROM {reg_payment_type} ";
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
	function get_statuses() {

		//
		// Cache our rows between calls
		//
		static $retval = array();

		if (!empty($retval)) {
			return($retval);
		}

		$query = "SELECT * FROM {reg_status} ORDER BY weight, status";
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
	function get_cc_types() {

		//
		// Cache our values between calls
		//
		static $retval = array();

		if (!empty($retval)) {
			return($retval);
		}

		$query = "SELECT * FROM {reg_cc_type}";
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
	function get_shirt_sizes($disabled = false) {

		//
		// Cache our values between calls
		//
		static $retval = array();
		
		if (!empty($retval)) {
			return($retval);
		}

		$query = "SELECT * FROM {reg_shirt_size}";
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
	function get_cc_exp_months() {
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
	*
	* @param integer $backwards How many years to start the list at before
	*	the current year.
	*
	* @return array The key and value re the expiration years, 
	*	starting at this year, minus the backward number of years.
	*/
	function get_cc_exp_years($backwards = 0) {

		$retval = array();

		$start = date("Y");
		$end = $start + 7;
		$start += ($backwards * -1);

		for ($i = $start; $i<$end; $i++) {
			$retval[$i] = $i;
		}

		return($retval);

	} // End of get_cc_exp_years()


	/**
	* This function will calculate a UNIX timestamp based on the year, 
	*	month, and day. 
	*/
	function get_time_t($year, $month, $day) {

		$retval = gmmktime(0, 0, 0, $month, $day, $year);

		//
		// Do NOT adjust this timestamp by any GMT offset if you are 
		// storing it.  If you do (like I used to), it WILL throw off
		//  your times when you switch timezones. 
		//
		// time_ts are absolute values.  The only time you should apply
		// a timezone/GMT offset to them is when displaying them.
		//
		// If you are displaying something like a credit card expiration
		// date, which requires a time of midnight, use a GMT offset
		// of *0* in Drupal's format_date() function.
		//

		return($retval);

	} // End of get_time_t()


	/**
	* Turn a year, month, and date into a date that MySQL can understand
	* for a field of type DATE.
	*/
	function get_date($year, $month, $day) {
		$retval = $year . "-" . sprintf("%02d", $month)
			. "-" . sprintf("%02d", $day);
		return($retval);
	} // End of get_date()


	/**
	* Turn a date string from MySQL (YYYY-MM-DD) into something a little
	* more readable.
	* Note that http://us2.php.net/mktime swears that versions of PHP
	* after 5.1.0 can handle dates before the UNIX epoch. (1 Jan 1970)
	*/
	function get_date_string($date) {

		$time = strtotime($date);
		$retval = date("F jS, Y", $time);

		return($retval);

	} // End of get_date_stirng()


	/**
	* Turn a date string from MySQL into an array that can be used
	* in the editing form.
	*/
	function get_date_array($date) {

		$retval = array();

		$results = explode("-", $date);

		$retval["year"] = $results[0];
		$retval["month"] = sprintf("%d", $results[1]);
		$retval["day"] = sprintf("%d", $results[2]);

		return($retval);

	} // End of get_date_array();


	/**
	* Format a badge number so that it is filled with leading zeros.
	*/
	function format_badge_num($badge_num) {

		if ($badge_num == "0") {
			$retval = sprintf("%04d", $badge_num);
		} else if ($badge_num == "" || $badge_num == null) {
			$retval = t("(Empty)");
		} else {
			$retval = sprintf("%04d", $badge_num);
		}

		return($retval);

	} // End of format_badge_num()


	/**
	* Get changed fields from old data to new data, and create a string
	* that details what was changed.  This way, we can have detailed
	* change auditing.
	*
	* @param array $data The new set of data.
	*
	* @param array $old_data The old set of data
	*
	* @return mixed If no changed were found, null is returned.  Otherwise
	*	a string detailing the changes is returned.
	*/
	function get_changed_data(&$data, &$old_data) {

		$retval = "";

		foreach ($old_data as $key => $value) {

			if ($value != $data[$key]) {

				$new_value = $data[$key];
				$old_value = $value;

				//
				// If this value is a time_t, turn it into a 
				// human-readable value.
				//
				if ($key == "start" || $key == "end") {
					$old_value = format_date($old_value, "custom", "r");
					$new_value = format_date($new_value, "custom", "r");
				}

				$retval .= t("Key '!key' set to '!new_value' "
					. "(old value: '!old_value').\n",
					array(
						"!key" => $key,
						"!new_value" => $new_value,
						"!old_value" => $old_value,
						)
					);
			}
		}

		if (!empty($retval)) {
			$retval = "(Audit Log: Fields changed:\n$retval)\n";
		}

		return($retval);

	} // End of get_changed_data()


	/**
	* Return the URL of our verification page.
	*/
	function get_verify_url() {
		$retval = $this->get_base() . "reg/verify";
		return($retval);
	} // End of get_verify_url()


	/**
	* Return a constant, based on the key.
	*/
	function get_constant($key) {

		$key = strtolower($key);

		if (!empty($this->constants[$key])) {
			$retval = $this->constants[$key];
		} else {
			$error = "Constant '$key' not found!";
			throw new Exception($error);
		}

		return($retval);

	} // End of get_constant()


    /**
	* Turn an array into an encoded key=value string, which can then
	*	be added onto a URL as an argument.
	* The reason for the extra encoding is to keep Drupal from tampering
	*	with the data.
    *
    * @param array $data Array of key/value pairs.
    *
    * @return string The encoded string of each of these.
    */
    function array_to_get_data($data) {

        //print_r($data); // Debugging
        $retval = http_build_query($data);
        $retval = rawurlencode($retval);

        return($retval);

    } // End of array_to_get_data()


	/**
	* Decode GET method data and turn it into an array that PHP can use.
	*
	* @param string $get_data GET data, which corresonds to a result
	* 	from arg(), and has been encoded with rawurlencode().
	*	The reason for the encoding is to prevent Drupal from tampering 
	*	with the string.
	*/
	protected function get_data_to_array($get_data) {

		$data = rawurldecode($get_data);
		$data = html_entity_decode($data);
		parse_str($data, $retval);
		//print_r($retval); // Debugging

		return($retval);

	} // End of get_data_to_array()


	/**
	* Wrapper function used for setting an error.
	*
	* @param string $name The name of a form element
	*
	* @param string $error The erroor string
	*/
	function setError($name, $error) {
		if ($this->errorDisplay) {
			form_set_error($name, $error);
		}
		$this->lastError = $error;
	}


	/**
	* Retrieve the last error that was set.
	*
	* @return string The last error that was set.
	*/
	function getLastError() {
		return($this->lastError);
	}


	/**
	* Set our errorDisplay variable.
	*
	* @param boolean $value True if we want errors displayed, 
	*	false otherwise.
	*/
	function setErrorDisplay($value) {
		$this->errorDisplay = $value;
	}


	/**
	* Is the member a minor?
	*
	* @param string $birthdate Date in YYYY-MM-DD format.
	*
	* @param string $now Optional date to check against in YYYY-MM-DD format.
	*	If not specified, checks against today.
	*
	* @return boolean True if the member is a minor, false otherwise.
	*/
	function isMinor($birthdate, $now = "") {

		if (empty($now)) {
			$now = date("Y-n-j", time());
		}

		//
		// Split our dates into arrays, and then subtract 18 from the "now"
		// year so that we have a target birthdate.
		//
		$birthdate = explode("-", $birthdate);
		$now = explode("-", $now);
		$now[0] -= 18;

		//
		// Sanity checking
		//
		if (empty($birthdate[2])) {
			$error = "Invalid birthdate: " . print_r($birthdate, true);
			throw new Exception($error);
		}

		if (empty($now[2])) {
			$error = "Invalid date: " . print_r($now, true);
			throw new Exception($error);
		}

		//
		// Finally, check our date!
		//
		if ($birthdate[0] < $now[0]) {
			//
			// Definitely older than 18
			//
			return(false);

		} else if ($birthdate[0] > $now[0]) {
			//
			// Definitely younger than 18
			//
			return(true);

		} else {
			//
			// Uh oh, the year is the same.  Check months and days now.
			//
			if ($birthdate[1] < $now[1]) {
				return(false);

			} else if ($birthdate[1] > $now[1]) {
				return(true);

			} else {
				if ($birthdate[2] < $now[2]) {
					return(false);

				} else if ($birthdate[2] > $now[2]) {
					return(true);

				} else {
					//
					// Happy 18th birthday!
					//
					return(false);
				}

			}

		}

	} // End of isMinor()


	/**
	* Get all possible years for levels.
	*
	* @return array Associative array where the keys and values 
	*	are valid years.
	*/
	function getYears() {

		$retval = array();

		$query = "SELECT DISTINCT year "
			. "FROM {reg_level} "
			. "ORDER BY year";
		$cursor = db_query($query);

		while ($row = db_fetch_array($cursor)) {
			$year = $row["year"];
			$retval[$year] = $year;
		}

		return($retval);

	} // End of getYears()


	/**
	* Set our starting badge number for a specified con year.
	* This funciton is usually called after a new level is created.
	*
	* @return boolean True if an entry was made into the badge table, 
	*	false otherwise.
	*/
	function initBadgeNum($year) {

		$query = "SELECT * FROM {reg_badge_num} "
			. "WHERE year=%s";
		$query_args = array($year);
		$cursor = db_query($query, $query_args);
		$row = db_fetch_array($cursor);

		//
		// We already have a row for this year.  Stop.
		//
		if (!empty($row)) {
			return(false);
		}

		//
		// No row found.  Add a new one.
		//
		$badge_num = $this->get_constant("start_badge_num");
		$query = "INSERT INTO {reg_badge_num} "
			. "(year, badge_num) "
			. "VALUES (%s, %s) "
			;
		$query_args = array($year, $badge_num);
		db_query($query, $query_args);

		return(true);

	} // End of initBadgeNum()


} // End of reg class

