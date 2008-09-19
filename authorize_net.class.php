<?php

/**
* Our core authorize_net class.
*
* It is extended by any other classes, and contains core functions and data.
*/
class authorize_net {

	/**
	* Variable names.
	*/
	const TEST_MODE = "authorize.net_test_mode";
	const LOGIN_ID = "authorize.net_login_id";
	const TRANSACTION_KEY = "authorize.net_transaction_key";

	/**
	* This will eventually be moved into the reg hierarchy once I 
	*	sort out some of those classes...
	*/
	const FORM_TEXT_SIZE = 20;

	/**
	* Our reg class.
	*/
	protected $reg;

	/**
	* Our log class.
	*/
	protected $log;


	function __construct($reg, &$log) {
		$this->reg = &$reg;
		$this->log = &$log;
	}


	/**
	* A wrapper for the variable_get() function, since I dislike having
	* to specify an empty string every string time.
	*
	* Eventually, I'll set these up in the reg class, but I have to 
	*	redesign that class hierarchy, first.
	*/
	function variable_get($key) {
		return(variable_get($key, ""));
	}


	/**
	* A wrapper for setting variables.  It will log the activity.
	*
	* Eventually, I'll set these up in the reg class, but I have to 
	*	redesign that class hierarchy, first.
	*/
	function variable_set($key, $value) {

		$old_value = variable_get($key, "");

		if ($value != $old_value) {
			$message = t("Variable '%key%' set to new value: '%value%'. "
				. " (Old value: '%old_value%')",
				array(
					"%key%" => $key,
					"%value%" => $value,
					"%old_value%" => $old_value,
				));

			$this->log->log($message);

		}

		variable_set($key, $value);

	} // End of variable_set()


} // End of authorize_net class

