<?php

/**
* This class is responsible for sending out messages as emails.
*
*/
class reg_email {

	/**
	* @var An instance of the reg_message class.
	*/
	protected $message;


	/**
	* @var An instance of the reg_log class.
	*/
	protected $log;


	function __construct($message, $log) {
		$this->message = &$message;
		$this->log = &$log;
	}


	/**
	* Actually send out our email.
	*
	* @param string $address The recipient
	*
	* @param string $message_name The name of the message to send.
	*
	* @param array $data Associative array of data that will replace tokens
	*	in the message.
	*
	* @param integer $reg_id The ID from the reg table.
	*
	* @return mixed On success, an array with the message and subject is 
	*	returned. 
	*	If there is an error sending the message, false is returned.
	*/
	public function email($address, $message_name, $reg_id, &$data) {

		$retval = $this->message->load_display($message_name, $data, false);

		//
		// Try sending out our email.  If we fail, return false.
		//
		$our_email = variable_get(reg::VAR_EMAIL, "");
		$headers = "From: $our_email\r\n";
		$headers .= "MIME-Version: 1.0\r\n";
		$headers .= "Content-type: text/html; charset=iso-8859-1\r\n";

		//
		// Make sure we're not faking email.
		// If we are, don't really send the email, and write a special
		// log message informing the admin.
		//
		$fake_email = variable_get(reg_form::FORM_ADMIN_FAKE_EMAIL, "");

		$result = true;
		if (!$fake_email) {
			$result = mail($address, $retval["subject"], 
				$retval["value"], $headers);
		}

		//
		// If there was an error sending the email, be sure to log it.
		//
		if (empty($result)) {
			$message = t("An error occured when attempting to send an "
				. "email to '!email'.",
				array(
					"!email" => $address,
				));
			$this->log->log($message, $reg_id, WATCHDOG_ERROR);

			return(false);
		} 

		$message = t("Email receipt sent to '!email'.<br/><br/>"
			. "Subject: !subject<br/><br/>"
			. "!message",
			array(
				"!email" => $address,
				"!subject" => $retval["subject"],
				"!message" => $retval["value"],
			));
		$this->log->log($message, $reg_id);

		if ($fake_email) {
			$message = t("Just kidding!  We are currently set to fake "
				. "sending email messages.");
			$this->log->log($message, $reg_id);

		}

		return($retval);

	} // End of email()


} // End of reg_email class


