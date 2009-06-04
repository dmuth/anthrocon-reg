<?php

class Reg_Util_Print {


	function __construct(&$reg) {
		$this->reg = $reg;
	} // End of __construct()


	/**
	* Get all print jobs with a specific status.
	*
	* @param string $status A specific status to search for.
	*
	* @return array An array of all matching rows from the 
	*	reg_print_jobs table.
	*/
	function getAllJobs($status = "", $printer = "") {

		$retval = array();

		$query = "SELECT * "
			. "FROM {reg_print_jobs} "
			;
		$query_where = array();
		$query_args = array();

		if (!empty($status)) {
			$query_where[] = "status='%s' ";
			$query_args[] = $status;
		}

		if (!empty($printer)) {
			$query_where[] = "printer='%s' ";
			$query_args[] = $printer;
		}

		if (!empty($query_where)) {
			$query .= "WHERE " . join("AND ", $query_where);
		}

		$cursor = db_query($query, $query_args);
		while ($row = db_fetch_array($cursor)) {
			$retval[] = $row;
		}

		return($retval);

	} // End of getAllJobs()



	/**
	* Add a print job for a specific badge.
	*
	* @param integer $reg_id The ID from the reg table.
	*
	* @param string $printer What printer to send the job to?
	*
	* @return integer The ID from the reg_print_jobs table.
	*/
	function addJob($reg_id, $printer = "default") {

		$retval = "";

		$query = "INSERT INTO {reg_print_jobs} "
			. "(reg_id, printer, status) "
			. "VALUES "
			. "('%s', '%s', '%s') "
			;
		$query_args = array($reg_id, $printer, "new");
		db_query($query, $query_args);
		$retval = $this->reg->get_insert_id();

		return($retval);

	} // End of addJob()


	/**
	* Retrieve information on a specific job, and the badge it prints.
	*/
	function getJob($id) {

		$retval = "";

		$query = "SELECT "
			. "jobs.*, "
			. "reg.badge_name, reg.year, reg.badge_num, "
			. "reg_type.member_type AS member_type "
			. "FROM {reg_print_jobs} AS jobs "
			. "JOIN {reg} AS reg ON jobs.reg_id = reg.id "
			. "JOIN {reg_type} AS reg_type ON reg_type.id = reg.reg_type_id "
			. "WHERE "
			. "jobs.id='%s' "
			;
		$query_args = array($id);
		$cursor = db_query($query, $query_args);
		$retval = db_fetch_array($cursor);
		
		return($retval);

	} // End of getJob()


	/**
	* Fetch the next job to print and mark it as printing.
	*
	* @param string $printer The printer to search for pending jobs.
	*
	* @return array Array of job data, including badge information.
	*/
	function getNextJob($printer = "default") {

		$retval = "";

		$query = "SELECT "
			. "jobs.*, "
			. "reg.badge_name, reg.year, reg.badge_num, "
			. "reg_type.member_type AS member_type "
			. "FROM {reg_print_jobs} AS jobs "
			. "JOIN {reg} AS reg ON jobs.reg_id = reg.id "
			. "JOIN {reg_type} AS reg_type ON reg_type.id = reg.reg_type_id "
			. "WHERE "
			. "printer='%s' "
			. "AND status='new' "
			. "ORDER BY jobs.id ASC "
			. "LIMIT 1 "
			;
		$query_args = array($printer);
		$cursor = db_query($query, $query_args);
		$retval = db_fetch_array($cursor);
		$id = $retval["id"];

		//
		// Now that we have that record, mark it as "printing".
		//
		$query = "UPDATE {reg_print_jobs} "
			. "SET "
			. "status='printing' "
			. "WHERE "
			. "id='%s' "
			;
		$query_args = array($id);
		db_query($query, $query_args);

		return($retval);

	} // End of getNextJob()


	/**
	* Update an existing job.
	*
	* @param integer $id The job ID.
	*
	* @param string $status The status to set the job to.
	*/
	function updateJob($id, $status) {

		$query = "UPDATE {reg_print_jobs} "
			. "SET "
			. "status='%s' "
			. "WHERE "
			. "id='%s' "
			;
		$query_args = array($status, $id);
		db_query($query, $query_args);

	} // End of updateJob()


} // End of Reg_Util_Print class

