<?php

/**
* This class holds functions related to our search functionality.
*/
class reg_admin_stats extends reg {


	function __construct(&$message, &$fake, &$log) {
		$this->admin_member = $admin_member;
		parent::__construct($message, $fake, $log);
	}


	/**
	* Our statistics page.
	*/
	function get_stats() {

		$year = $this->get_constant("year");

		$retval .= t("<h2>Registration Statistics for Convention Year %year</h2>",
			array(
				"%year" => $year,
			));

		$data_reg = $this->get_reg_data($year);
		$retval .= $this->get_reg_report($data_reg);

		$data_rev = $this->get_rev_data($year);
		$retval .= $this->get_rev_report($data_rev);

		return($retval);

	} // End of get_stats()


	/**
	* This function runs queries to get data on current registrations.
	*
	* @param integer $year The convention year
	*
	* @return array multi-dimensional array of different membership types and 
	*	the statuses for those membership types.
	*/
	function get_reg_data($year) {

		$retval = array();

		$types = $this->get_types();

		foreach ($types as $key => $value) {

			$type_id = $key;
			$type_name = $value;

			$query = "SELECT "
				. "COUNT(reg.id) AS num,  "
				. "reg_status.status "
				. "FROM {reg} "
				. "JOIN {reg_status} ON reg.reg_status_id = reg_status.id "
				. "WHERE "
				. "year='%s' "
				. "AND reg_type_id='%s' "
				. "GROUP BY reg_status.id "
				;

			$query_args = array(
				$year, $type_id
				);

			$cursor = db_query($query, $query_args);
			while ($row = db_fetch_array($cursor)) {
				$row["type"] = $type_name;
				$status = $row["status"];
				$retval[$type_name][$status] = $row;
			}

		}

		return($retval);

	} // End of get_reg_data()


	/**
	* This function creates a report based on our data.
	*
	* @param array $data Our data structure returned from get_reg_data().
	*
	* @return string HTML code for the report.
	*/
	function get_reg_report($data) {

		$retval = "";

		$retval .= t("<h3>Badge Breakdown</h3>");

		$statuses = $this->get_statuses();

		//
		// Create our header, which corresponds to the statuses.
		//
		$header = $statuses;
		array_unshift($header, t("Badge Type"));

		$rows = array();

		//
		// Loop through our badge types and statuses, and get 
		// table cells for each.
		//
		foreach ($data as $key => $value) {

			$row = array();

			$row[] = $key;

			foreach ($statuses as $key2 => $value2) {
				$status = $value2;

				$num = 0;
				if (!empty($value[$status]["num"])) {
					$num = $value[$status]["num"];
				}
				$row[] = array("data" => $num, "align" => "right");
			}

			$rows[] = $row;

		}

		$retval .= theme("table", $header, $rows);

		return($retval);

	} // End of get_reg_report()


	/**
	* Get revenue data for the given convention year.
	*/
	function get_rev_data($year) {

		$retval = array();

		//
		// Get our starting and ending years.
		//
		$years = $this->get_rev_years($year);

		$total = array();
		$total["year"] = t("Totals");

		//
		// Loop through all of our years and print stats for both.
		//
		for ($i = $years["start"]; $i <= $years["end"]; $i++) {

			$query = "SELECT "
				. "COUNT(*) AS num, "
				. "SUM(reg_trans.badge_cost) AS badge_cost, "
				. "SUM(reg_trans.donation) AS donation, "
				. "SUM(reg_trans.total_cost) AS total_cost "
				. "FROM {reg_trans} "
				. "JOIN {reg} ON reg_trans.reg_id = reg.id "
				. "WHERE "
				. "reg.year = '%s' "
				. "AND YEAR(FROM_UNIXTIME(reg_trans.date)) = '%s' "
				;
			$query_args = array($year, $i);
			$cursor = db_query($query, $query_args);
			$row = db_fetch_array($cursor);
			$row["year"] = $i;

			$retval[$i] = $row;

			$total["num"] += $row["num"];
			$total["badge_cost"] += $row["badge_cost"];
			$total["donation"] += $row["donation"];
			$total["total_cost"] += $row["total_cost"];

		}

		//
		// Add in our totals.
		//
		$retval["total"] = $total;

		return($retval);

	} // End of get_rev_data()


	/**
	* Get the starting and ending year for a specific convention year.
	* For example, if the convention is in 2009, payments may have
	* been accepted in 2008, and we need to know this for tax purposes.
	*
	* @return array Associative array of starting and ending years.
	*/
	protected function get_rev_years($year) {

		$retval = array();

		//
		// Get starting year for this con year.
		//
		$query = "SELECT "
			. "YEAR(FROM_UNIXTIME(date)) AS year "
			. "FROM "
			. "{reg_trans} "
			. "JOIN {reg} ON reg_trans.reg_id = reg.id "
			. "WHERE "
			. "reg.year = '%s' "
			. "ORDER BY reg_trans.date "
			. "LIMIT 1";
			;
		//$year += 5; // Debugging

		$query_args = array($year);
		$cursor = db_query($query, $query_args);
		$row = db_fetch_array($cursor);

		$retval["start"] = $row["year"];

		//
		// Reverse the order to get the ending year
		//
		$query = "SELECT "
			. "YEAR(FROM_UNIXTIME(date)) AS year "
			. "FROM "
			. "{reg_trans} "
			. "JOIN {reg} ON reg_trans.reg_id = reg.id "
			. "WHERE "
			. "reg.year = '%s' "
			. "ORDER BY reg_trans.date DESC "
			. "LIMIT 1";
			;
		$cursor = db_query($query, $query_args);
		$row = db_fetch_array($cursor);

		$retval["end"] = $row["year"];

		return($retval);

	} // End of get_rev_years()


	/**
	* Format our revenue report.
	*
	* @param array $data Associative array of revenue breakdowns.
	*/
	function get_rev_report(&$data) {

		$retval = "";

		$retval .= t("<h3>Revenue Breakdown</h3>");


		//
		// Create our header, which corresponds to the statuses.
		//
		$header = array();
		$header[] = t("Calendar Year");
		$header[] = t("# Transactions");
		$header[] = t("Badge Cost");
		$header[] = t("Donation");
		$header[] = t("Total Cost");

		$rows = array();

		foreach ($data as $key => $value) {

			$row = array();
			$row[] = array("data" => $value["year"], "align" => "right", 
				"header" => true);
			$row[] = array("data" => $value["num"], "align" => "right");
			$row[] = array(
				"data" => "$" . number_format($value["badge_cost"]), 
				"align" => "right");
			$row[] = array(
				"data" => "$" . number_format($value["donation"]), 
				"align" => "right");
			$row[] = array(
				"data" => "$" . number_format($value["total_cost"]), 
				"align" => "right");

			$rows[] = $row;

		}

		$retval .= theme("table", $header, $rows);

		$retval .= t("Note that a single membership can have more than one transaction.");

		return($retval);

	} // End of get_rev_report()

} // End of class reg_admin_stats

