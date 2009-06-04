<?php
/**
* This class is used to create the user-facing form for our printing client.
*/
class Reg_Util_PrintingClient {


	function __construct() {
	}


	/**
	* Get our page.
	*
	* @return string HTML code for the page.
	*/
	function getPage() {

		$retval = "";

		//
		// Load our printer widget
		//
		$path = drupal_get_path("module", "reg");
		drupal_add_js($path . "/js/printerWidget.js", "module");

		//
		// Get the path to our module, set it in Javascript, then 
		// call the printer widget jQuery plugin.
		//
		$path = $GLOBALS["base_root"] . base_path() 
			. drupal_get_path("module", "reg");

		//
		// If we're in SSL, fix the path.
		//
		// Okay, this is commented out for awhile, since Word freaks out 
		// over self-signed SSL certificates.
		//
		//$port = getenv("SERVER_PORT");
		//if ($port == 443) {
		//	$path = str_replace("http://", "https://", $path);
		//}

		$js = "$(document).ready(function() {\n"
			. "\tvar reg_base_path = '${path}';\n"
			. "\tjQuery.fn.printerWidget(reg_base_path);\n"
			. "});\n"
			;

		drupal_add_js($js, "inline");

		$retval .= drupal_get_form("reg_admin_utils_print_client_form");

		return($retval);

	} // End of getPage()



	/**
	* Get our form for printing.
	*
	* @return array An array of form elements to be processed by Drupal.
	*/
	function getForm() {

		$retval = array(
			);

		//
		// Our settings to control printing.
		//
		$retval["settings"] = array(
			"#title" => t("Settings"),
			"#type" => "fieldset",
			"#theme" => "reg_theme",
			);

		$retval["settings"]["type"] = array(
			"#title" => t("Type of badges to print"),
			"#type" => "select",
			"#options" => array(
				"default" => t("Default (usually adult badges)"),
				"minor" => t("Minor badges"),
				)
			);

		$retval["settings"]["interval"] = array(
			"#title" => t("Seconds between checks"),
			"#type" => "textfield",
			"#default_value" => 5,
			"#size" => 2,
			);

		//
		// This will be updated by Javascript.
		//
		$retval["status"] = array(
			"#title" => t("Status"),
			"#type" => "fieldset",
			"#theme" => "reg_theme",
			);

		$retval["status"]["current"] = array(
			"#title" => t("Current Status"),
			"#type" => "textfield",
			"#disabled" => true,
			);

		//
		// Starting or stopping the print client.
		// Note that the button will be enabled by Javascript, because
		// Javascript will attached to the button that returns false so the
		// form can never be submitted.
		//
		$retval["control"] = array(
			"#title" => t("Printing Control"),
			"#type" => "fieldset",
			"#theme" => "reg_theme",
			);

		$retval["control"]["button"] = array(
			"#type" => "button",
			"#value" => "Start Printing Badges",
			"#disabled" => true,
			);

		return($retval);

	} // End of getForm()


} // End of Reg_Util_PrintingClient class

