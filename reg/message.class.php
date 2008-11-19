<?php

/**
* This class is used for interacting with stored messages from user-facing
*	pages.
*
* The reason why this extends the reg class is because the reg class also
* depends on this class, and we can't have any circular dependencies.  
* That would be bad.
*/
class reg_message extends reg {

	/**
	* @var An associative array of tokens for each of the messages.
	*	Strangely enough, trying to use t() on the value causes a syntax 
	*	error in PHP.  I guess executing code there is a no-no. :-)
	*/
	protected $tokens = array();


	function __construct(&$log) {
		$this->log = $log;
	}


	/**
	* This function loads a message, based on its unique name.
	* 
	* @param string $name The unique name for the message.
	*
	* @return array Associative array of data for the message, or null
	*	if no message by that name was found.
	*/
	function load($name) {

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
			$this->log->log($message, '', WATCHDOG_WARNING);
		}

		return($retval);

	} // End of load()


	/**
	* A wrapper for the load() function.  This function will convert newlines
	* to <br> tags, and parse values with user-specified data so that the
	* result can be displayed to the user.
	*
	* @param string $name The name of the message to load.
	*
	* @param array $data An associative array where the key is the token and
	*	the value is what gets substituted in that token.  You will note that
	*	this is exactly the same type of array which can be passed into the
	*	t() function.
	*
	* @param boolean $edit_link If set to false, the edit link will not be 
	*	displayed, even for an admin.  This should be set when sending out 
	*	emails.
	* 
	* @return array An associative array with the message to display and
	*	the subject line, if it is an email.
	*/
	function load_display($name, $data = array(), $edit_link = true) {

		$retval = array();

		$this->set_tokens();
		$message = $this->load($name);
		$retval["subject"] = $message["subject"];
		$retval["type"] = $message["type"];
		$retval["value"] = $message["value"];

		//
		// If the user is an admin, give them an edit link.
		//
		if ($this->is_admin() && $edit_link) {
			$url = "admin/reg/settings/messages/" . $message["id"] . "/edit";
			$retval["value"] .= " " . l(t("[Edit this blurb]"), $url, "", 
				drupal_get_destination());
		}

		//
		// Grab our email address and munge it.
		//
		$data["!email"] = variable_get(reg::VAR_EMAIL, "");
		$tmp = $data["!email"];
		$tmp = str_replace("@", " AT ", $tmp);
		$tmp = str_replace(".", " DOT ", $tmp);
		$data["!munged_email"] = $tmp;

		//
		// Grab our verify URL, too.
		//
        $url = reg_data::get_verify_url();
		$data["!verify_url"] = l($url, $url);

		$retval["value"] = t($retval["value"], $data);

		//
		// No need to mess with input filters here, since the data is
		// coming from an admin.
		//
		$retval["value"] = nl2br($retval["value"]);

		return($retval);

	} // End of load_display()


	/**
	* Same as load(), but load a message by ID.
	*/
	function load_by_id($id) {

		$this->set_tokens();
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
			$this->log->log($message, '', WATCHDOG_WARNING);
		}


		return($retval);

	} // End of load_by_id()


	/**
	* Retrieve the message tokens for a specific message.
	*
	* @param string $key The message to get tokens for.
	*
	* @return array An associative array full of tokens and descriptions 
	*	for each token.
	*/
	function get_tokens($key) {

		$retval = array();
		$this->set_tokens();

		if (!empty($this->tokens[$key])) {
			$retval = $this->tokens[$key];
		}

		return($retval);

	} // End of get_tokens()


	/**
	* Set our object-wide list of tokens.
	* The reason for this function is because we can't use any functions
	*	or string concatenation if I set the values in the class definition.
	*/
	function set_tokens() {

		if (!empty($this->tokens)) {
			return(null);
		}

		$this->tokens = array(
			"no-levels-available" => array(
				"!email" => t("Our contact email."),
				"!munged_email" => t("Our obfuscated email address.  "
					. "Please use this on public pages!"),
				),
			"verify" => array(
				"!email" => t("Our contact email."),
				"!munged_email" => t("Our obfuscated email address.  "
					. "Please use this on public pages!"),
				),
			"success" => array(
				"!email" => t("Our contact email."),
				"!munged_email" => t("Our obfuscated email address.  "
					. "Please use this on public pages!"),
				"!member_email" => t("The member's email address."),
				"!verify_url" => t("The verify page URL."),
				"!badge_num" => t("The assigned badge number."),
				"!cc_name" => t("The credit card name."),
				"!total_cost" => t("The total cost of the membership."),
				),
			"email-receipt" => array(
				"!email" => t("Our contact email."),
				"!name" => t("The member's full name."),
				"!badge_num" => t("The badge number"),
				"!site" => t("The name of this site."),
				"!url" => t("The URL of this site."),
				"!total_cost" => t("The total cost. (Badge plus donation)"),
				"!cc_num" => t("The credit card used to pay for the membership.")
				),
			"email-receipt-no-cc" => array(
				"!email" => t("Our contact email."),
				"!name" => t("The member's full name."),
				"!badge_num" => t("The badge number"),
				"!site" => t("The name of this site."),
				"!url" => t("The URL of this site."),
				"!total_cost" => t("The total cost. (Badge plus donation)"),
				),
			"cc-declined" => array(
				"!email" => t("Our contact email."),
				"!munged_email" => t("Our obfuscated email address.  "
					. "Please use this on public pages!"),
				),
			"cc-error" => array(
				"!email" => t("Our contact email."),
				"!munged_email" => t("Our obfuscated email address.  "
					. "Please use this on public pages!"),
				),
			);

	} // End of set_tokens()

} // End of reg_message class


