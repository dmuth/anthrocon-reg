<?php

/**
* This function is used for printing the success page for a user after 
*	they register.
*/
class reg_success {

	function __construct() {
		$factory = new reg_factory();
		$this->message = $factory->get_object("message");
		$this->log = $factory->get_object("log");
	}


	/**
	* Check to see if our user just registered, and print up a success 
	* message if they did.
	*/
	static function success() {

		$retval = "";

		//
		// If there is no registration data, send the user over to the
		// verify page.
		//
		$data = $_SESSION["reg"]["success"];
		if (empty($data)) {
			$message = t("No success data found.  Sending user over "
				. "to verify page.");

			$factory = new reg_factory();
			$log = $factory->get_object("log");
			$log->log($message);

			reg::goto_url("reg/verify");
		}

		$retval = self::success_page($data);

		return($retval);

	} // End of success()


	/**
	* Create our success page.
	*/
	static function success_page(&$data) {

		$url = reg_data::get_verify_url();
		$email = variable_get(reg::VAR_EMAIL, "");

		$retval = "<p/>\n";

		$factory = new reg_factory();
		$message = $factory->get_object("message");
		$message = $message->load_display("success",
			array(
				"!email" => $email,
				"!member_email" => $data["member_email"],
				"!verify_url" => l($url, $url),
				"!badge_num" => $data["badge_num"],
				"!cc_name" => $data["cc_name"],
				"!total_cost" => $data["total_cost"],
				)
			);

		$retval .= $message["value"];

		return($retval);

	} // End of success_page()


} // End of reg_success class


