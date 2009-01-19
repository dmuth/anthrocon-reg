<?php

/**
* This class holds functions related to our search functionality.
*
* @todo Split this up into seperate classes for each report.
*
*/
class reg_admin_stats extends reg {


	function __construct(&$message, &$fake, &$log, &$search) {
		$this->admin_member = $admin_member;
		$this->search = $search;
		parent::__construct($message, $fake, $log);
	}


	/**
	* Get badge type breakdown.
	*/
	function get_stats_badge() {

		$message = t("Audit log: Stats: Viewed badge breakdown report.");
		$this->log->log($message);

		$year = $this->get_constant("year");

		$retval .= t("<h2>Badge Breakdown for Convention Year %year</h2>",
			array(
				"%year" => $year,
			));

		$data_reg = $this->get_badge_data($year);
		$retval .= $this->get_badge_report($data_reg);

		return($retval);

	} // End of get_stats_registration()


	/**
	* Get a breakdown of registration activity.
	*/
	function get_stats_reg() {

		$message = t("Audit log: Stats: Viewed registration activity report.");
		$this->log->log($message);

		$year = $this->get_constant("year");

		$retval .= t("<h2>Registration Activity for Convention Year %year</h2>",
			array(
				"%year" => $year,
			));

		$data = $this->get_reg_data($year);
		$retval .= $this->get_reg_report($data);

		return($retval);

	} // End of get_stats_reg()


	/**
	* Get statistics for revenue brought in.
	*/
	function get_stats_revenue() {

		$message = t("Audit log: Stats: Viewed revenue breakdown report.");
		$this->log->log($message);

		$year = $this->get_constant("year");

		$retval .= t("<h2>Revenue Breakdown for Convention Year %year</h2>",
			array(
				"%year" => $year,
			));

		$data_rev = $this->get_rev_data($year);
		$retval .= $this->get_rev_report($data_rev);

		return($retval);

	} // End of get_stats_revenue()


	/**
	* This function runs queries to get data on current registrations.
	*
	* @param integer $year The convention year
	*
	* @return array multi-dimensional array of different membership types and 
	*	the statuses for those membership types.
	*/
	function get_badge_data($year) {

		$retval = array();

		$total_status = array();
		$total_status["total"] = 0;

		$types = $this->get_types();

		//
		// Loop through our types (rows in the report)
		//
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

			//
			// Loop through our statuses (columns in the report)
			// 
			$total = 0;
			$cursor = db_query($query, $query_args);
			while ($row = db_fetch_array($cursor)) {

				$row["type"] = $type_name;
				$status = $row["status"];
				$retval[$type_id][$status] = $row;

				//
				// Update the type total and store it here so we won't have
				// empty totals for otherwise empty statuses.
				//
				$total += $row["num"];
				$retval[$type_id]["total"] = $total;

				if (empty($total_status[$status]["num"])) {
					$total_status[$status]["num"] = 0;
				}

				//
				// Update the total for this status.
				//
				$total_status[$status]["num"] += $row["num"];

				//
				// Update the grand total
				//
				$total_status["total"] += $row["num"];

			}


		}

		//
		// Add the totals onto the end.
		//
		$retval[t("Totals")] = $total_status;

		//print "<pre>"; print_r($retval); print "</pre>"; // Debugging

