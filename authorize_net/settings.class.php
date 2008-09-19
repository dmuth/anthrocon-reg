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
		$retval .= drupal_get_form("authorize_net_settings_form");
		return($retval);
	}


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

		$retval["test_mode"] = array(
			"#type" => "checkbox",
			"#title" => t("Credit Card Test Mode?"),
			"#default_value" => $this->variable_get(self::TEST_MODE),
			"#description" => t("If set, the gateway will be used in "
				. "\"test mode\".  <b>Do NOT use in production!</b>"),
			);

		$retval["submit"] = array(
			"#type" => "submit",
			"#value" => t("Save"),
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
			"#default_value" => $this->variable_get(self::LOGIN_ID),
			"#description" => $desc_text,
			"#size" => self::FORM_TEXT_SIZE,
			);

		$retval["api_transaction_key"] = array(
			"#type" => "textfield",
			"#title" => t("Authorize.net API Transaction Key"),
			"#default_value" => $this->variable_get(self::TRANSACTION_KEY),
			"#description" => $desc_text,
			"#size" => self::FORM_TEXT_SIZE,
			);

/*
TODO: I need to figure this out.
	- Maybe a link that goes to authorize_net/test?
		- I'd have to tweak the menu to pass in the argment through a callback, and act on it in settings()


I also need to write a sanity_check function, that throws an error if the ID or key are unset.  It should do this in the mains settings page, though.


		$retval["test_gateway_details"] = array(
			"#value" => t("Test the gateway with the above settings:"),
			);

		$retval["test_gateway"] = array(
			"#type" => "submit",
			"#value" => t("Test Gateway"),
			);
*/

		return($retval);

	} // End of get_credentials()


	function validate($form_id, &$data) {
if ($data["op"] == t("Test Gateway")) {
//print "TEST";
}
	}


	function submit($form_id, &$data) {

		$credentials = $data["credentials"];

		$this->variable_set(self::LOGIN_ID, $credentials["api_login_id"]);
		$this->variable_set(self::TRANSACTION_KEY, 
			$credentials["api_transaction_key"]);
		$this->variable_set(self::TEST_MODE, $data["test_mode"]);

		drupal_set_message(t("Settings updated"));

	} // End of submit()


} // End of authorize_net_settings class

