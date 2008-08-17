<?php

/**
* This class is used for generating lots of fake data for registration forms.
* This will be used lots during testing, when I want to create all sorts of
* different membership records so that I can test out the system.
*
* One reason why this code is in a separate class file is so that it will
* not get loaded at ALL during production use of the system.
*/
class reg_fake {


	/**
	* This function creates a lot of fake data for our form.
	*
	* @param array $data The associative array of data from our form.
	*/
	static function get_data(&$data) {

		$data["badge_name"] = self::get_badge_name();
		$data["first"] = self::get_first_name();
		$data["middle"] = self::get_first_name();
		$data["last"] = self::get_last_name();
		$data["address1"] = self::get_string();
		$data["address2"] = self::get_string();
		$data["city"] = self::get_string();
		$data["state"] = self::get_string();
		$data["zip"] = self::get_string();
		$data["country"] = self::get_string();
		$data["email"] = self::get_string();
		$data["email2"] = $data["email"];
		$data["phone"] = self::get_string();
		$data["shirt_size_id"] = self::get_number(1, 5);
		$data["conduct"] = 1;
		$data["cc_type"] = self::get_number(1, 4);
		$data["cc_num"] = self::get_cc_num();
		$data["cc_exp"]["month"] = self::get_number(1, 12);
		$data["cc_exp"]["year"] = date("Y") + self::get_number(1, 5);
		$data["donation"] = self::get_number(0, 250)
			. "." . sprintf("%02d", self::get_number(0, 99))
			;

	} // End of fake_data()


	/**
	* Generate a random string.
	*/
	protected static function get_string() {

		$retval = "";

		$max = 8;

		for ($i=0; $i<$max; $i++) {
			$retval .= chr(mt_rand(65, 122));
		}

		return($retval);

	} // End of get_string()

	
	/**
	* Generate a random number.
	*/
	protected static function get_number($min = 0, $max = 100) {

		$retval = mt_rand($min, $max);

		return($retval);

	} // End of get_number()


	/**
	* Generate a random credit card number.
	*/
	protected static function get_cc_num() {

		$retval = "";
		$retval .=
			sprintf("%04d", self::get_number(0, 9999))
			. " " . sprintf("%04d", self::get_number(0, 9999))
			. " " . sprintf("%04d", self::get_number(0, 9999))
			. " " . sprintf("%04d", self::get_number(0, 9999))
			;

		return($retval);

	} // End of get_cc_num()


	/**
	* Return a random array element.
	*/
	protected static function get_random_from_set($items) {

		$len = count($items) - 1;
		$index = mt_rand(0, $len);
		$retval = $items[$index];

		return($retval);

	} // End of get_random_from_set()


	/**
	* Create a fake badge name.
	*/
	protected static function get_badge_name() {

		$names = array("Fluffy", "Wolfy", "Skunky", "Lion", "Leopard",
			"Chewtoy", "Catnip", "Mouse", "Paws");

		$retval = self::get_random_from_set($names) 
			. self::get_number(0, 99);

		return($retval);

	} // End of get_badge_name()


	/**
	* Create a fake first name.
	*/
	protected static function get_first_name() {

		$names = array("Sam", "Doug", "Dave", "John", "Mark", "Dan", "Phil", 
			"Joe");

		$retval = self::get_random_from_set($names);

		return($retval);
		
	} // End of get_first_name()


	/**
	* Create a fake last name.
	*/
	protected static function get_last_name() {

		$names = array("Conway", "Muth", "Smith", "Johnson", "Phillips", 
			"Stevensen");

		$retval = self::get_random_from_set($names);

		return($retval);

	} // End of get_last_name()


} // End of reg_fake class


