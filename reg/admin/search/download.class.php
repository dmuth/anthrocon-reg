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

		$search = $this->search_get_args();
                
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


} // End of reg_admin_search_download class

