<?php

/**
* This is the reg_admin class, which holds functions related to the 
*	administrative end of the registration system.
*/
class reg_admin {

	function __construct() {
		$factory = new reg_factory();
		$this->log = $factory->get_object("log");
	}

	/**
	* A wrapper for setting variables.  It will log the activity.
	*/
	static function variable_set($key, $value) {

		$old_value = variable_get($key, "");

		if ($value != $old_value) {
			$message = t("Variable '%key%' set to new value: '%value%'. "
				. " (Old value: '%old_value%')",
				array(
					"%key%" => $key,
					"%value%" => $value,
					"%old_value%" => $old_value,
					)
				);

			$factory = new reg_factory();
			$log = $factory->get_object("log");

			$log->log($message);

		}

		variable_set($key, $value);

	} // End of variable_set()


	/**
	* Our "main" page for the admin.
	*/
	static function main() {

		$retval = "";

		$retval = "<h2>Quick Links:</h2>";

		$retval .= "<ul>\n"
			. "<li>" . l(t("Recent Members"), "admin/reg/members") 
				. "</li>\n"
			. "<li>" . l(t("Search Members"), "admin/reg/members/search") 
				. "</li>\n"
			. "<li>" . l(t("Add a new member"), "admin/reg/members/add") 
				. "</li>\n"
			. "<li>" . l(t("Settings"), "admin/reg/settings") 
				. "</li>\n"
			."</ul>\n"
			;

		return($retval);

	} // End of main()


} // End of reg_admin class

