<?php

/**
* This class is responsible for the settings page.
*/
class authorize_net_settings extends authorize_net {

	function __construct($reg, $log) {
		parent::__construct($reg, $log);
	}


	/**
	* Our main settings page.
	*/
	function settings() {

		$retval = "";

		//
		// Only run a sanity check if we're not processing a form.
		//
		if (empty($_REQUEST["form_id"])) {
			$this->sanity_check();
		}

		$retval .= drupal_get_form("authorize_net_settings_form");

		return($retval);

	} // End of settings()


	/**
	* The settings form.
	*/
	function form() {

		$retval = array();

		$desc_text = t("This can be obtained through Authorize.net's "
			. "merchant login at !link.",
			array(
				"!link" => l("https://account.authorize.net/", 
					"https://account.authorize.net/"),
			));

		$retval["credentials"] = $this->get_credentials();
		$retval["test_gateway"] = $this->get_test_gateway();

		$retval["submit"] = array(
			"#type" => "submit",
			"#value" => t("Save"),
			);

		$retval["submit_test_gateway"] = array(
			"#type" => "submit",
			"#value" => t("Save and Test Credentials"),
			//
			// Different function to be called if this button is clicked.
			//
			"#submit" => array("authorize_net_settings_form_submit_test_gateway"),
			);

		return($retval);

	} // End of form()


	/**
	* Get the fieldset relating to credentials.
	*/
	function get_credentials() {

		$retval = array(
			"#type" => "fieldset",
			"#title" => t("Credentials"),
			"#tree" => true,
			);

		$retval["api_login_id"] = array(
			"#type" => "textfield",
			"#title" => t("Authorize.net API Login ID"),
			"#default_value" => $this->variable_get(
				$this->get_constant("LOGIN_ID")),
			"#description" => $desc_text,
			"#size" => $this->get_constant("FORM_TEXT_SIZE"),
			);

		$retval["api_transaction_key"] = array(
			"#type" => "textfield",
			"#title" => t("Authorize.net API Transaction Key"),
			"#default_value" => $this->variable_get(
				$this->get_constant("TRANSACTION_KEY")),
			"#description" => $desc_text,
			"#size" => $this->get_constant("FORM_TEXT_SIZE"),
			);

		$retval["test_mode"] = array(
			"#type" => "checkbox",
			"#title" => t("Authorize.net Test Mode?"),
			"#default_value" => $this->variable_get(
				$this->get_constant("TEST_MODE")),
			"#description" => t("If set, the gateway will be used in "
				. "\"test mode\" and cards will not be charged.  "
				. "<b>Do NOT use in production!</b>"),
			);

		return($retval);

	} // End of get_credentials()


	/**
	* Return our fieldset for gateway testing params.
	*/
	function get_test_gateway() {

		$retval = array(
			"#type" => "fieldset",
			"#title" => t("Gateway test settings"),
			"#tree" => true,
			);

		if (empty($_SESSION["reg"]["authorize_net"]["test_cost"])) {
			$_SESSION["reg"]["authorize_net"]["test_cost"] = "1.00";
		}


		$url = "http://developer.authorize.net/guides/AIM/"
			. "Transaction_Response/"
			. "Response_Reason_Codes_and_Response_Reason_Text.htm"
			;

		$retval["total_cost"] = array(
			"#type" => "textfield",
			"#title" => t("Amount to \"charge\""),
			"#default_value" => 
				$_SESSION["reg"]["authorize_net"]["test_cost"],
			"#size" => 5,
			"#description" => t("The amount entered will force a specific "
				. "!link.  (!link_full)<br/>\n"
				. "Key codes: 1 == success, 2 == decline, 5 == error "
					. "27 == AVS mismatch, 78 = CVV mismatch",
				array(
					"!link" => l(t("Response Reason Code"), $url),
					"!link_full" => l(t("Full Authorize.net documentation"),
						"http://developer.authorize.net/guides/AIM/"),
				)),
			);

		$retval["detail"] = array(
			"#type" => "item",
			"#value" => t("<b>Note:</b> Testing credentials will be "
				. "done in test mode.  Your card will NOT be charged."),
			);

		return($retval);

	} // End of get_test_gateway()


