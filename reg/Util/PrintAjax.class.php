<?php
/**
* This class is called via AJAX from the printer client.
*/
class Reg_Util_PrintAjax {

	function __construct(&$reg, &$util, &$log) {
		$this->reg = $reg;
		$this->util = $util;
		$this->log = $log;
	}


	/**
	* Fetch the next available job for a specific printer.
	*
	* @param string $printer The printer we want a job for.
	*
	* @return string A string with key=value& pairs.
	*/
	function fetch($printer = "") {

		$retval = "";

		//
		// Fetch our next job
		//
		$job = $this->util->getNextJob($printer);

		if (!empty($job)) {

			//
			// Create our full abdge number, then turn this into a list of 
			// key=value pairs.
			//
			$job["badge_num_full"] = $job["year"] . "-" 
				. $this->reg->format_badge_num($job["badge_num"]);
			$retval = http_build_query($job, "", "&");

			$reg_id = $job["reg_id"];
			$message = t("Print Job !id fetched by print client.",
				array("!id" => $id)
				);
			$this->log->log($message, $reg_id);

		}

		return($retval);

	} // End of fetch()


	/**
	* Update a specific job by setting it to a specific status.
	*
	* @param integer $id The Job ID
	*
	* @param string $status The status to set it to.
	*
	*/
	function update($id, $status) {

		$this->util->updateJob($id, $status);

		//
		// Load the job so we can get the reg ID and write a log entry.
		//
		$job = $this->util->getJob($id);
		$reg_id = $job["reg_id"];
		$message = t("Print Job !id status changed to '!status'",
			array(
				"!id" => $id,
				"!status" => $status,
			));
		$this->log->log($message, $reg_id);

	} // End of update()


} // End of Reg_Util_PrintAjax class

