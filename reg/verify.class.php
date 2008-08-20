<?php

/**
* This class is used so that users can verify their current registrations.
*/
class reg_verify {

	/**
	* Our registration verification page.
	*/
	static function verify() {

		$retval .= drupal_get_form("reg_verify_form");

		return($retval);

	} // End of search()


	static function verify_form() {

/*
TODO:
- Copy lots of stuff from the reg_search class
- For passing data to the result functin, I should probably look into session data instead, so that search results don't get indexed.
*/

	}


	static function verify_validate($form_id, &$data) {
	}


	static function verify_submit($form_id, &$data) {
	}


	static function results() {
	}


} // End of reg_verify class

