<?php

/**
* This class is used for downloading search results.
*/
class reg_admin_search_download extends reg_admin_search {

	/**
	* Our array of fields which will be exported.
	*/
	protected $fields = array(
		"year" => "", "badge_num" => "", "badge_name" => "", 
		"member_type" => "", "status" => "",
		"first" => "", "middle" => "", "last" => "", "birthdate" => "", 
		"address1" => "", "address2" => "", "city" => "", "state" => "", 
		"zip" => "", "country" => "",
		"shipping_address1" => "", "shipping_address2" => "", 
		"shipping_city" => "", "shipping_state" => "", 
		"shipping_zip" => "", "shipping_country" => "",
		"no_receipt" => "No Receipt?", "email" => "", "phone" => "", "shirt_size" => "", 
		"badge_cost" => "",	"donation" => "", "total_cost" => ""
		);

	function __construct(&$message, &$fake, &$log, &$admin_member) {
		$this->log = $log;
		parent::__construct($message, $fake, $log, $admin_member);
	}


	/**
	* Similar to results(), this does a search, but instead will print
	*	out the results in tab-delmited format, suitable for downloading.
	*
	* @return string a tab-delimited string of registrations.
	*/
	function download() {

		$retval = "";

		$arg = $this->get_args_string();
		$search = $this->get_data_to_array($arg);

		if (!empty($search["submit"])) {
			$this->log_download($search);
		}
                
		if (empty($search)) {
			return(null);
		}

		//
		// Our column and record delimiters.
		//
		$delimiter = "\t";
		$newline = "\r\n";

		$retval .= $this->get_header($delimiter, $newline);

		$cursor = $this->get_cursor($search, "ORDER BY id DESC", false);

		while ($row = db_fetch_array($cursor)) {
			$retval .= $this->get_row($row, $delimiter, $newline);
		}

		return($retval);

	} // End of download()


	/**
	* Create our delimited header row.
	*
	* @param $delimiter Our delimiter
	*
	* @param $newline Our newline
	*
	* @return string A delimited string
	*/
	protected function get_header($delimiter, $newline) {

		$retval = "";

		foreach ($this->fields as $key => $value) {

			if (!empty($retval)) {
				$retval .= $delimiter;
			}

			if (!empty($value)) {
				$retval .= $value;
			} else {
				$retval .= $key;
			}
			
		}

		$retval .= $newline;

		return($retval);

	} // End of get_header()


	/**
	* Turn a row array into a delimited string.
	*
	* @param $row array Associative array of row data.
	*
	* @param $delimiter Our delimiter
	*
	* @param $newline Our newline
	*
	* @return string A delimited string
	*/
	protected function get_row(&$row, $delimiter, $newline) {

		$retval .= "";

		foreach ($this->fields as $key => $value) {

			if (!empty($retval)) {
				$retval .= $delimiter;
			}

			$retval .= $row[$key];

		}

		$retval .= $newline;

		return($retval);

	} // End of get_row()


	/**
	* Log this download.
	*/
	function log_download($search) {

		unset($search["submit"]);

		//
		// Turn our search criteria into text.
		//
		$search_text = "";
		foreach ($search as $key => $value) {
			if (!empty($value)) {
				if (!empty($search_text)) {
					$search_text .= ", ";
				}
				$search_text .= "$key: $value";
			}
		}

		$message = "Downloaded membership records. ";
		if (!empty($search_text)) {
			$message .= "Criteria: $search_text. ";
		}

		//
		// Note the page number as well.
		//
		$page = $_GET["page"];
		if (empty($page)) {
			$page = 0;
		}
		$message .= "Page: $page.";

		$this->log->log($message);

	} // End of log_download()


} // End of reg_admin_search_download class

