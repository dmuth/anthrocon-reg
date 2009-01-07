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


/*
TODO:
    - Amount of revenue for current convention year
        - Break down by calendar year of purchase (get_rev_data())
			- Check transactions table for data (get_rev_report())

*/

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

		$statuses = $this->get_statuses();

		$header = $statuses;
		array_unshift($header, t("Badge Type"));

		$rows = array();

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

		$retval = theme("table", $header, $rows);

		return($retval);

	} // End of get_reg_report()


} // End of class reg_admin_stats

