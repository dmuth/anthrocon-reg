<?php

/**
* This class holds our main setings page.
*/
class reg_admin_settings extends reg {


	function __construct($admin) {
		$this->admin = $admin;
	}

	/**
	* Our main settings page.
	*/
	function settings() {

		$retval = drupal_get_form("reg_admin_settings_form");

		return($retval);

	} // End of admin()


	/**
	* This function creates the data structure for our main admin form.
	*
	* @return array Associative array of registration form.
	*/
	function settings_form() {

		$retval = array();

		$retval["conduct_path"] = array(
			"#type" => "textfield",
			"#title" => t("Standards of Conduct Path"),
			"#default_value" => variable_get(
				$this->get_constant("FORM_ADMIN_CONDUCT_PATH"), ""),
			"#description" => t("If a valid path is entered here, "
				. "the user will be forced to agree to the "
				. "Standards of Conduct before registering.  Do NOT use a "
				. "leading slash."),
			"#size" => $this->get_constant("FORM_TEXT_SIZE"),
			);

		$retval["no_production"] = array(
			"#type" => "fieldset",
			"#title" => t("Things NOT to set in production"),
			"#tree" => "true",
			"#collapsible" => true,
			"#collapsed" => false,
			);

		$retval["no_production"]["fake_cc"] = array(
			"#type" => "checkbox",
			"#title" => t("Credit Card Test Mode?"),
			"#default_value" => variable_get(
				$this->get_constant("FORM_ADMIN_FAKE_CC"), false),
			"#description" => t("If set, credit card numbers will "
				. "not be sent off to our merchant gateway.  "
				. "Do NOT use in production!"),
			);

		$retval["no_production"]["fake_data"] = array(
			"#type" => "checkbox",
			"#title" => t("Data entry test mode"),
			"#default_value" => variable_get(
				$this->get_constant("FORM_ADMIN_FAKE_DATA"), ""),
			"#description" => t("Set this to allow fake data to be created on "
				. "registraiton forms.  This will create an alternate submit "
				. "button to poulate the form with fake data.  Do NOT use in "
				. "production!"),
			);

		$retval["no_production"]["fake_email"] = array(
			"#type" => "checkbox",
			"#title" => t("Fake sending of emails?"),
			"#default_value" => variable_get(
				$this->get_constant("FORM_ADMIN_FAKE_EMAIL"), ""),
			"#description" => t("If set, emails will NOT be sent.  This is a "
				. "really good idea when testing."),
			);

		$retval["no_production"]["no_ssl_redirect"] = array(
			"#type" => "checkbox",
			"#title" => t("Turn off SSL redirection on pages?"),
			"#default_value" => variable_get(
				$this->get_constant("form_admin_no_ssl_redirect"), ""),
			"#description" => t("If set, redirection to SSL-enabled pages will be "
				. "turned off.  This is only useful for running unit tests.  "
				. "Do NOT enable in production, EVER!"),
			);

		$retval["no_production"]["no_captcha"] = array(
			"#type" => "checkbox",
			"#title" => t("Disable CAPTCHA on registration form?"),
			"#default_value" => variable_get(
				$this->get_constant("form_admin_no_captcha"), ""),
			"#description" => t("If set, a CAPTCHA will not be displayed on "
				. "the registration page.  This is useful for functionality "
				. "testing."),
			);

		$retval["submit"] = array(
			"#type" => "submit",
			"#value" => "Save"
			);

		return($retval);

	} // End of form()


	/**
	* This function is called to validate the form data.
	* If there are any issues, form_set_error() should be called so
	* that form processing does not continue.
	*/
	function settings_form_validate(&$data) {

		//
		// If a path was entered, make sure it is a valid alias or
		// a valid node.
		//
		if (!empty($data["conduct_path"])) {

			if ($data["conduct_path"][0] == "/") {
				$error = t("You used a leading slash in %path even after I "
					. "told you not to!",
					array(
						"%path" => $data["conduct_path"],
					));
				form_set_error("conduct_path", $error);

			} else if (!drupal_lookup_path("source", $data["conduct_path"])) {
				$results = explode("/", $data["conduct_path"]);
				$nid = $results[1];
				if (empty($nid) || !node_load($nid)) {
					form_set_error("conduct_path", 
						t("Invalid path entered for Standards of Conduct"));
				}
			}

		}

	} // End of form_validate()


	/**
	* This function is called after our form has been successfully validated.
	*
	* It should make any necessary changes to the database.  At the 
	* conclusion of this funciton, the user is redirected back to the 
	* form page.
	*/
	function settings_form_submit(&$data) {

		$admin = $this->admin;
		$admin->variable_set($this->get_constant("FORM_ADMIN_FAKE_CC"), 
			$data["no_production"]["fake_cc"]);
		$admin->variable_set($this->get_constant("FORM_ADMIN_FAKE_DATA"), 
			$data["no_production"]["fake_data"]);
		$admin->variable_set($this->get_constant("FORM_ADMIN_FAKE_EMAIL"), 
			$data["no_production"]["fake_email"]);
		$admin->variable_set($this->get_constant("FORM_ADMIN_CONDUCT_PATH"), 
			$data["conduct_path"]);
		$admin->variable_set($this->get_constant("form_admin_no_ssl_redirect"), 
			$data["no_production"]["no_ssl_redirect"]);
		$admin->variable_set($this->get_constant("form_admin_no_captcha"), 
			$data["no_production"]["no_captcha"]);

		drupal_set_message("Settings updated");

		$uri = "admin/reg/settings";
		$this->goto_url($uri);

	}


} // End of reg_admin_settings class

?>
