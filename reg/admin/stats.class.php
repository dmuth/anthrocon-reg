<?php

/**
* This class holds functions related to our search functionality.
*/
class reg_admin_stats extends reg {


	function __construct(&$message, &$fake, &$log) {
		$this->admin_member = $admin_member;
	}


	/**
	* Our statistics page.
	*/
	function get_stats() {

		$retval .= "<h2>Registration Statistics</h2>";

		$retval .= "I would put stats stuff here.";

/*
TODO:

	- Borrow database code (and possibly queries) from admin_search class.

    - Number of registrations for current convention year
        - Break down by membership
			- Check reg table
    - Amount of revenue for current convention year
        - Break down by calendar year of purchase
			- Check transactions table for data

*/

		return($retval);

	} // End of get_stats()


} // End of class reg_admin_stats

