<?php
/**
* This class contains functions to display the various duplicate 
*	registration reports.
*/
class Reg_Util_DuplicateDisplay {


	function __construct(&$reg, &$util, &$log) {
		$this->reg = $reg;
		$this->util = $util;
		$this->log = $log;
	}


	/**
	* Our main function, which renders our form.
	*/
	function go() {

        $retval = "";

		//
		// Grab the current year, then get a list of all years.
		//
		$year = arg(4);
		$url = "admin/reg/utils/duplicate";
		$retval .= $this->reg->getYearHtml($url, $year);

		//
		// If a year wasn't passed in, stop here.
		//
		if (empty($year)) {
			return($retval);
		}

		//$_SESSION["reg"]["util"]["duplicate"] = $year; // Debugging

		//
		// If our session variable is set, run the report, get the results,
		// and unset the variable.
		//
		if (!empty($_SESSION["reg"]["util"]["duplicate"])) {

			$year = $_SESSION["reg"]["util"]["duplicate"];

			$retval .= $this->getResults($year);
			unset($_SESSION["reg"]["util"]["duplicate"]);

			$message = t("Audit log: Viewed duplicate membership report.");
			$this->log->log($message);

		}

		$retval .= drupal_get_form("reg_admin_utils_duplicate_form");

		return($retval);

	} // End of go()


	/**
	* This function generates our form which asks if we really want to run 
	*	the duplicate member report report.
	*
	* @return array Array of form elements.
	*/
	function getForm() {

		$retval = array();

		$year = arg(4);

		$retval["description"] = array(
			"#type" => "item",
			"#value" => t("Do you want to run a search for all possible "
					. "duplicate memberships for the convetion year %year?",
				array(
					"%year" => $year,
				)),
			);

		$retval["year"] = array(
			"#type" => "hidden",
			"#value" => $year,
			);

		$retval["submit"] = array(
			"#type" => "submit",
			"#value" => t("Yes, run the search!"),
			);

		return($retval);

	} // End of getForm()


	/**
	* This is called on form submission.
	*/
	function getFormSubmit(&$data) {

		$retval = "";

		$year = $data["year"];

		$_SESSION["reg"]["util"]["duplicate"] = $year;

		$url = "admin/reg/utils/duplicate/" . $year;
        $this->reg->goto_url($url);

		return($retval);

	} // End of getFormSubmit()


	/**
	* A (slighty ghetto) wrapper to generate a collapsible fieldset outside 
	*	of a form.
	*
	* @param string $title The name of the fieldset.
	*
	* @param string $content The content to display inside of the fieldset.
	*
	* @return string HTML code for a collapsible fieldset.
	*/
	function getFieldset($title, $content) {

		$retval = "";

		drupal_add_js("misc/collapse.js");

		//
		// We have to add "fieldset-wrapper" around our content.
		//
		$content = "<div class=\"fieldset-wrapper\">"
			. $content
			. "</div>"
			;

		//
		// Now let's play with theme_fieldset().
		//
		$retval .= 
			"<div>"
			. theme("fieldset", 
				array("#title" => check_plain($title),
					"#children" => $content,
					"#collapsible" => true,
					"#collapsed" => true,
				))
			."</div>"
			;

		return($retval);

	} // End of getFieldset()


	/**
	* This function turns an array of rows into a table.
	*
	* @param array $rows An array of all matching rows
	*
	* @param string $field The name of the field we are matching on
	*
	* @return string HTML code of a table.
	*/
	function getTable(&$rows, $field) {

		$retval = "";

		$header = array(
			array("data" => t("Badge Number")),
			array("data" => t("Badge Name")),
			array("data" => t("Real Name")),
			array("data" => t("Matching on field '%field'", array("%field" => $field))),
			);

		$rows_table = array();

		//
		// Loop through our results, putting each of them into a new array
		// with different data, links, etc.
		//
		foreach ($rows as $key => $value) {

			$id = $value["id"];                
			$link = "admin/reg/members/view/" . $id . "/view";

			$badge_num = $value["year"] . "-" 
				. $this->reg->format_badge_num($value["badge_num"]);
			$badge_name = $value["badge_name"];
			$name = $value["first"] . " " . $value["last"];

			$row = array(
				array("data" => l($badge_num, $link)),
				array("data" => l($badge_name, $link)),
				array("data" => l($name, $link)),
				array("data" => check_plain($value["match"])),
				);
			$rows_table[] = $row;

		}

		//
		// If there were no matches, add a row into the table noting this.
		//
		if (empty($rows_table)) {
			$row = array(
				array("data" => t("No matching members found. (Yay?)"), 
					"colspan" => 4)
				);
			$rows_table[] = $row;
		}

		$retval = theme("table", $header, $rows_table);

		return($retval);

	} // End of getTable()


	/**
	* Run our searches
	*
	* @param intger $year The convention year to search
	*
	* @return string A report of possible duplicate memberships.
	*/
	function getResults($year) {

		$retval = "";

		//
		// Run searches for each of our search criteria, put them into a
		// table, and glue the table onto our return value.
		//
		$rows = $this->util->GetLastNames($year);
		$title = t("!count Possible Duplicate Memberships By Matching Last Names",
			array("!count" => count($rows))
			);
		$content = $this->getTable($rows, t("Last Name"));
		$retval .= $this->getFieldset($title, $content);

		$rows = $this->util->getPhoneNumbers($year);
		$title = t("!count Possible Duplicate Memberships By Matching Phone Numbers",
			array("!count" => count($rows))
			);
		$content = $this->getTable($rows, t("Phone Number"));
		$retval .= $this->getFieldset($title, $content);

		$rows = $this->util->getEmailAddresses($year);
		$title = t("!count Possible Duplicate Memberships By Matching Email Addresses",
			array("!count" => count($rows))
			);
		$content = $this->getTable($rows, t("Email Address"));
		$retval .= $this->getFieldset($title, $content);

		$rows = $this->util->getAddresses($year);
		$title = t("!count Possible Duplicate Memberships By Matching Addresses",
			array("!count" => count($rows))
			);
		$content = $this->getTable($rows, t("Address Line #1"));
		$retval .= $this->getFieldset($title, $content);

		$rows = $this->util->getBadgeNames($year);
		$title = t("!count Possible Duplicate Memberships By Matching Badge Names",
			array("!count" => count($rows))
			);
		$content = $this->getTable($rows, t("Badge Name"));
		$retval .= $this->getFieldset($title, $content);

		return($retval);

	} // End of getResuts()

} // End of Reg_Util_DuplicateDisplay class

