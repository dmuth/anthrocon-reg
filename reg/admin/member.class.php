<?php

/**
* This class handles all administrative functions that relate to members, 
*	such as updates, adding notes, etc.
*/
class reg_admin_member extends reg {


	function __construct(&$log, &$admin_log) {
		$this->log = $log;
		$this->admin_log = $admin_log;
		//
		// This here is a circular dependency that I need to fix at some point.
		//
		//$this->form = $factory->get_object("form");
	}


	/**
	* Display the most recent registrations.
	*
	* @return string HTML of the list of recent registrations.
	*/
	function recent() {

		$header = $this->get_member_table_header();

		//
		// By default, we'll be sorting by the reverse date.
		//
		$order_by = tablesort_sql($header);

		//
		// Select log entries with the username included.
		//
		$rows = array();
		$query = "SELECT reg.*, "
			. "reg_type.member_type, "
			. "reg_status.status "
			. "FROM {reg} "
			. "LEFT JOIN {reg_type} ON reg.reg_type_id = reg_type.id "
			. "LEFT JOIN {reg_status} ON reg.reg_status_id = reg_status.id "
			. "$order_by"
			;
                
		$cursor = pager_query($query, $this->get_constant("ITEMS_PER_PAGE"));
                
		while ($row = db_fetch_array($cursor)) {
			$rows[] = $this->get_member_table_row($row);
		}

		if (empty($rows)) {
			$message = t("No members found.");
			$rows[] = array(
				array(
					"data" => $message,
					"colspan" => count($header),
					)
				);
		}

		$retval = theme("table", $header, $rows);

		$retval .= theme_pager();

		return($retval);

	} // End of recent()


	/**
	* Return the table header for a member.
	*/
	function get_member_table_header() {

		$header = array();
		$header[] = array("data" => "Id #", "field" => "id",
			"sort" => "desc");
		$header[] = array("data" => "Badge #", "field" => "badge_num");
		$header[] = array("data" => "Badge Name", "field" => "badge_name");
		$header[] = array("data" => "Real Name");
		$header[] = array("data" => "Member Type", "field" => "member_type");
		$header[] = array("data" => "Status", "field" => "status");

		return($header);

	} // End of get_member_table_header()


	/**
	* Turn a member record into a row for the table.
	*
	* @param array $row Associative array of a single member
	*
	* @return array A row to be displayed in a table.
	*/
	function get_member_table_row(&$row) {

		$id = $row["id"];
		$badge_num = $row["badge_num"];
		$badge_name = $row["badge_name"];
		$real_name = $row["first"] . " " . $row["middle"] . " "
			. $row["last"];

		$link = "admin/reg/members/view/" . $id . "/view";
                        
		$retval = array(
			l($id, $link),
			l($row["year"] . "-" . $this->format_badge_num($badge_num), 
				$link),
			l($badge_name, $link),
			l($real_name, $link),
			$row["member_type"],
			$row["status"],
			);

		return($retval);

	} // End of get_member_table_row()


	/**
	* Load a single registration.
	*
	* @param integer $id The registration ID
	*
	* @return array Array of Registration info
	*/
	function load_reg($id) {

		$query = "SELECT reg.*, "
			. "reg_type.member_type, "
			. "reg_status.status, reg_status.detail "
			. "FROM {reg} "
			. "LEFT JOIN {reg_type} ON reg.reg_type_id = reg_type.id "
			. "LEFT JOIN {reg_status} ON reg.reg_status_id = reg_status.id "
			. "WHERE reg.id = '%s'"
			;
                
		$cursor = db_query($query, $id);
		$row = db_fetch_array($cursor);

		return($row);

	} // End of load_reg()


