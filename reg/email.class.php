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

	function __construct($message) {
		$this->message = &$message;
	}


	/**
	* Actually send out our email.
	*
	* @param string $address The recipient
	*
	* @param string $subject The subject of the email
	*
	* @param string $message_name The name of the message to send.
	*
	* @param array $data Associative array of data that will replace tokens
	*	in the message.
	*
	* @return mixed The sent email message is returned on success.
	*	If there is an error sending the message, false is returned.
	*/
	public function email($address, $subject, $message_name, &$data) {

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
		//
		$fake_email = variable_get(reg_form::FORM_ADMIN_FAKE_EMAIL, "");

		if (!$fake_email) {
			$result = mail($address, $subject, $retval, $headers);

			if (empty($result)) {
				$retval = false;
			}

		}

		return($retval);

	} // End of email()


} // End of reg_email class


