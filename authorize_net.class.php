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
	protected function variable_get($key) {
		return(variable_get($key, ""));
	}


	/**
	* A wrapper for setting variables.  It will log the activity.
	*
	* Eventually, I'll set these up in the reg class, but I have to 
	*	redesign that class hierarchy, first.
	*/
	protected function variable_set($key, $value) {

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


	/**
	* This function takes an array of user data in the format that is 
	* normally used in our database (with respect to array keys) and converts
	* it into a set of array keys that authorize.net expects.
	*
	* @return array The array of the modified keys.
	*/
	protected function prepare_data($data) {

		$retval = array();

		$retval["x_login"] = $this->variable_get(self::LOGIN_ID);
		$retval["x_tran_key"] = $this->variable_get(self::TRANSACTION_KEY);
		$retval["x_type"] = "AUTH_CAPTURE";
		$retval["x_version"] = "3.1";
		$retval["x_delim_data"] = "TRUE";
		$retval["x_delim_char"] = ":";
		//$data["x_encap_char"] = "|";

		$convert = array();
		$convert["first"] = "x_first_name";
		$convert["last"] = "x_last_name";
		$convert["address1"] = "x_address";
		$convert["city"] = "x_city";
		$convert["state"] = "x_state";
		$convert["zip"] = "x_zip";
		$convert["country"] = "x_country";
		$convert["phone"] = "x_phone";
		$convert["email"] = "x_email";
		$convert["description"] = "x_description";
		$convert["invoice_number"] = "x_invoice_num";
		$convert["cvv"] = "x_card_code";
		$convert["cc_num"] = "x_card_num";
		$convert["cc_exp"] = "x_exp_date";
		$convert["total_cost"] = "x_amount";

		$convert["shipping_name"] = "x_ship_to_first_name";
		$convert["shipping_address1"] = "x_ship_to_address";
		$convert["shipping_city"] = "x_ship_to_city";
		$convert["shipping_state"] = "x_ship_to_state";
		$convert["shipping_zip"] = "x_ship_to_zip";
		$convert["shipping_country"] = "x_ship_to_country";
	
		if (is_array($data["cc_exp"])) {
			$cc_exp = 
				sprintf("%02d", $data["cc_exp"]["month"])
				. "/" . $data["cc_exp"]["year"]
				;
			$data["cc_exp"] = $cc_exp;
		}

		//
		// When specified, place the gateway in test mode.
		//
		$convert["test_request"] = "x_test_request";

		foreach ($convert as $key => $value) {

			if (!empty($data[$key])) {

				//
				// Filter out any instances of our delimiter
				//
				$retval[$value] = ereg_replace(":", "", $data[$key]);

				//
				// Get the price set down to the cents.
				//
				if ($value == "x_amount") {
					$retval[$value] = sprintf("%.2f", $retval[$value]);
				}

			}
		}

		//
		// Authorize.net only supports a single address field.
		// 
		$retval["x_address"] .= " " . $data["address2"];
		$retval["x_ship_to_address"] .= " " . $data["shipping_address2"];

		//
		// Make note of what our customer's IP address is.
		//
		$retval["x_customer_ip"] = $_SERVER["REMOTE_ADDR"];

		return($retval);

	} // End of prepare_data()


	/**
	* Create our string to send to authrize.net as a POST-formatted string.
	*
	* @param array $fields Associative array of our data to send to 
	*	authorize.net.  The key is the field name that authorize.net expects
	*	and the value is the value.
	*
	* @return string A POST-formatted string.
	*/
	protected function get_post_string(&$fields) {

		$retval = "";
		$log_string = "";

		foreach ($fields as $key => $value) {

			$retval .= rawurlencode($key) . "=" . 
				rawurlencode($value) . "&";

			//
			// Sensitive values get filtered out.
			//
			if ($key == "x_card_num"
				|| $key == "x_card_code" 
				|| $key == "x_login" 
				|| $key == "x_tran_key"
				) {
				$value = "XXXX" . substr($value, -4);
			}

			$log_string .= rawurlencode($key) . "=" . 
				rawurlencode($value) . "&";

		}

		if (empty($fields["x_test_request"])) {
			$message = t("Authorize.net query: !string",
				array("!string" => $log_string));

		} else {
			$message = t("Authorize.net (TESTMODE) query: !string",
				array("!string" => $log_string));

		}

		$this->log->log($message);

		return($retval);

	} // End of get_post_string()


	/**
	* This function does the actual work of connecting to authorize.net,
	*	sending the data, and returning a response.
	*
	* If there are any issues, an exception is thrown.  It is up to the 
	*	caller to catch it!
	*
	* Pro-top: Calling the getMessage() method on the exception will get the
	*	error text for display to the user.
	*
	* @param array $fields Array of data to send to authorize.net.  The keys
	*	should be named in the x_fieldname convention that authorize.net uses.
	*
	* @return mixed On a successful connection to authorize.net, the string
	*	is returned.  On failure, false is returned and errors are logged.
	*/
	protected function send_data($fields) {

			$url = "https://secure.authorize.net/gateway/transact.dll";

			$fp = curl_init($url);

			if (empty($fp)) {
				$error = t("Unable to open connection to %url",
					array(
						"%url" => $url
					));
				$this->log->log($error, "", WATCHDOG_ERROR);
				throw new Exception($error);
			}

			$error = "";

			//
			// Don't display header info in the response
			//
			if (!curl_setopt($fp, CURLOPT_HEADER, 0)) {
				$option = array("%option" => "CURLOPT_HEADER");
				$error = t("Unable to set CURL option %option", $option);
				$this->log->log($error, "", WATCHDOG_ERROR);
				throw new Exception($error);
			}

			//
			// Make curl_exec() return the response data
			//
			if (!curl_setopt($fp, CURLOPT_RETURNTRANSFER, 1)) {
				$option = array("%option" => "CURLOPT_RETURNTRANSFER");
				$error = t("Unable to set CURL option %option", $option);
				$this->log->log($error, "", WATCHDOG_ERROR);
				throw new Exception($error);
			}

			if (!curl_setopt($fp, CURLOPT_POST, true)) {
				$option = array("%option" => "CURLOPT_POST");
				$error = t("Unable to set CURL option %option", $option);
				$this->log->log($error, "", WATCHDOG_ERROR);
				throw new Exception($error);
			}

			$post_string = $this->get_post_string($fields);

			if (!curl_setopt($fp, CURLOPT_POSTFIELDS, $post_string)) {
				$option = array("%option" => "CURLOPT_POSTFIELDS");
				$error = t("Unable to set CURL option %option", $option);
				$this->log->log($error, "", WATCHDOG_ERROR);
				throw new Exception($error);
			}

			//
			// If I uncomment this, the SSL certificate will not be
			// checked.
			//
			//if (!curl_setopt($fp, CURLOPT_SSL_VERIFYPEER, false)) {
			//	$option = array("%option" => "CURLOPT_SSL_VERIFYPEER");
			//	$this->log->log($error, "", WATCHDOG_ERROR);
			//	$error = t("Unable to set CURL option %option", $option);
			//	throw new Exception($error);
			//}

			$response = curl_exec($fp);

			if (empty($response)) {
				$error = t("curl_exec() returned false.  Error: !error",
					array(
						"!error" => curl_error($fp),
					));
				$this->log->log($error, "", WATCHDOG_ERROR);
				throw new Exception($error);

			} else {
				$message = t("Authorize.net response: " . $response);
				$this->log->log($message);

			}

			curl_close($fp);

			return($response);

	} // End of send_data()


	/**
	* Check to see if a result string from authorize.net indicates success.
	*
	* @param string $string The result from authorize.net.
	*
	* @return boolean True on success, false otherwise.
	*/
	protected function is_success($string) {

		if ($string[0] == "1") {
			return(true);
		}

		return(false);

	} // End of is_success()

	/**
	* Check to see if a result string from authorize.net indicates a 
	*	declined transaction.
	*
	* @param string $string The result from authorize.net.
	*
	* @return boolean True on a decline, false otherwise.
	*/
	protected function is_declined($string) {

		if ($string[0] == "2") {
			return(true);
		}

		return(false);

	}


	/**
	* Function to check and see if we are currently in test mode.
	*/
	public function is_test_mode() {

		$value = $this->variable_get(self::TEST_MODE);

		if (!empty($value)) {
			return(true);
		}

		return(false);

	} // End of is_test_mode()


	/**
	* Was there a bad CVV code?
	*
	* @param array $data The array of resposne values from authorize.net.
	*/
	protected function is_bad_cvv(&$data) {

		//
		// 65 - account was configured to reject this CVV response
		// 78 - invalid card code format
		//
		if ($data[2] == "65"
			|| $data[2] == "78") {
			return(true);
		}

		return(false);

	} // End of is_bad_cvv()


	/**
	* Was there a bad AVS code?
	*
	* @param array $data The array of response values from authorize.net.
	*/
	protected function is_bad_avs(&$data) {

		if ($data[2] == "27") {
			return(true);
		}

		return(false);

	} // End of is_bad_avs()


	/**
	* Our public function to charge a credit card number.
	*
	* @param array $data Associative array of customer and credit card 
	*	data.
	*
	* @return array Associative array of stats on the transaction.
	*	This includes things like AVS and CVV responses, and the all 
	*	important "status" field which will be either "success", 
	*	"declined", or "error".
	*/
	public function charge_cc($data) {

		$retval = array();

		$fields = $this->prepare_data($data);

		try {
			$response = $this->send_data($fields);
		} catch (Exception $e) {
			$retval["status"] = "error";
			return($retval);
		}

		//
		// Pull out key fields and put them into our return value.
		//
		$results = explode(":" , $response);

		if ($this->is_success($response)) {
			$retval["status"] = "success";

		} else if ($this->is_bad_avs($results)) {
			$retval["status"] = "bad avs";

		} else if ($this->is_bad_cvv($results)) {
			$retval["status"] = "bad cvv";

		} else if ($this->is_declined($response)) {
			$retval["status"] = "declined";

		} else {
			$retval["status"] = "error";

		}

		$retval["auth_code"] = $results[4];
		$retval["avs_response"] = $results[5];
		$retval["transaction_id"] = $results[6];
		$retval["cvv_response"] = $results[38];

		//
		// Send back raw data for curious programmers.
		//
		$retval["raw_response"] = $response;

		return($retval);

	} // End of charge_cc()


} // End of authorize_net class

