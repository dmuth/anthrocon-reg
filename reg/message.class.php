<?php

/**
* This class is used for interacting with stored messages from user-facing
*	pages.
*/
class reg_message {

	/**
	* This function loads a message, based on its unique name.
	* 
	* @param string $name The unique name for the message.
	*
	* @return array Associative array of data for the message, or null
	*	if no message by that name was found.
	*/
	static function load($name) {

		$query = "SELECT * FROM {reg_message} "
			. "WHERE "
			. "name='%s' ";
		$query_args = array($name);

		$cursor = db_query($query, $query_args);
		$retval = db_fetch_array($cursor);

		if (empty($retval)) {
			$message = t("Unable to load message with name '!name'!",
				array("!name" => $name)
				);
			reg_log::log($message, '', WATCHDOG_WARNING);
		}

		return($retval);

	} // End of load()


	/**
	* Same as load(), but load a message by ID.
	*/
	static function load_by_id($id) {

		$query = "SELECT * FROM {reg_message} "
			. "WHERE "
			. "id='%s' ";
		$query_args = array($id);

		$cursor = db_query($query, $query_args);
		$retval = db_fetch_array($cursor);

		if (empty($retval)) {
			$message = t("Unable to load message with id '!id'!",
				array("!id" => $id)
				);
			reg_log::log($message, '', WATCHDOG_WARNING);
		}


		return($retval);

	} // End of load_by_id()


} // End of reg_message class


