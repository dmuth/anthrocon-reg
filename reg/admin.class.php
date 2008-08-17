<?php

/**
* This is the reg_admin class, which holds functions related to the 
*	administrative end of the registration system.
*/
class reg_admin {

	/**
	* Our constructor.  This should never be called.
	*/
	function __construct() {
		$error = "You tried to instantiate this class even after I told "
			. "you not to!";
		throw new Exception($error);
	}


	/**
	* Our main admin page.
	*/
	static function settings() {

		$retval = "";
		$retval = drupal_get_form("reg_admin_settings_form");

		return($retval);

	} // End of admin()

	

	/**
	* This function creates the data structure for our main admin form.
	*
	* @return array Associative array of registration form.
	*/
	static function settings_form() {

		$retval = array();

		$retval["conduct_path"] = array(
			"#type" => "textfield",
			"#title" => "Standards of Conduct Path",
			"#default_value" => variable_get(reg_form::FORM_ADMIN_CONDUCT_PATH, ""),
			"#description" => "If a valid path is entered here, "
				. "the user will be forced to agree to the "
				. "Standards of Conduct before registering.  Do NOT use a "
				. "leading slash.",
			"#size" => reg_form::FORM_TEXT_SIZE,
			);

		$retval["no_production"] = array(
			"#type" => "fieldset",
			"#title" => "Things not to set in production",
			"#tree" => "true",
			"#collapsible" => true,
			"#collapsed" => false,
			);

		$retval["no_production"]["fake_cc"] = array(
			"#type" => "checkbox",
			"#title" => "Credit Card Test Mode?",
			"#default_value" => variable_get(reg_form::FORM_ADMIN_FAKE_CC, false),
			"#description" => "If set, credit card numbers will "
				. "not be processed.  Do NOT use in production!",
			);

		$retval["no_production"]["fake_data"] = array(
			"#type" => "checkbox",
			"#title" => "Data entry test mode",
			"#default_value" => variable_get(reg_form::FORM_ADMIN_FAKE_DATA, ""),
			"#description" => "Set this to allow fake data to be created on "
				. "registraiton forms.  This will create an alternate submit "
				. "button to poulate the form with fake data.  Do NOT use in "
				. "production!",
			"#size" => reg_form::FORM_TEXT_SIZE,
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
	static function settings_form_validate(&$form_id, &$data) {

		//
		// If a path was entered, make sure it is a valid alias or
		// a valid node.
		//
		if (!empty($data["conduct_path"])) {
			if (!drupal_lookup_path("source", $data["conduct_path"])) {
				$results = explode("/", $data["conduct_path"]);
				$nid = $results[1];
				if (empty($nid) || !node_load($nid)) {
					form_set_error("conduct_path", 
						"Invalid path entered for Standards of Conduct");
				}
			}
		}

		//form_set_error("fake_cc", "test2");
		//print_r($data);

	} // End of form_validate()


	/**
	* This function is called after our form has been successfully validated.
	*
	* It should make any necessary changes to the database.  At the 
	* conclusion of this funciton, the user is redirected back to the 
	* form page.
	*/
	static function settings_form_submit($form_id, $data) {

		variable_set(reg_form::FORM_ADMIN_FAKE_CC, $data["no_production"]["fake_cc"]);
		variable_set(reg_form::FORM_ADMIN_FAKE_DATA, $data["no_production"]["fake_data"]);
		variable_set(reg_form::FORM_ADMIN_CONDUCT_PATH, $data["conduct_path"]);
		drupal_set_message("Settings updated");

	}


	/**
	* Our "main" page for the admin.
	*/
	static function main() {

		$retval = "";

		$retval = "<h2>Quick Links:</h2>";

		$retval .= "<ul>\n"
			. "<li>" . l(t("Recent Members"), "admin/reg/members") 
				. "</li>\n"
			. "<li>" . l(t("Search Members"), "admin/reg/members/search") 
				. "</li>\n"
			. "<li>" . l(t("Add a new member"), "admin/reg/members/add") 
				. "</li>\n"
			."</ul>\n"
			;

		return($retval);

	} // End of main()


} // End of reg_admin class