	/**
	* A little function that checks to see if key fields are defined.
	* If not, it sets an error to warn the user.
	*/
	function sanity_check() {

		if (!$this->variable_get($this->get_constant("LOGIN_ID"))
			) {
			$error = t("Login ID is not specified.  It is needed "
				. "to charge credit cards.");
			form_set_error("credentials][api_login_id", $error);
		}

		if (!$this->variable_get($this->get_constant("TRANSACTION_KEY"))
			) {
			$error = t("API Transaction Key is not specified.  It is "
				. "needed to charge credit cards.");
			form_set_error("credentials][api_transaction_key", $error);
		}

		if (!function_exists("curl_init")) {
			$error = t("Function 'curl_init' not found.  Is the CURL "
				. "library installed with PHP?");
			form_set_error("curl_init", $error);
		}

		if (!function_exists("curl_setopt")) {
			$error = t("Function 'curl_setopt' not found.  Is the CURL "
				. "library installed with PHP?");
			form_set_error("curl_setopt", $error);
		}


	} // End of sanity_check()


	function validate(&$data) {

	} // End of validate()


	/**
	* Run a test transaction against the gateway, in test mode.
	*
	* @param array $data The data submitted to the form.
	*/
	function test_gateway($data) {

		$cust_data = array();

		$cust_data["test_request"] = "TRUE"; // Special :-)

		$cust_data["cc_num"] = "4222222222222";
		$cust_data["cc_exp"] = "01/2015";

		$cust_data["total_cost"] = 1;
		if (!empty($data["test_gateway"]["total_cost"])) {
			$cust_data["total_cost"] = $data["test_gateway"]["total_cost"];
		}

		$cust_data["cvv"] = "123";
		$cust_data["invoice_number"] = "1234567890";
		$cust_data["description"] = "a test description";
		$cust_data["first"] = "Firstname";
		$cust_data["last"] = "Lastname";
		$cust_data["address1"] = "address1";
		$cust_data["address2"] = "address2";
		$cust_data["city"] = "city name";
		$cust_data["state"] = "state name";
		$cust_data["zip"] = "zipcode";
		$cust_data["country"] = "USA";
		$cust_data["phone"] = "123-456-7890";
		$cust_data["email"] = "doug.muth@gmail.com";

		$status = $this->charge_cc($cust_data);

		//
		// Display our response.
		//
		$message = t("Authorize.net response: !response",
			array("!response" => $status["raw_response"]));
		drupal_set_message($message);

		$message = t("charge_cc() status: !status", 
			array("!status" => $status["status"]));
		drupal_set_message($message);

		if ($status["status"] == "success") {
			$message = t("This (test) transaction was successful.");

		} else if ($status["status"] == "declined") {
			$message = t("This (test) transaction was declined.");

		} else if ($status["status"] == "bad avs") {
			$message = t("Test (test) transaction had an AVS mismatch.");

		} else if ($status["status"] == "bad cvv") {
			$message = t("Test (test) transaction had a bad CVV code.");

		} else if ($status["status"] == "error") {
			$message = t("Payment gateway (test) error.");

		} else {
			$message = t("Unknown status: !status",
				array("!status" => $status["status"]));

		}

		drupal_set_message($message);

		return($response);

	} // End of test_gateway()


	/**
	* Our submit function.
	*
	* @param array $data Associative array of form data.
	*
	* @param $test_cred boolean Do we also want to test the gateway?
	*/
	function submit(&$data, $test_cred = false) {

		$credentials = $data["credentials"];
		$test_gateway = $data["test_gateway"];

		$this->variable_set($this->get_constant("LOGIN_ID"), 
			$credentials["api_login_id"]);
		$this->variable_set($this->get_constant("TRANSACTION_KEY"), 
			$credentials["api_transaction_key"]);
		$this->variable_set($this->get_constant("TEST_MODE"), 
			$credentials["test_mode"]);

		$_SESSION["reg"]["authorize_net"]["test_cost"] = 
			$test_gateway["total_cost"];

		drupal_set_message(t("Settings updated"));

		//
		// Test our credentials by hitting authorize.net in test mode.
		// Do this AFTER the data is saved in case we updated the
		// login ID or transaction key.
		//
		if ($test_cred) {
			$this->test_gateway($data);
		}

	} // End of submit()


} // End of authorize_net_settings class