	/**
	* Pull up details on a specific record.	
	*
	* @return string HTML of the member to display.
	*/
	function view_reg($id) {

		$retval = "";

		$row = $this->load_reg($id);

		//
		// Now create our table.
		//
		$rows = array();

		$rows[] = array(
			array("data" => "Registration ID #", "header" => true),
			$row["id"]
			);

		$rows[] = array(
			array("data" => "Badge Number", "header" => true),
			$row["year"] . "-" . $this->format_badge_num($row["badge_num"])
			);

		$rows[] = array(
			array("data" => "Badge Name", "header" => true),
			$row["badge_name"]
			);

		$rows[] = array(
			array("data" => "Real Name", "header" => true),
			$row["first"] . " " . $row["middle"] . " " . $row["last"]
			);

		$rows[] = array(
			array("data" => "Birthdate", "header" => true),
			$this->get_date_string($row["birthdate"])
			);

		$rows[] = array(
			array("data" => "Address", "header" => true, "valign" => "top"),
			$row["address1"] . " " . $row["address2"] . "<br/>\n"
				. $row["city"] . ", " . $row["state"] . " " . $row["zip"] 
					. "<br/>\n"
				. $row["country"]
			);


		//
		// If we have any part of a shipping address, show it.
		//
		if (!empty($row["shipping_name"])
			||!empty($row["shipping_address1"])
			|| !empty($row["shipping_address2"])
			|| !empty($row["shipping_city"])
			|| !empty($row["shipping_state"])
			|| !empty($row["shipping_zip"])
			|| !empty($row["shipping_country"]) ) {

			$rows[] = array(
				array("data" => "Shipping Address", "header" => true, 
					"valign" => "top"),
				$row["shipping_name"] . "<br/>\n"
				. $row["shipping_address1"] . " " 
				. $row["shipping_address2"] . "<br/>\n"
				. $row["shipping_city"] . ", " . $row["shipping_state"] 
				. " " . $row["shipping_zip"] . "<br/>\n"
				. $row["shipping_country"]
			);

		}

		if (!empty($row["no_receipt"])) {
			$no_receipt = t("Do NOT send a paper receipt.");
		} else {
			$no_receipt = t("Paper receipt requested.");
		}

		$rows[] = array(
			array("data" => "No paper receipt?", "header" => false),
			$no_receipt
			);

		$rows[] = array(
			array("data" => "Email", "header" => false),
			$row["email"]
			);

		$rows[] = array(
			array("data" => "Phone", "header" => true),
			$row["phone"]
			);

		$shirt_sizes = $this->get_shirt_sizes();
		$rows[] = array(
			array("data" => "Shirt Size", "header" => true),
			$shirt_sizes[$row["shirt_size_id"]]
			);

		$rows[] = array(
			array("data" => "Membership Type", "header" => true),
			$row["member_type"]
			);

		$rows[] = array(
			array("data" => "Status", "header" => true),
			$row["status"] . " (" . $row["detail"] . ")"
			);

		$rows[] = array(
			array("data" => "Badge Cost Balance", "header" => true),
			"$" . $row["badge_cost"],
			);

		$rows[] = array(
			array("data" => "Donation Balance", "header" => true),
			"$" . $row["donation"],
			);

		$rows[] = array(
			array("data" => "Total Balance", "header" => true),
			"$" . $row["total_cost"],
			);

		$retval .= "<h2>Member Info</h2>";
		$retval .= theme("table", array(), $rows);

		//
		// Load up log entries and transactions for this user.
		//
		$retval .= "<h2>Log Entries</h2>";
		$retval .= $this->admin_log->log_recent($row["id"]);

		$retval .= "<h2>Transactions</h2>";
		$retval .= $this->admin_log->trans_recent($row["id"]);

		return($retval);

	} // End of view_reg()


	/**
	* Edit a current registration.
	*
	* @param integer $id The reg_id of the record to edit.
	*/
	function edit_reg($id) {

		$retval = "";

		//
		// Load our main registration form.
		//
		$retval .= "<h2>Edit Registration</h2>";
		$retval .= drupal_get_form("reg_registration_form", $id);

		return($retval);

	} // End of edit_reg()


	/**
	* This function is used to add a new registration.
	*/
	function add_reg($id = "") {

		$retval = "";

		$retval .= "<h2>Manually Add a Registration</h2>";
		$retval .= drupal_get_form("reg_registration_form");

		return($retval);

	} // End of add_reg()