		return($retval);

	} // End of get_badge_data()


	/**
	* This function creates a report based on our data.
	*
	* @param array $data Our data structure returned from get_badge_data().
	*
	* @return string HTML code for the report.
	*/
	function get_badge_report($data) {

		$retval = "";

		$statuses = $this->get_statuses();
		$types = $this->get_types();

		//
		// Create our header, which corresponds to the statuses.
		//
		$header = $statuses;
		array_unshift($header, t("Badge Type"));
		$header[] = t("Total");

		$rows = array();

		//
		// Loop through our badge types and statuses, and get 
		// table cells for each.
		//
		foreach ($data as $key => $value) {

			$type_id = $key;

			$row = array();

			//
			// Get the name of the row, which can be a badge type or "totals".
			//
			if (!empty($types[$type_id])) {
				$row[] = $types[$type_id];
			} else {
				$row[] = $key;
			}

			foreach ($statuses as $key2 => $value2) {
				$status = $value2;
				$status_id = $key2;

				$num = 0;
				if (!empty($value[$status]["num"])) {
					$num = $value[$status]["num"];
				}


				if ($num != 0 && $type_id != t("Totals")) {

					//
					// Create searh criteria and append it to the URL to the 
					// search page.
					//
					$search = array();
					$search["reg_status_id"] = $status_id;
					$search["reg_type_id"] = $type_id;

					$url = "admin/reg/members/search/";
					$get_data = $this->array_to_get_data($search);
					$url .= $get_data;

					$link = l($num, $url);
				} else {
					$link = $num;
				}

				$row[] = array("data" => $link, "align" => "right");
			}

			$row[] = array("data" => $value["total"], "align" => "right");

			$rows[] = $row;

		}

		$retval .= theme("table", $header, $rows);

		return($retval);

	} // End of get_badge_report()


	/**
	* Get our registration activity.
	*/
	function get_reg_data($year) {

		$retval = array();

		$query = "SELECT "
			. "COUNT(*) AS num, "
			. "FROM_UNIXTIME(created, '%Y-%m') AS date, "
			. "FROM_UNIXTIME(created, '%M, %Y') AS date_name "
			. "FROM {reg} "
			. "WHERE "
			. "year='%s' "
			. "GROUP BY date "
			. "ORDER BY date";
		$query_args = array($year);
		$cursor = db_query($query, $query_args);

		while ($row = db_fetch_array($cursor)) {
			$key = $row["date"];
			$retval[$key] = $row;
		}

		return($retval);

	} // End of get_reg_data()


	/**
	* Get our registration activity report.
	*/
	function get_reg_report(&$data) {

		$retval = "";

		//
		// Create our header, which corresponds to the statuses.
		//
		$header = array();
		$header[] = t("Date");
		$header[] = t("# Purchases");

		$rows = array();

		foreach ($data as $value) {
			$row = array();
			$row[] = array("data" => $value["date_name"]);
			$row[] = array(
				"data" => t("!num badges", 
					array("!num" => $value["num"])),
				"align" => "right");
			$rows[] = $row;
		}

		$retval .= theme("table", $header, $rows);

		$retval .= t("Note: This table only reflects purchases, so the numbers "
			. "may be larger than actual attendance.  "
			. "If you want to see refunds, please check the !link.",
			array(
				"!link" => l(t("Badge Breakdown Report"), 
					"admin/reg/stats/badge")
			)
			);

		return($retval);

	} // End of get_reg_report()


	/**
	* Get revenue data for the given convention year.
	*/
	function get_rev_data($year) {

		$retval = array();
		$total = array();

		//
		// Get our starting and ending years.for transactions for this 
		// convention year, grouped by months.
		// Under normal circumstances, say for a 2009 convention, we'll
		// have transactions in 2008 and 2009, but it doesn't hurt to
		// make this a little more generic.
		//
		$years = $this->get_rev_years($year);

		//
		// Loop through all of our years and print stats for both.
		//
		for ($i = $years["start"]; $i <= $years["end"]; $i++) {

			$retval[$i] = array();
			$year_total = array();

			$query = "SELECT "
				. "COUNT(*) AS num, "
				. "MONTH(FROM_UNIXTIME(reg_trans.date)) AS month, "
				. "MONTHNAME(FROM_UNIXTIME(reg_trans.date)) AS month_name, "
				. "SUM(reg_trans.badge_cost) AS badge_cost, "
				. "SUM(reg_trans.donation) AS donation, "
				. "SUM(reg_trans.total_cost) AS total_cost "
				. "FROM {reg_trans} "
				. "JOIN {reg} ON reg_trans.reg_id = reg.id "
				. "WHERE "
				. "reg.year = '%s' "
				. "AND YEAR(FROM_UNIXTIME(reg_trans.date)) = '%s' "
				. "GROUP BY month "
				. "ORDER BY month "
				;
			$query_args = array($year, $i);
			$cursor = db_query($query, $query_args);

			while ($row = db_fetch_array($cursor)) {

				$row["date"] = $row["month_name"] . ", " . $i;
				$month = $row["month"];
				$retval[$i][$month] = $row;

				//
				// Update yearly totals
				//
				$year_total["num"] += $row["num"];
				$year_total["badge_cost"] += $row["badge_cost"];
				$year_total["donation"] += $row["donation"];
				$year_total["total_cost"] += $row["total_cost"];

				//
				// Update grand totals
				//
				$total["num"] += $row["num"];
				$total["badge_cost"] += $row["badge_cost"];
				$total["donation"] += $row["donation"];
				$total["total_cost"] += $row["total_cost"];

			}

			$year_total["date"] = t("%year% totals", array("%year%" => $i));
			$retval[$i]["total"] = $year_total;

		}

		//
		// Put our totals at the end so they show up at the end of
		// the final report.
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

		//
		// Create our header, which corresponds to the statuses.
		//
		$header = array();
		$header[] = t("Date");
		$header[] = t("# Transactions");
		$header[] = t("Badge Cost");
		$header[] = t("Donation");
		$header[] = t("Total Cost");

		$rows = array();

		//
		// Loop through year
		//
		foreach ($data as $key => $value) {

			//
			// Loop through months in that year
			//
			foreach ($value as $key2 => $value2) {

				$row = array();

				//
				// If not the grand total, print up the line.
				//
				if ($key != "total") {
					$row[] = array("data" => $value2["date"], "align" => "right", 
						"header" => true);
					$row[] = array("data" => $value2["num"], "align" => "right");
					$row[] = array(
						"data" => "$" . number_format($value2["badge_cost"]), 
						"align" => "right");
					$row[] = array(
						"data" => "$" . number_format($value2["donation"]), 
						"align" => "right");
					$row[] = array(
						"data" => "$" . number_format($value2["total_cost"]), 
						"align" => "right");
					$rows[] = $row;

				}

			}


			$row = array();

			//
			// If this row is the grand total, then print it differently.
			//
			if ($key == "total") {

				$row[] = array("data" => t("Grand Total"), "align" => "right", 
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

			} else {
				//
				// This is the end of a year, print an empty line for spacing.
				//
				$row[] = array("data" => "", "colspan" => 5);

			}

			$rows[] = $row;

		}

		$retval .= theme("table", $header, $rows);

		$retval .= "<p/>" 
			. t("<b>Note:</b> A single membership can have more than one "
			. "transaction associated with it.");

		return($retval);

	} // End of get_rev_report()

} // End of class reg_admin_stats

