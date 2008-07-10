<?php

/**
* This class is used to hold our log and transaction-related functions.
*/
class reg_log {


	/**
	* Our log viewer.
	*
	* @param integer $id Optional registration ID to limit results
	*	to a single membership.
	*
	* @return string HTML code of the log entry.
	*/
	function log($id = "") {

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
		if (!empty($id)) {
			$where = "WHERE reg_log.reg_id='" . db_escape_string($id) . "' ";
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
		$cursor = db_query($query);
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
			
			$link = "admin/reg/logs/view/" . $id;
			$rows[] = array(
				format_date($row["date"], "small"),
				l($row["message"], $link),
				$user_link,
				);
		}

		$retval = theme("table", $header, $rows);

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
			. "users.uid, users.name "
			. "FROM {reg_log} "
			. "LEFT JOIN {users} ON reg_log.uid = users.uid "
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
			
		$rows = array();
		$rows[] = array(
			array("data" => "Registration Log ID#", "header" => true),
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
	* View our transactions.
	*
	* @param integer $id Optional registration ID to limit results
	*	to a single membership.
	*
	* @return string HTML code.
	*/
	function trans($id = "") {

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
		if (!empty($id)) {
			$where = "WHERE reg_trans.reg_id='" . db_escape_string($id) . "' ";
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
		$cursor = db_query($query);
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
				$date_string,
				$row["payment_type"],
				$row["trans_type"],
				l("$" . $row["badge_cost"], $link),
				l("$" . $row["donation"], $link),
				l("$" . $row["total_cost"], $link),
				$user_link,
				);
		}

		$retval = theme("table", $header, $rows);

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


