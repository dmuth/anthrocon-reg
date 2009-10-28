<?php
/**
* This class is used to create the user-facing form for our printing client.
*/
class Reg_Util_PrintClient {


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
		// Word freaks out over self-signed SSL certs, and even hangs
		// in some cases.  So let's convert an HTTPS path to plain 
		// old HTTP for now.
		//
		// Maybe in the future I could copy the doc file locally and
		// open that file instead...
		//
		$path = ereg_replace("^https://", "http://", $path);

		//
		// Do we want to only test non-MSIE-specific stuff?
		//
		$js_nomsie = "jQuery.fn.printerWidget.debugNoMSIE = false;";
		if ($this->isNoMSIE()) {
			$js_nomsie = "jQuery.fn.printerWidget.debugNoMSIE = true;";
		}

		$base_url = $GLOBALS["base_url"];

		//
		// If we're in SSL, adjust our base URL.
		//
		$port = getenv("SERVER_PORT");
		if ($port == 443) {
			$base_url = str_replace("http://", "https://", $base_url);
		}

		$js = "$(document).ready(function() {\n"
			. "\tvar reg_base_path = '${path}';\n"
			. "\tvar reg_base_url = '${base_url}';\n"
			. "\t${js_nomsie}\n"
			. "\tjQuery.fn.printerWidget(reg_base_path, reg_base_url);\n"
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
		// Don't print this when we're already testing.
		//
		if (!$this->isNoMSIE()) {
			$url = "admin/reg/utils/print/client/nomsie";
			$link = l(t("Click to test in another browser with fake badges"), $url);
			$retval["debug"] = array(
				"#type" => "fieldset",
				"#title" => t("Debugging"),
				"#collapsible" => true,
				"#collapsed" => true,
				"#theme" => "reg_theme",
				);

			$retval["debug"]["no_msie"] = array(
				"#type" => "item",
				"#title" => t("Test in a non-MSIE browser?"),
				"#value" => $link,
				);
		}

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


	/**
	* Are we in the "test with non-MSIE stuff" mode?
	*
	* @return boolean True if we're in non-MSIE mode.  False otherwise.
	*/
	function isNoMSIE() {

		$uri = request_uri();
		if (strstr($uri, "print/client/nomsie")) {
			return(true);
		}

		return(false);

	} // End of isNoMSIE()


} // End of Reg_Util_PrintClient class

