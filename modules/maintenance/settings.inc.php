<?php
/**
* This file holds code for our settings form.
*
* @author Douglas Muth <http://www.dmuth.org/>
*
*/


/**
* Our configuration form
*/
function reg_maint_settings() {

	$retval = "";

	$retval .= drupal_get_form("reg_maint_settings_form");

	return($retval);

} // End of reg_maint_settings()


/**
* The actual form. :-)
*/
function reg_maint_settings_form() {

	$retval = array();

	$settings = array(
		"#type" => "fieldset",
		"#title" => t("Settings"),
	);

	$settings["enabled"] = array(
		"#type" => "checkbox",
		"#title" => t("Enabled?"),
		"#description" => t("If checked, <b>registration will be disabled!</b> This is for doing maintenance and such."),
		"#default_value" => variable_get("reg_maint_enabled", false),
		);

	$settings["message"] = array(
		"#type" => "textarea",
		"#title" => "Message",
		"#description" => t("Message to display to users going to the registration pages."),
		"#default_value" => variable_get("reg_maint_message", ""),
		);

	$settings["submit"] = array(
		"#type" => "submit",
		"#value" => "Save Changes",
		);

	$retval["settings"] = $settings;

	return($retval);

} // End of reg_maint_settings_form()


function reg_maint_settings_form_validate($form, $form_state) {
}


/**
* Our form submission handler.
*/
function reg_maint_settings_form_submit($form, $form_state) {

        $values = $form_state["values"];

		$enabled = $values["enabled"];
		$message = $values["message"];

		variable_set("reg_maint_enabled", $enabled);
		variable_set("reg_maint_message", $message);

        drupal_set_message("Settings updated!");

} // End of ddt_settings_form_submit()




