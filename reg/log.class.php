<?php

/**
* This class is used to hold our log and transaction-related functions.
*/
class reg_log {


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
	*/
	static function log($message, $reg_id = "", $severity = WATCHDOG_NOTICE) {

		global $user, $base_root;

		watchdog("reg", $message, $severity);

		$url = $base_root . request_uri();
		$query = "INSERT INTO {reg_log} "
			. "(reg_id, uid, date, url, referrer, remote_addr, message) "
			. "VALUES "
			. "('%s', '%s', '%s', '%s', '%s', '%s', '%s') "
			;
		$query_args = array($reg_id, $user->uid, time(), $url, 
			referer_uri(), $_SERVER["REMOTE_ADDR"], $message
			);
		db_query($query, $query_args);

	} // End of log()


	/**
	* Our log viewer.
	*
	* @param integer $id Optional registration ID to limit results
	*	to a single membership.
	*
	* @return string HTML code of the log entry.
	*/
	function log_recent($id = "") {

		$header = array();
		$header[] = array("data" => "Date", "field" => "date",
			"sort" => "desc");
		$header[] = array("data" => "Message", "field" => "message");
		$header[] = array("data" => "User", "field" => "name");

		//
		// By default, we'll be sorting by the reverse date.
		//
		$order_by = tablesort_sql($header);

		$where = "";
		$where_args = array();
		if (!empty($id)) {
			$where = "WHERE reg_log.reg_id='%s' ";
			$where_args[] = $id;
		}

		//
		// Fetch our log entries and loop through them
		//
		$rows = array();
		$query = "SELECT reg_log.*, "
			. "users.uid, users.name "
			. "FROM {reg_log} "
			. "LEFT JOIN {users} ON reg_log.uid = users.uid "
			. $where
			. $order_by
			;
		$cursor = pager_query($query, reg::ITEMS_PER_PAGE, 0, null, 
			$where_args);
		while ($row = db_fetch_array($cursor)) {
			$id = $row["id"];

			//
			// Stick in the username if we have it.
			//
			$username = $row["name"];
			if (!empty($row["name"])) {
				$uid = $row["uid"];
				$user_link = l($username, "user/" . $uid);

			} else {
				$user_link = "Anonymous";

			}
			
			$max_len = 60;

			$link = "admin/reg/logs/view/" . $id;
			$date = format_date($row["date"], "small");
			$message = truncate_utf8($row["message"], $max_len);
			if (strlen($row["message"]) > $max_len) {
				$message .= "...";
			}

			$rows[] = array(
				l($date, $link),
				l($message, $link),
				$user_link,
				);
		}

		$retval = theme("table", $header, $rows);

		$retval .= theme_pager();

		return($retval);

	} // End of log()


	/**
	* Pull up details for a single row.
	*
	* @param integer $id The ID from the reg_log table.
	*
	* @return string HTML code of the log entry.
	*/
	function log_detail($id) {

		$query = "SELECT reg_log.*, "
			. "reg.badge_num, reg.badge_name, "
			. "reg.first, reg.middle, reg.last, "
			. "users.uid, users.name "
			. "FROM {reg_log} "
			. "LEFT JOIN {users} ON reg_log.uid = users.uid "
			. "LEFT JOIN {reg} ON reg_log.reg_id = reg.id "
			. "WHERE "
			. "reg_log.id='%s' ";
		$query_args = array($id);
		$cursor = db_query($query, $query_args);
		$row = db_fetch_array($cursor);
		$row["url"] = check_url($row["url"]);
		$row["referrer"] = check_url($row["referrer"]);

		//
		// Stick in the username if we have it.
		//
		$username = $row["name"];
		if (!empty($row["name"])) {
			$uid = $row["uid"];
			$user_link = l($username, "user/" . $uid);

		} else {
			$user_link = "Anonymous";

		}
			
		if (!empty($row["reg_id"])) {
			$member_link = "admin/reg/members/view/" 
				. $row["reg_id"] . "/view";
		}

		$rows = array();
		$rows[] = array(
			array("data" => "Registration Log ID#", "header" => true),
			$row["id"]
			);
		$rows[] = array(
			array("data" => "Date", "header" => true),
			format_date($row["date"], "small"),
			);

		if (!empty($row["badge_num"])) {
			$rows[] = array(
				array("data" => "Badge Number", "header" => true),
				l($row["badge_num"], $member_link)
				);
		}

		if (!empty($row["badge_name"])) {
			$rows[] = array(
				array("data" => "Badge Name", "header" => true),
				l($row["badge_name"], $member_link)
				);
		}

		if (!empty($row["first"])) {
			$name = $row["first"] . " " 
				. $row["middle"] . " " . $row["last"];
			$rows[] = array(
				array("data" => "Real Name", "header" => true),
				l($name, $member_link)
				);
		}

		$rows[] = array(
			array("data" => "Location", "header" => true),
			"<a href=\"" . $row["url"] . "\">" . $row["url"] . "</a>",
			);
		$rows[] = array(
			array("data" => "Referrer", "header" => true),
			"<a href=\"" . $row["referrer"] . "\">" 
				. $row["referrer"] . "</a>",
			);
		$rows[] = array(
			array("data" => "User", "header" => true),
			$user_link
			);
		$rows[] = array(
			array("data" => "Message", "header" => true),
			$row["message"]
			);
		$rows[] = array(
			array("data" => "Hostname", "header" => true),
			$row["remote_addr"]
			);

		$retval = theme("table", array(), $rows);
		return($retval);

	} // End of log_detail()


