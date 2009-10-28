<?php

/**
* This class is responsible for creating a catcha in our forms.
*
* The reason why we are not using the captcha module is because checking
* of the captcha is done during the validation step, and since Drupal
* does not guarantee the order in which different modules' validation
* events are run, we could concievably have a situation where a credit
* card is charged, but captcha fails.  That would be be bad.  Doing the
* captcha ourselves guarantees that it will pass before any charging occurs.
*/
class reg_captcha {

	/**
	* Our logging class
	*/
	protected $log;


	/**
	* Array of session data that is used for the captcha.
	*/
	protected $data;


	function __construct(&$log) {
		$this->log = $log;
		$this->data = &$_SESSION["reg"]["captcha"];
	}


	/**
	* This function creates a data structure for a form element that is 
	*	a captcha.
	*
	* @return mixed A form element, or null if the captcha was already 
	*	answered.
	*/
	function create() {

		//
		// If we previously answered the captcha correctly, stop here.
		//
		if (!empty($this->data["okay"])) {
			return(null);
		}

		//
		// If the captcha hasn't been generated, do so and store it in the 
		// session.  Otherwise, retrieve our data from the session.
		//
		if (empty($this->data["answer"])) {

			$num1 = $this->get_num();
			$num2 = $this->get_num();
			$answer = $num1 + $num2;

			$this->data["num1"] = $num1;
			$this->data["num2"] = $num2;
			$this->data["answer"] = $answer;

		} else {
			$num1 = $this->data["num1"];
			$num2 = $this->data["num2"];
			$answer = $this->data["answer"];

		}

		$retval= array(
			"#title" => t("Math Question"),
			"#type" => "textfield",
			"#description" => t("Please prove that you are a human.  "
				. "Past incidents with spambots have made this necessary."),
			"#field_prefix" => t("!num1 + !num2 = ",
				array(
					"!num1" => $num1,
					"!num2" => $num2,
				)),
			"#size" => 5,
			"#required" => true,
			);

		return($retval);

	} // End of create()


	/**
	* This function checks to see if the user-supplied answer matches
	*	what we were expecting.
	*
	* @param integer $answer The user-supplied answer
	*
	* @return boolean True if the answer matches, false otherwise.
	*/
	function check($answer) {

		//
		// If the user already passed the captcha, stop here.
		//
		if (!empty($this->data["okay"])) {
			return(true);
		}

		if ($this->data["answer"] != $answer) {
			$message = t("Captcha answer of '!answer' to question "
				. "'!num1 + !num2' does not match "
				. "expected answer of '!real_answer'",
				array(
					"!answer" => $answer,
					"!num1" => $this->data["num1"],
					"!num2" => $this->data["num2"],
					"!real_answer" => $this->data["answer"],
				));
			$this->log->log($message, "", WATCHDOG_WARNING);
			return(false);
		}

		//
		// Set this captcha as okay so that the form is no longer printed.
		//
		$this->data["okay"] = 1;

		return(true);

	} // End of check()


	/**
	* Return a random integer in the specified range.
	*/
	function get_num($min = 1, $max = 15) {

		$retval = mt_rand($min, $max);

		return($retval);

	} // End of get_num()


	/**
	* When a form is successful, we no longer need a captcha's data, and
	* can remove it.  This also has the side effect of making the user 
	* "start over" if they want to purchase another registration.  This 
	* is /probably/ desired behavior.
	*/
	function clear() {
		//
		// Unsetting a reference doesn't touch the data it points to.
		//
		unset($_SESSION["reg"]["captcha"]);
	}


} // End of reg_captcha class

