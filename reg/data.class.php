<?php

/**
* This class holds misc functions for interacting with data in the databse.
* Examples include: getting the next available badge number, getting all
*	membership levels, getting all t-shirt sizes, etc.
*	
*/
class reg_data {

	/**
	* Get the next badge number for the current year.
	*
	* @return integer The badge number.  This number can be assigned
	*	to a specific user.
	*/
	static function get_badge_num() {

		$year = reg::YEAR;
		$query = "UPDATE {reg_badge_num} "
			. "SET badge_num = @val := badge_num+1 "
			. "WHERE year=%s ";
		db_query($query, array($year));
		$cursor = db_query("SELECT @val AS badge_num");
		$results = db_fetch_array($cursor);

		return($results["badge_num"]);

	} // End of get_badge_num()


	/**
	* Retrieve the most recent insert ID from a database insert.
	*
	* @return integer The most recent insert ID.
	*/
	static function get_insert_id() {

		$cursor = db_query("SELECT LAST_INSERT_ID() AS id");
		$row = db_fetch_array($cursor);
		$retval = $row["id"];
		return($retval);

	} // End of get_insert_id()


	/**
	* This function gets the human-readable name for a specific card.
	*
	* @param mixed $cc_type This can be a string such as "Visa", or a value 
	*	from the reg_cc_type table.
	*
	* @param string $cc_num The full credit card number.  This is assumed to 
	*	consist only of integers.
	*
	* @return string A human-readable string, such as "Visa ending in '1234'"
	*
	*/
	static function get_cc_name($cc_type, $cc_num) {

		//
		// Get the string type for a card if we don't already have it.
		//
		$cc_type_int = intval($cc_type);
		if ($cc_type == (string)$cc_type_int) {
			$types = self::get_cc_types();
			$cc_type = $types[$cc_type];
		}

		$retval = "${cc_type} ending in '" 
			. self::get_cc_last_4($cc_num) . "'";

		return($retval);

	} // End of get_cc_name()


	/**
	* Get the last 4 digits of a credit card number.
	*
	* We do NOT want to store the entire crediit card number in our system.
	* That way, it limits exposure in case of a security comprismise and 
	* it lowers my blood pressure. :-)
	*
	* @return string The last 4 digits from our credit card number.
	*/
	static function get_cc_last_4($cc_num) {

		$retval = substr($cc_num, -4);

		return($retval);

	} // End of get_last_4()


	/**
	* Return a list of currently valid registration levels.
	*
	* @return array The key is the membersip ID and the value is
	*	an associative array of member data.
	*/
	static function get_valid_levels() {

		static $retval = array();

		if (!empty($retval)) {
			return($retval);
		}

		$timestamp = gmmktime();
		$query = "SELECT * FROM {reg_level} "
			. "WHERE "
			. "start <= '%s' AND end >= '%s' "
			. "ORDER BY price "
			;
		$query_args = array($timestamp, $timestamp);
		$cursor = db_query($query, $query_args);

		while ($row = db_fetch_array($cursor)) {
			$id = $row["id"];
			$retval[$id] = $row;
		}

		return($retval);

	} // End of get_valid_levels()


	/**
	* Determine the cost of a registration based on the reg_level_id.
	*
	* @return integer The cost of the registration.
	*/
	static function get_reg_cost($level_id) {

		$query = "SELECT price FROM {reg_level} "
			. "WHERE id='%s'";
		$query_args = array($level_id);
		$cursor = db_query($query, $query_args);
		$row = db_fetch_array($cursor);
		$retval = $row["price"];

		return($retval);

	} // End of get_reg_cost()


	/**
	* Get the registration type ID, based on the level.
	*/
	static function get_reg_type_id($level_id) {

		$query = "SELECT reg_type_id FROM {reg_level} "
			. "WHERE id='%s'";
		$query_args = array($level_id);
		$cursor = db_query($query, $query_args);
		$row = db_fetch_array($cursor);
		$retval = $row["reg_type_id"];

		return($retval);

	} // End of get_reg_type_id()


	/**
	* This function retrieves our different types of membership from
	* the database.
	*
	* @return array Array where the key is the unique ID and the value is
	*	the membership type.
	*/
	static function get_types() {

		//
		// Cache our rows between calls
		//
		static $retval = array();

		if (!empty($retval)) {
			return($retval);
		}

		$query = "SELECT * FROM {reg_type} ORDER BY weight";
		$cursor = db_query($query);

		while ($row = db_fetch_array($cursor)) {
			$id = $row["id"];
			$name = $row["member_type"];
			$retval[$id] = $name;
		}

		return($retval);

	} // End of get_types()


	/**
	* Retrieve our different transaction types from the database.
	*
	* @return array Array where the key is the unique ID and the value is
	*	the transaction type.
	*/
	static function get_trans_types() {

		//
		// Cache our rows between calls
		//
		static $retval = array();

		if (!empty($retval)) {
			return($retval);
		}

		$query = "SELECT * FROM {reg_trans_type} ";
		$cursor = db_query($query);

		while ($row = db_fetch_array($cursor)) {
			$id = $row["id"];
			$name = $row["trans_type"];
			$retval[$id] = $name;
		}

		return($retval);

	} // End of get_trans_types()


	/**
	* Retrieve data on a payment type based on an ID.
	*/
	static function get_payment_type($id) {

		$query = "SELECT "
			. "payment_type "
			. "FROM {reg_payment_type} "
			. "WHERE "
			. "id='%s' "
			;
		$query_args = array($id);
		$cursor = db_query($query, $query_args);

		$row = db_fetch_array($cursor);

		return($row["payment_type"]);

	} // End of get_payment_type()