	/**
	* This function logs a successful transaction.
	*
	* @TODO Support for different transaction types?
	*
	* @return integer the ID of the row that was inserted into the database.
	*/
	static function log_trans(&$data) {

		global $user;

		//
		// Save the successful charge in reg_trans.
		//
		$query = "INSERT INTO reg_trans ("
			. "uid, "
			. "date, reg_trans_type_id, reg_payment_type_id, "
			. "first, middle, last, address1, address2, "
			. "city, state, zip, country, "
			. "reg_cc_type_id, cc_num, card_expire, "
			. "badge_cost, donation, total_cost "
			. ") VALUES ("
			. "'%s', "
			. "'%s', '%s', '%s', "
			. "'%s', '%s', '%s', '%s', '%s', "
			. "'%s', '%s', '%s', '%s', "
			. "'%s', '%s', '%s', "
			. "'%s', '%s', '%s' "
			. ")"
			;
		$exp = $data["cc_exp"];
		$exp_string = $exp["year"] . "-" . $exp["month"] ."-0";

		$data["cc_name"] = reg_data::get_cc_name($data["cc_type"], $data["cc_num"]);
		$query_args = array(
			$user->uid, 
			time(), 1, 1,
			$data["first"], $data["middle"], $data["last"], 
				$data["address1"], $data["address2"],
			$data["city"], $data["state"], $data["zip"], $data["country"],
			$data["cc_type"], $data["cc_name"], $exp_string,
			$data["badge_cost"], $data["donation"], $data["total_cost"]
			);

		db_query($query, $query_args);

		$id = reg_data::get_insert_id();

		return($id);

	} // End of log_trans()


