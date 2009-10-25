<?php

class Reg_Util_UnusedBadgeNumsDisplay {


	function __construct(&$reg, &$util, &$log) {
		$this->reg = $reg;
		$this->util = $util;
		$this->log = $log;
	}


	/**
	* Our main function, which simply renders our form.
	*/
	function go() {

        $retval = "";

		//
		// Grab the current year, then get a list of all years.
		//
		$year = arg(3);
		$url = "admin/reg/utils";
		$retval .= $this->reg->getYearHtml($url, $year);

		//
		// If a year wasn't passed in, stop here.
		//
		if (empty($year)) {
			return($retval);
		}

		//$_SESSION["reg"]["util"]["unused_badge_nums"] = $year; // Debugging
		//
		// If our session variable is set, run the report, get the results,
		// and unset the variable.
		//
		if (!empty($_SESSION["reg"]["util"]["unused_badge_nums"])) {

			$message = t("Audit log: Viewed unused badge number report.");
			$this->log->log($message);

			$retval .= $this->getResults($year);
			unset($_SESSION["reg"]["util"]["unused_badge_nums"]);
		}

		$retval .= drupal_get_form("reg_admin_utils_unused_badge_nums_form");

		return($retval);

	} // End of go()


	/**
	* This function generates our form which asks if we really want to run 
	*	the unused badge number report.
	*
	* @return array Array of form elements.
	*/
	function getForm() {

		$retval = array();

		$year = arg(3);

		$retval["description"] = array(
			"#type" => "item",
			"#value" => t("Do you want to run a search for all unused badge "
				. "numbers for the convention year %year?",
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

		$_SESSION["reg"]["util"]["unused_badge_nums"] = $year;

		$url = "admin/reg/utils/" . $year;
        $this->reg->goto_url($url);

		return($retval);

	} // End of getFormSubmit()


	/**
	* Calculate what badge numbers have not been assigned.
	*
	* @param intger $year The convention year to search
	*
	* @return string A report of unassigned badge numbers
	*/
	function getResults($year) {

		$retval = "";

		$nums = $this->util->getBadgeNums($year);

		$retval .= t("The following badge numbers are not currently assigned:<p/>");
		$retval .= join(", ", $nums);
		$retval .= "<p/>\n";

		$max = $this->util->getMaxBadgeNum($year);
		$retval .= t("Highest Badge Number: %num", array(
			"%num" => $max,
			));

		return($retval);

	} // End of getResuts()


} // End of Reg_Util_UnusedBadgeNumsDisplay class

