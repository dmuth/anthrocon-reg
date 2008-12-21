<?php

/**
* This class is used to hold our log and transaction-related functions.
*
* The reason why this extends the reg class is because the reg class also
* depends on this class, and we can't have any circular dependencies.  
* That would be bad.
*/
class reg_log extends reg {

	/**
	* Our constructor.
	*/
	function __construct() {
	} // End of __construct()


	/**
	* This is our registration log function.  It contains a wrapper for
	* the Drupal watchdog() facility, but also logs entries via our own logging
	* table.  This way, we can keep track of log entries in the registration 
	* system for months, or even years if necessary.
	*
	* @param string $message The log message
	*
	* @param integer $reg_id An optional value for the registered user's ID.
	*
	* @param mixed $severity The severity of the message.  See Drupal's 
	*	watchdog() function docs for more details on this.
	*
	* @return integer The ID of the row that was inserted into the database.
	*
	*/
	function log($message, $reg_id = "", $severity = WATCHDOG_NOTICE) {

		global $user, $base_root;

		watchdog("reg", $message, $severity);

		//
		// Non-apache setups (nginx, SSL accelerators, etc.) cause $base_root
		// to not include SSL.  So we're going to do an additional check
		// and fix there here.
		//
		if ($this->is_ssl()) {
			$base_root = eregi_replace("^http://", "https://", $base_root);
		}

		$url = $base_root . request_uri();
		$query = "INSERT INTO {reg_log} "
			. "(reg_id, uid, date, url, "
			. "referrer, remote_addr, message, "
				. "severity) "
			. "VALUES "
			. "('%s', '%s', '%s', '%s', '%s', '%s', '%s', "
				. "'%s') "
			;
		$query_args = array($reg_id, $user->uid, time(), $url, 
			referer_uri(), $_SERVER["REMOTE_ADDR"], $message,
			$severity,
			);
		db_query($query, $query_args);

		$id = $this->get_insert_id();

		return($id);

	} // End of log()


	/**
	* This function logs a successful transaction.
	*
	* @TODO Support for different transaction types?
	*
	* @return integer the ID of the row that was inserted into the database.
	*/
	function log_trans(&$data) {

		global $user;

		$exp = $data["cc_exp"];
		//
		// Set each expire string to the first of the month, otherwise 
		// strttime() and friends will "correct" the date to be in the 
		// previous month!
		//
		$exp_string = 0;
		if (!empty($exp)) {
			$exp_string = $this->get_time_t($exp["year"], $exp["month"], 1);
		}

		//
		// Make sure we have actual numbers here, just in case.
		//
		if (empty($data["badge_cost"])) {
			$data["badge_cost"] = 0;
		}

		if (empty($data["donation"])) {
			$data["donation"] = 0;
		}

		//
		// We NEVER want to log the full credit card number.  That creates a
		// bunch of security concerns.
		//
		if (!empty($data["cc_num"])) {
			$data["cc_num"] = $this->get_cc_last_4($data["cc_num"]);
		}

		$data["total_cost"] = $data["badge_cost"] + $data["donation"];
		//
		// Save the successful charge in reg_trans.
		//
		$query = "INSERT INTO {reg_trans} ("
			. "uid, reg_id, "
			. "date, reg_trans_type_id, reg_payment_type_id, "
			. "reg_trans_gateway_id, "
			. "first, middle, last, address1, address2, "
			. "city, state, zip, country, "
			. "shipping_name, shipping_address1, shipping_address2, "
			. "shipping_city, shipping_state, shipping_zip, "
			. "shipping_country, "
			. "reg_cc_type_id, cc_num, card_expire, "
			. "badge_cost, donation, total_cost, "
			. "reg_log_id, gateway_auth_code, gateway_avs, gateway_cvv, "
			. "gateway_transaction_id, invoice_number "
			. ") VALUES ("
			. "'%s', '%s', "
			. "'%s', '%s', '%s', "
			. "'%s', "
			. "'%s', '%s', '%s', '%s', '%s', "
			. "'%s', '%s', '%s', '%s', "
			. "'%s', '%s', '%s', "
			. "'%s', '%s', '%s', "
			. "'%s', "

			. "'%s', '%s', '%s', "
			. "'%s', '%s', '%s', "
			. "'%s', '%s', '%s', '%s', "
			. "'%s', '%s' "
			. ")"
			;

		$query_args = array(
			$user->uid, $data["reg_id"], 
			time(), $data["reg_trans_type_id"], $data["reg_payment_type_id"],
			$data["reg_trans_gateway_id"],
			$data["first"], $data["middle"], $data["last"], 
				$data["address1"], $data["address2"],
			$data["city"], $data["state"], $data["zip"], $data["country"],
			$data["shipping_name"], $data["shipping_address1"], 
			$data["shipping_address2"], $data["shipping_city"], 
			$data["shipping_state"], $data["shipping_zip"], 
			$data["shipping_country"],
			$data["cc_type_id"], $data["cc_num"], $exp_string,
			$data["badge_cost"], $data["donation"], $data["total_cost"],
			$data["reg_log_id"], $data["gateway_auth_code"], 
				$data["gateway_avs"], $data["gateway_cvv"],
			$data["gateway_transaction_id"], $data["invoice_number"],
			);

		db_query($query, $query_args);

		//
		// Update our main registration record, if one is present.
		//
		if (!empty($data["reg_id"])) {
			$query = "UPDATE {reg} "
				. "SET "
				. "badge_cost = badge_cost + '%s', "
				. "donation = donation + '%s', "
				. "total_cost = total_cost + '%s' "
				. "WHERE "
				. "id='%s' ";
			$query_args = array($data["badge_cost"], $data["donation"],
				$data["total_cost"], $data["reg_id"]);
			db_query($query, $query_args);

		}


		$id = $this->get_insert_id();

		return($id);

	} // End of log_trans()


} // End of reg_log class


