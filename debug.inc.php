<?php
/**
* Our functions that relate to debugging.
*
* @author Douglas Muth <http://www.dmuth.org/>
*/


/**
* Our main debug function.
*
* @param string $message
*/
function reg_debug($message) {

	if (is_array($message)) {
		$message = "<pre>" . print_r($message, true) . "</pre>";
	}

	drupal_set_message("Reg Debug: " . $message);

} // End of reg_debug()

/**
* A wrapper for our reg_debug() function that includes a backtrace.
*/
function reg_debug_backtrace($message) {

	if (is_array($message)) {
		$message = print_r($message, true);
	}

	$trace = reg_log_get_backtrace(2);
	$message .= "<br/><br/>Traceback:<br/>${trace}";

	reg_debug($message);

} // End of reg_debug_backtrace()