	/**
	* View our transactions.
	*
	* @param integer $id Optional registration ID to limit results
	*	to a single membership.
	*
	* @return string HTML code.
	*/
	function trans_recent($id = "") {

		$retval = "";

		$header = array();
		$header[] = array("data" => "Date", "field" => "date",
			"sort" => "desc");
		$header[] = array("data" => "Payment Type", "field" => "name");
		$header[] = array("data" => "Transaction Type", "field" => "name");
		$header[] = array("data" => "Amount", "field" => "name");
		$header[] = array("data" => "Donation", "field" => "name");
		$header[] = array("data" => "Total", "field" => "name");
		$header[] = array("data" => "User", "field" => "name");

		//
		// By default, we'll be sorting by the reverse date.
		//
		$order_by = tablesort_sql($header);

		$where = "";
		$where_args = array();
		if (!empty($id)) {
			$where = "WHERE reg_trans.reg_id='%s' ";
			$where_args[] = $id;
		}

		//
		// Select log entries with the username included.
		//
		$rows = array();
		$query = "SELECT reg_trans.*, "
			. "reg_payment_type.payment_type, "
			. "reg_trans_type.trans_type, "
			. "users.uid, users.name "
			. "FROM {reg_trans} "
			. "LEFT JOIN {reg_trans_type} "
				. "ON reg_trans_type_id = reg_trans_type.id "
			. "LEFT JOIN {reg_payment_type} "
				. "ON reg_payment_type_id = reg_payment_type.id "
			. "LEFT JOIN {users} ON reg_trans.uid = users.uid "
			. $where
			. $order_by
			;
		$cursor = pager_query($query, reg::ITEMS_PER_PAGE, 0, null, 
			$where_args);
		while ($row = db_fetch_array($cursor)) {

			$id = $row["id"];

			//
			// Stick in the username if we have it.
			//
			$username = $row["name"];
			if (!empty($row["name"])) {
				$uid = $row["uid"];
				$user_link = l($username, "user/" . $uid);

			} else {
				$user_link = "Anonymous";

			}
			
			$link = "admin/reg/transactions/view/" . $id;
			$date_string = format_date($row["date"], "small");
			$rows[] = array(
				l($date_string, $link),
				$row["payment_type"],
				$row["trans_type"],
				l("$" . $row["badge_cost"], $link),
				l("$" . $row["donation"], $link),
				l("$" . $row["total_cost"], $link),
				$user_link,
				);
		}

		$retval = theme("table", $header, $rows);

		$retval .= theme_pager();

		return($retval);

	} // End of trans()


	/**
	* Pull up details for a single transaction.
	*
	* @param integer $id The ID from the reg_transacion table.
	*
	* @return string HTML code of the log entry.
	*/
	function trans_detail($id) {

		$query = "SELECT reg_trans.*, "
			. "reg_payment_type.payment_type, "
			. "reg_trans_type.trans_type, "
			. "users.uid, users.name "
			. "FROM {reg_trans} "
			. "LEFT JOIN {reg_trans_type} "
				. "ON reg_trans_type_id = reg_trans_type.id "
			. "LEFT JOIN {reg_payment_type} "
				. "ON reg_payment_type_id = reg_payment_type.id "
			. "LEFT JOIN {users} ON reg_trans.uid = users.uid "
			. "WHERE "
			. "reg_trans.id='%s' ";
		$query_args = array($id);
		$cursor = db_query($query, $query_args);
		$row = db_fetch_array($cursor);
		$row["url"] = check_url($row["url"]);
		$row["referrer"] = check_url($row["referrer"]);

		//
		// Stick in the username if we have it.
		//
		$username = $row["name"];
		if (!empty($row["name"])) {
			$uid = $row["uid"];
			$user_link = l($username, "user/" . $uid);

		} else {
			$user_link = "Anonymous";

		}
			
		$rows = array();
		$rows[] = array(
			array("data" => "Transaction Log ID#", "header" => true),
			$row["id"]
			);
		$rows[] = array(
			array("data" => "Date", "header" => true),
			format_date($row["date"], "small"),
			);
		$rows[] = array(
			array("data" => "User", "header" => true),
			$user_link
			);

		$name = $row["first"] . " " . $row["middle"] . " " . $row["last"];
		$rows[] = array(
			array("data" => "Name", "header" => true),
			$name
			);
		$address = $row["address1"] . "<br>\n"
			. $row["address2"] . "<br>\n"
			. $row["city"] . ", " . $row["state"] . " " . $row["zip"] 
				. "<br>\n"
			. $row["country"]
			;
		$rows[] = array(
			array("data" => "Address", "header" => true, "valign" => "top"),
			$address
			);

		$rows[] = array(
			array("data" => "Payment Type", "header" => true),
			$row["payment_type"]
			);
		$rows[] = array(
			array("data" => "Credit Card", "header" => true),
			$row["cc_num"]
			);
		$rows[] = array(
			array("data" => "Transaction Type", "header" => true),
			$row["trans_type"]
			);
		$rows[] = array(
			array("data" => "Amount", "header" => true),
			"$" . $row["badge_cost"]
			);
		$rows[] = array(
			array("data" => "Donation", "header" => true),
			"$" . $row["donation"]
			);
		$rows[] = array(
			array("data" => "Total Cost", "header" => true),
			"$" . $row["total_cost"]
			);

		$retval = theme("table", array(), $rows);
		return($retval);

	} // End of trans_detail()


} // End of reg_log class