	/**
	* This function updates an existing membership, and is only used by 
	* an admin.
	* 
	* @return integer The badge number of the member that we just updated.
	*/
	function update_member($data) {

		//
		// Assign a badge number if one was not entered.
		//
		if ($data["badge_num"] == "") {
			$data["badge_num"] = $this->get_badge_num();
			$message = t("New badge number generated");
			drupal_set_message($message);
		}

		//
		// Load our old data and unset certain values which are never updated
		// when a member is updated.
		//
		$query = "SELECT * FROM {reg} "
			. "WHERE id='%s'";
		$query_args = array($data["reg_id"]);
		$cursor = db_query($query, $query_args);
		$old_data = db_fetch_array($cursor);
		unset($old_data["id"]);
		unset($old_data["created"]);
		unset($old_data["modified"]);
		unset($old_data["year"]);
		unset($old_data["badge_cost"]);
		unset($old_data["donation"]);
		unset($old_data["total_cost"]);
		//
		// Also, put our birthdate in the same key as the new data uses.
		//
		$old_data["birthdate_string"] = $old_data["birthdate"];
		unset($old_data["birthdate"]);

		$query = "UPDATE {reg} "
			. "SET "
			. "modified=UNIX_TIMESTAMP(), reg_type_id='%s', reg_status_id='%s', "
			. "badge_num='%s', badge_name='%s', "
			. "first='%s', middle='%s', last='%s', "
				. "birthdate='%s', "
			. "address1='%s', address2='%s', city='%s', state='%s', "
			. "zip='%s', country='%s', "
			. "shipping_name='%s', "
			. "shipping_address1='%s', shipping_address2='%s', "
			. "shipping_city='%s', shipping_state='%s', "
			. "shipping_zip='%s', shipping_country='%s', "
			. "no_receipt='%s', "

			. "email='%s', phone='%s', "
			. "shirt_size_id='%s' "
			."WHERE id=%d ";

		$birth = $data["birthdate"];
		$data["birthdate_string"] = $this->get_date($birth["year"], 
			$birth["month"], $birth["day"]);

		$query_args = array(
			$data["reg_type_id"], $data["reg_status_id"],
			$data["badge_num"], $data["badge_name"],
			$data["first"], $data["middle"], $data["last"], 
				$data["birthdate_string"],
			$data["address1"], $data["address2"], $data["city"], 
				$data["state"],
			$data["zip"], $data["country"], 
			$data["shipping_name"],
			$data["shipping_address1"], $data["shipping_address2"], 
			$data["shipping_city"], $data["shipping_state"],
			$data["shipping_zip"], $data["shipping_country"], 
			$data["no_receipt"],

			$data["email"], $data["phone"],
			$data["shirt_size_id"],
			$data["reg_id"]
			);
		db_query($query, $query_args);

		$message = t("Updated registration for badge number '!num'",
				array("!num" => $data["badge_num"])
				);


		//
		// Create an audit log entry and write it out.
		//
		if (!empty($old_data)) {
			$message .= " " . $this->get_changed_data(
				$data, $old_data);
		}

		$factory = new reg_factory();
		$log = $factory->get_object("log");
		$log->log($message, $data["reg_id"]);

		return($data["badge_num"]);

	} // End of update_member()


	/**
	* Add a note to an existing member.
	*/
	function add_note($id) {

		$retval = "";

		$retval .= drupal_get_form("reg_admin_members_add_note_form", $id);

		return($retval);

	} // End of add_note()


	function add_note_form($id) {

		$retval = array();
		$data = $this->load_reg($id);

		$retval["reg_id"] = array(
			"#type" => "hidden",
			"#value" => $id,
			);

		$retval["note"] = array(
			"#value" => 
				t("Add a note about this user for our own use. ")
				. t("Examples: we received a chargeback for this user, etc. ")
			);

		$retval["badge_name"] = array(
			"#title" => "Badge Name",
			"#type" => "textfield",
			"#size" => reg_form::FORM_TEXT_SIZE_SMALL,
			"#default_value" => $data["badge_name"],
			"#disabled" => true,
			);

		$retval["badge_num"] = array(
			"#title" => "Badge Number",
			"#type" => "textfield",
			"#size" => reg_form::FORM_TEXT_SIZE_SMALL,
			"#default_value" => $data["badge_num"],
			"#disabled" => true,
			);

		$name = $data["first"]
			. " " . $data["middle"]
			. " " . $data["last"]
			;

		$retval["real_name"] = array(
			"#title" => "Name",
			"#type" => "textfield",
			"#size" => reg_form::FORM_TEXT_SIZE_SMALL,
			"#default_value" => $name,
			"#disabled" => true,
			);

		$retval["notes"] = array(
			"#title" => "Notes",
			"#description" => t("Enter as much as you like about this user. ")
				. t("They will NOT see your comments."),
			"#type" => "textarea",
			"#required" => true,
			);

		$retval["submit"] = array(
			"#type" => "submit",
			"#value" => t("Save Note")
			);

		return($retval);

	} // End of add_note_form()


	function add_note_form_validate($form_id, &$data) {
	} // End of add_note_form_validate()


	/**
	* Save the new note.
	*/ 
	function add_note_form_submit($form_id, &$data) {

		$reg_id = $data["reg_id"];
		$message = t("Added Note: ") . $data["notes"];

		$factory = new reg_factory();
		$log = $factory->get_object("log");
		$log->log($message, $reg_id);

		drupal_set_message(t("Log entry saved for this member."));

		//
		// Redirect the user back to the viewing page.
		//
		$uri = "admin/reg/members/view/" . $reg_id . "/view";
  		$this->goto_url($uri);

	} // End of add_note_form_submit()


} // End reg_admin_member

