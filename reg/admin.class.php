<?php

/**
* This is the reg_admin class, which holds functions related to the 
*	administrative end of the registration system.
*/
class reg_admin {

	function __construct($log) {
		$this->log = $log;
	}

	/**
	* A wrapper for setting variables.  It will log the activity.
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
					)
				);

			$this->log->log($message);

		}

		variable_set($key, $value);

	} // End of variable_set()


	/**
	* Our "main" page for the admin.
	*/
	function main() {

		//
		// Only display 3 log items and transactions here.
		//
		$this->log->set_items_per_page(3);

		$retval = "";

		$retval = t("<h2>Quick Links:</h2>");

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

		//
		// Show a few recent log entries and transactions, so we can
		// get an idea of the status of the reg system at a glance.
		//
		$retval .= t("<h2>Recent Log Entries:</h2>")
 			. $this->log->log_recent()
			. t("<h2>Recent Transactions:</h2>")
			. $this->log->trans_recent()
			;

		return($retval);

	} // End of main()


} // End of reg_admin class

