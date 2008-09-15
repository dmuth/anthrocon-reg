<?php

/**
* This class handles our member-centric functions, such as adding, viewing,
*	and editing members.
*/
class reg_member {


	/**
	* This function actually does the dirty work of adding a new member to
	* the system.  It is assumed that any credit card charging has been done.
	*
	* @param integer $reg_trans_id An option ID of the associated transaction
	*	stored in the reg_trans table.  This is so that the transaction can
	*	be updated with the ID from the reg table.
	*
	* @return integer The badge number of the member that we just added.
	*/
	static function add_member(&$data, $reg_trans_id = "") {

		//
		// If there is no badge number specififed OR we are in the
		// public interface, automatically generate a badge number.
		// Otherwise, we'll accept the admin-specified one.
		//
		if (
			(	empty($data["badge_num"])
				&& $data["badge_num"] != "0"
			)
			|| !reg_form::in_admin()) {
			$data["badge_num"] = reg_data::get_badge_num();

		}

		$query = "INSERT INTO {reg} "
			. "(created, modified, year, reg_type_id, reg_status_id, "
				. "badge_num, "
				. "badge_name, first, middle, last, "
				. "birthdate, "
				. "address1, address2, city, state, zip, country, email, "
				. "phone, shirt_size_id "
			. ") "
			. "VALUES "
			. "(UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), '%s', '%s', '%s', "
				. "'%s', "
				. "'%s', '%s', '%s', '%s', "
				. "'%s', "
				. "'%s', '%s', '%s', '%s', '%s', '%s', '%s', "
				. "'%s', '%s')"
			;

		$birth = $data["birthdate"];
		$date_string = reg_data::get_date($birth["year"], $birth["month"], 
			$birth["day"]);

		if (empty($data["reg_type_id"])) {
			$data["reg_type_id"] = reg_data::get_reg_type_id(
				$data["reg_level_id"]);
		}

		$query_args = array(reg::YEAR, $data["reg_type_id"], 1, $data["badge_num"], 
			$data["badge_name"], $data["first"], $data["middle"], 
			$data["last"], $date_string, $data["address1"], 
			$data["address2"], $data["city"], $data["state"], $data["zip"],
			$data["country"], $data["email"], $data["phone"],
			$data["shirt_size_id"]
			);
		db_query($query, $query_args);

		$data["id"] = reg_data::get_insert_id();

		$message = t("Added registration for badge number '!num'",
			array("!num" => $data["badge_num"])
			);
		reg_log::log($message, $data["id"]);

		//
		// Make a note in the just-written transaction what the member's ID is.
		//
		if (!empty($reg_trans_id)) {
			$query = "UPDATE {reg_trans} "
				. "SET "
				. "reg_id='%s' "
				. "WHERE "
				. "id='%s'";
			$query_args = array($data["id"], $reg_trans_id);
			db_query($query, $query_args);

		}

		//
		// Load that row from the transactions table to get the amounts of
		// money that were involved, then update the balances foro the main
		// registration record.
		//
		$query = "SELECT * "
			. "FROM {reg_trans} "
			. "WHERE "
			. "id='%s' ";
		$query_args = array($reg_trans_id);
		$cursor = db_query($query, $query_args);
		$trans_data = db_fetch_array($cursor);

		$query = "UPDATE {reg} "
			. "SET "
			. "badge_cost = badge_cost + '%s', "
			. "donation = donation + '%s', "
			. "total_cost = total_cost + '%s' "
			. "WHERE "
			. "id='%s' "
			;
		$query_args = array($trans_data["badge_cost"], $trans_data["donation"], 
			$trans_data["total_cost"], $data["id"]);
		db_query($query, $query_args);

		//
		// Save our cost into the data array so the caller can make
		// use of it.
		//
		$data["total_cost"] = $trans_data["total_cost"];

		self::email_receipt($data);

		return($data["badge_num"]);

	} // End of add_member()


	/**
	* Email out our registration receipt.
	*/
	static function email_receipt(&$data) {

        //
		// If we have credit card data, get a nice string.
		//
		if (!empty($data["cc_type_id"])
			&& !reg_form::in_admin()
			) {
			$message_name = "email-receipt";
			$data["cc_name"] = reg_data::get_cc_name($data["cc_type_id"],
			$data["cc_num"]);

		} else {
			$message_name = "email-receipt-no-cc";

		}

		$email_data = array(
			"!name" => $data["first"] . " " . $data["middle"] . " "
				. $data["last"],
			"!badge_num" => $data["badge_num"],
			"!cc_name" => $data["cc_name"],
			"!total_cost" => $data["total_cost"],
			);

		$message = new reg_message();
		$log = new reg_log();
		$email = new reg_email($message, $log);
		$email_sent = $email->email($data["email"], t("Your Receipt"), 
			$message_name, $data["id"], $email_data);

	} // End of send_email()


} // End of reg_member class

