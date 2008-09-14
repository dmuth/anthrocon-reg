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

		//
		// Get a random number of days, figure out the seconds, and 
		// subtract them from the current time.
		//
		$num_days = self::get_number(45, 20000);
		$num_seconds = $num_days * 86400;
		$data["birthdate"] =time() - $num_seconds;

		$data["address1"] = self::get_number(1, 1000) . " " 
			. self::get_string();
		$data["address2"] = self::get_string();
		$data["city"] = self::get_string() . " " . self::get_string();
		$data["state"] = self::get_string();
		$data["zip"] = self::get_number(10000, 99999) . "-" 
			. sprintf("%04d", self::get_number(0, 9999));
		$data["country"] = self::get_string();
		$data["email"] = self::get_string() . "@" . self::get_string()
			. "." . self::get_string(3)
			;
		$data["email2"] = $data["email"];
		$data["phone"] = 
			self::get_number(0, 999)
			. "-"
			. self::get_number(0, 99999)
			. "-"
			. sprintf("%04d", self::get_number(1, 9999))
			;
		$data["shirt_size_id"] = self::get_number(1, 5);
		$data["conduct"] = 1;
		$data["cc_type_id"] = self::get_number(1, 4);
		$data["cc_num"] = self::get_cc_num();
		$data["cvv"] = self::get_number(100, 999);
		$data["cc_exp"]["month"] = self::get_number(1, 12);
		$data["cc_exp"]["year"] = date("Y") + self::get_number(1, 5);
		$data["donation"] = self::get_number(0, 250)
			. "." . sprintf("%02d", self::get_number(0, 99))
			;
		$data["badge_cost"] = self::get_number(0, 250)
			. "." . sprintf("%02d", self::get_number(0, 99))
			;
		$data["reg_payment_type_id"] = self::get_number(1, 10);
		$data["reg_type_id"] = self::get_number(1, 12);

		//
		// What we're going to do here is get a list of valid levels,
		// then get a random value somewhere between zero and the number
		// of levels.
		//
		// From there, we'll get a list of all keys from that aray in a
		// separate array with sequential keys, index that array based on 
		// the random value, and have the final reg_level_id to set in 
		// the form.
		//
		$levels = reg_data::get_valid_levels();
		$index = self::get_number(0, (count($levels) - 1));
		$level_keys = array_keys($levels);
		$level_index = $level_keys[$index];
		$data["reg_level_id"] = $level_index;

	} // End of fake_data()


	/**
	* Generate a random string.
	*/
	public static function get_string($max = 8) {

		$retval = "";

		for ($i=0; $i<$max; $i++) {
			$retval .= chr(mt_rand(65, 122));
		}

		return($retval);

	} // End of get_string()

	
	/**
	* Generate a random number.
	*/
	public static function get_number($min = 0, $max = 100) {

		$retval = mt_rand($min, $max);

		return($retval);

	} // End of get_number()


	/**
	* Generate a random credit card number.
	*/
	public static function get_cc_num() {

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
	public static function get_random_from_set($items) {

		$len = count($items) - 1;
		$index = mt_rand(0, $len);
		$retval = $items[$index];

		return($retval);

	} // End of get_random_from_set()


	/**
	* Create a fake badge name.
	*/
	public static function get_badge_name() {

		$names = array("Fluffy", "Wolfy", "Skunky", "Lion", "Leopard",
			"Chewtoy", "Catnip", "Mouse", "Paws");

		$retval = self::get_random_from_set($names) 
			. self::get_number(0, 99);

		return($retval);

	} // End of get_badge_name()


	/**
	* Create a fake first name.
	*/
	public static function get_first_name() {

		$names = array("Sam", "Doug", "Dave", "John", "Mark", "Dan", "Phil", 
			"Joe");

		$retval = self::get_random_from_set($names);

		return($retval);
		
	} // End of get_first_name()


	/**
	* Create a fake last name.
	*/
	public static function get_last_name() {

		$names = array("Conway", "Muth", "Smith", "Johnson", "Phillips", 
			"Stevensen");

		$retval = self::get_random_from_set($names);

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

		$index = self::get_number(0, (count($list) - 1));
		$list_keys = array_keys($list);
		$list_index = $list_keys[$index];

		$retval = $list[$list_index];

		return($retval);

	} // End of get_item()

} // End of reg_fake class


