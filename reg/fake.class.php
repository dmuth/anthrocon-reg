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

		$retval = "";

		for ($i=0; $i<$max; $i++) {
			$retval .= chr(mt_rand(65, 122));
		}

		return($retval);

	} // End of get_string()

	
	/**
	* Generate a random number.
	*/
	public function get_number($min = 0, $max = 100) {

		$message = "This function is deprecated!";
		reg_log($message, "", "notice", true);

		return(reg_fake_get_number($min, $max));

		$retval = mt_rand($min, $max);

		return($retval);

	} // End of get_number()


	/**
	* Generate a random credit card number.
	*/
	public function get_cc_num() {

		$message = "This function is deprecated!";
		reg_log($message, "", "notice", true);

		return(reg_fake_get_cc_num());

		$retval = "";
		$retval .=
			sprintf("%04d", $this->get_number(0, 9999))
			. " " . sprintf("%04d", $this->get_number(0, 9999))
			. " " . sprintf("%04d", $this->get_number(0, 9999))
			. " " . sprintf("%04d", $this->get_number(0, 9999))
			;

		return($retval);

	} // End of get_cc_num()


	/**
	* Return a random array element.
	*/
	public function get_random_from_set($items) {

		$message = "This function is deprecated!";
		reg_log($message, "", "notice", true);

		return(reg_fake_get_random_from_set($items));

		$len = count($items) - 1;
		$index = mt_rand(0, $len);
		$retval = $items[$index];

		return($retval);

	} // End of get_random_from_set()


	/**
	* Create a fake badge name.
	*/
	public function get_badge_name() {

		$message = "This function is deprecated!";
		reg_log($message, "", "notice", true);

		return(reg_fake_get_badge_name());

		$names = array("Fluffy", "Wolfy", "Skunky", "Lion", "Leopard",
			"Chewtoy", "Catnip", "Mouse", "Paws");

		$retval = $this->get_random_from_set($names) 
			. $this->get_number(0, 99);

		return($retval);

	} // End of get_badge_name()


	/**
	* Create a fake first name.
	*/
	public function get_first_name() {

		$message = "This function is deprecated!";
		reg_log($message, "", "notice", true);

		return(reg_fake_get_first_name());

		$names = array("Sam", "Doug", "Dave", "John", "Mark", "Dan", "Phil", 
			"Joe");

		$retval = $this->get_random_from_set($names);

		return($retval);
		
	} // End of get_first_name()


	/**
	* Create a fake last name.
	*/
	public function get_last_name() {

		$message = "This function is deprecated!";
		reg_log($message, "", "notice", true);

		return(reg_fake_get_last_name());

		$names = array("Conway", "Muth", "Smith", "Johnson", "Phillips", 
			"Stevensen");

		$retval = $this->get_random_from_set($names);

		return($retval);

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

		$index = $this->get_number(0, (count($list) - 1));
		$list_keys = array_keys($list);
		$list_index = $list_keys[$index];

		$retval = $list[$list_index];

		return($retval);

	} // End of get_item()

} // End of reg_fake class