	/**
	* Retrieve our different payment types from the database.
	*
	* @return array Array where the key is the unique ID and the value is
	*	the payment type.
	*/
	static function get_payment_types() {

		//
		// Cache our rows between calls
		//
		static $retval = array();

		if (!empty($retval)) {
			return($retval);
		}

		$query = "SELECT * FROM {reg_payment_type} ";
		$cursor = db_query($query);

		while ($row = db_fetch_array($cursor)) {
			$id = $row["id"];
			$name = $row["payment_type"];
			$retval[$id] = $name;
		}

		return($retval);

	} // End of get_payment_types()


	/**
	* This function retrieves our different statuses from the database.
	*
	* @return array Array where the key is the unique ID and the value is
	*	the status.
	*
	* @todo I need to do something about the "detail" field at some point...
	*/
	static function get_statuses() {

		//
		// Cache our rows between calls
		//
		static $retval = array();

		if (!empty($retval)) {
			return($retval);
		}

		$query = "SELECT * FROM {reg_status} ORDER BY weight";
		$cursor = db_query($query);

		while ($row = db_fetch_array($cursor)) {
			$id = $row["id"];
			$name = $row["status"];
			$retval[$id] = $name;
		}

		return($retval);

	} // End of get_statuses()


	/**
	* This function retrieves our different credit card types from the
	*	database.
	*
	* @return array Array where the key is the unique ID and the value is
	*	the credit card type.
	*/
	static function get_cc_types() {

		//
		// Cache our values between calls
		//
		static $retval = array();

		if (!empty($retval)) {
			return($retval);
		}

		$query = "SELECT * FROM {reg_cc_type}";
		$cursor = db_query($query);

		while ($row = db_fetch_array($cursor)) {
			$id = $row["id"];
			$name = $row["cc_type"];
			$retval[$id] = $name;
		}

		return($retval);

	} // End of get_statuses()


	/**
	* This function gets our possible shirt sizes.
	*
	* @param boolean $disabled Do we want to include disabled shirt sizes?
	*
	* @return array Array where the key is the unique ID and the value is
	*	the shirt size.
	*/
	static function get_shirt_sizes($disabled = false) {

		//
		// Cache our values between calls
		//
		static $retval = array();
		
		if (!empty($retval)) {
			return($retval);
		}

		$query = "SELECT * FROM {reg_shirt_size}";
		if (empty($disabled)) {
			$query .= " WHERE disabled IS NULL";
		}
		$cursor = db_query($query);

		while ($row = db_fetch_array($cursor)) {
			$id = $row["id"];
			$size = $row["shirt_size"];
			$retval[$id] = $size;
		}

		return($retval);

	} // End of get_shirt_sizes()


	/**
	* Return an array of credit card expiration months.
	*/
	static function get_cc_exp_months() {
		//
		// We put a bogus first element in so that the rest of the elements
		// will match their keys.
		//
		$retval = array(
			"1" => "Jan",
			"2" => "Feb",
			"3" => "Mar",
			"4" => "Apr",
			"5" => "May",
			"6" => "Jun",
			"7" => "Jul",
			"8" => "Aug",
			"9" => "Sep",
			"10" => "Oct",
			"11" => "Nov",
			"12" => "Dec",
			);
		return($retval);
	}


	/**
	* Return an array of credit card expiration years.
	*/
	static function get_cc_exp_years() {
		$retval = array();

		$start = date("Y");
		$end = $start + 7;
		for ($i = $start; $i<$end; $i++) {
			$retval[$i] = $i;
		}

		return($retval);
	}


	/**
	* This function will calculate a UNIX timestamp based on the year, 
	*	month, and day.  Note that the timestamp will be adjusted for
	*	the local timezone. 
	*
	* For example, passing in a date of 12-30-2008, while this code is
	*	being run during EDT (GMT -0400), will result in a timestamp which
	*	evaluates to 30 Dec 2008 00:00:00 -0400.
	*/
	static function get_time_t($year, $month, $day) {

		$retval = gmmktime(0, 0, 0, $month, $day, $year);

		$offset = date("Z") * -1;
		$retval += $offset;

		return($retval);

	} // End of get_time_t()


	/**
	* Format a badge number so that it is filled with leading zeros.
	*/
	static function format_badge_num($badge_num) {

		$retval = sprintf("%04d", $badge_num);
		return($retval);

	} // End of format_badge_num()


	/**
	* Get changed fields from old data to new data, and create a string
	* that details what was changed.  This way, we can have detailed
	* change auditing.
	*
	* @param array $data The new set of data.
	*
	* @param array $old_data The old set of data
	*
	* @return mixed If no changed were found, null is returned.  Otherwise
	*	a string detailing the changes is returned.
	*/
	static function get_changed_data(&$data, &$old_data) {

		$retval = "";

		foreach ($old_data as $key => $value) {

			if ($value != $data[$key]) {
				$retval .= t("Key '!key' set to '!new_value' "
					. "(old value: '!old_value').\n",
					array(
						"!key" => $key,
						"!new_value" => $data[$key],
						"!old_value" => $value,
						)
					);
			}
		}

		if (!empty($retval)) {
			$retval = "(Fields changed:\n$retval)\n";
		}

		return($retval);

	} // End of get_changed_data()


} // End of reg_data class

