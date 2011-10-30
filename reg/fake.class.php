<?php

/**
* This class is used for generating lots of fake data for registration forms.
* This will be used lots during testing, when I want to create all sorts of
* different membership records so that I can test out the system.
*
* One reason why this code is in a separate class file is so that it will
* not get loaded at ALL during production use of the system.
*/
class reg_fake extends reg {


	function __construct() {
	} // End of __construct()


	/**
	* This function creates a lot of fake data for our form.
	*
	* @param array $data The associative array of data from our form.
	*/
	function get_data(&$data) {

		$message = "This function is deprecated!";
		reg_log($message, "", "notice", true);

		reg_fake_get_data(&$data);

	} // End of fake_data()


	/**
	* Generate a random string.
	*/
	public function get_string($max = 8) {

		$message = "This function is deprecated!";
		reg_log($message, "", "notice", true);

		return(reg_fake_get_string($max));

	} // End of get_string()

	
	/**
	* Generate a random number.
	*/
	public function get_number($min = 0, $max = 100) {

		$message = "This function is deprecated!";
		reg_log($message, "", "notice", true);

		return(reg_fake_get_number($min, $max));

	} // End of get_number()


	/**
	* Generate a random credit card number.
	*/
	public function get_cc_num() {

		$message = "This function is deprecated!";
		reg_log($message, "", "notice", true);

		return(reg_fake_get_cc_num());

	} // End of get_cc_num()


	/**
	* Return a random array element.
	*/
	public function get_random_from_set($items) {

		$message = "This function is deprecated!";
		reg_log($message, "", "notice", true);

		return(reg_fake_get_random_from_set($items));

	} // End of get_random_from_set()


	/**
	* Create a fake badge name.
	*/
	public function get_badge_name() {

		$message = "This function is deprecated!";
		reg_log($message, "", "notice", true);

		return(reg_fake_get_badge_name());

	} // End of get_badge_name()


	/**
	* Create a fake first name.
	*/
	public function get_first_name() {

		$message = "This function is deprecated!";
		reg_log($message, "", "notice", true);

		return(reg_fake_get_first_name());

	} // End of get_first_name()


	/**
	* Create a fake last name.
	*/
	public function get_last_name() {

		$message = "This function is deprecated!";
		reg_log($message, "", "notice", true);

		return(reg_fake_get_last_name());

	} // End of get_last_name()


	/**
	* This function selects a random item from a list and returns it.
	*
	* @param array $list An array of items to select from.  It does NOT
	*	need to be a scalar array.
	*
	* @return mixed A random item from the list.
	*/
	public function get_item($list) {

		$message = "This function is deprecated!";
		reg_log($message, "", "notice", true);

		return(reg_fake_get_item($list));

	} // End of get_item()

} // End of reg_fake class


