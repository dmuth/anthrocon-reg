<?php

/**
* This function is used for printing the success page for a user after 
*	they register.
*/
class reg_success extends reg {

	function __construct(&$message, &$log) {

		$this->message = $message;
		$this->log = $log;

	}


	/**
	* Check to see if our user just registered, and print up a success 
	* message if they did.
	*/
	function success() {

		$retval = "";

		//
		// If there is no registration data, send the user over to the
		// verify page.
		//
		$data = $_SESSION["reg"]["success"];
		if (empty($data)) {
			$message = t("No success data found.  Sending user over "
				. "to verify page.");

			$this->log->log($message);
			$this->goto_url("reg/verify");
		}

		$retval = $this->success_page($data);

		return($retval);

	} // End of success()


	/**
	* Create our success page.
	*/
	function success_page(&$data) {

		$url = $this->get_verify_url();
		$email = variable_get(reg::VAR_EMAIL, "");

		$retval = "<p/>\n";

		$message = $this->message->load_display("success",
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


