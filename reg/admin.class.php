<?php

/**
* This is the reg_admin class, which holds functions related to the 
*	administrative end of the registration system.
*/
class reg_admin {

	/**
	* Our constructor.  This should never be called.
	*/
	function __construct() {
		$error = "You tried to instantiate this class even after I told "
			. "you not to!";
		throw new Exception($error);
	}


	/**
	* Our main admin page.
	*/
	static function settings() {

		$retval = "";
		$retval = drupal_get_form("reg_admin_settings_form");

		return($retval);

	} // End of admin()

	

	/**
	* This function creates the data structure for our main admin form.
	*
	* @return array Associative array of registration form.
	*/
	static function settings_form() {

		$retval = array();

		$retval["fake_cc"] = array(
			"#type" => "checkbox",
			"#title" => "Credit Card Test Mode?",
			"#default_value" => variable_get(reg_form::FORM_ADMIN_FAKE_CC, false),
			"#description" => "If set, credit card numbers will "
				. "not be processed.  Do NOT use in production!",
			);

		$retval["conduct_path"] = array(
			"#type" => "textfield",
			"#title" => "Standards of Conduct Path",
			"#default_value" => variable_get(reg_form::FORM_ADMIN_CONDUCT_PATH, ""),
			"#description" => "If a valid path is entered here, "
				. "the user will be forced to agree to the "
				. "Standards of Conduct before registering.  Do NOT use a "
				. "leading slash.",
			"#size" => reg_form::FORM_TEXT_SIZE,
			);

		$retval["submit"] = array(
			"#type" => "submit",
			"#value" => "Save"
			);

		return($retval);

	} // End of form()


	/**
	* This function is called to validate the form data.
	* If there are any issues, form_set_error() should be called so
	* that form processing does not continue.
	*/
	static function settings_form_validate(&$form_id, &$data) {

		//
		// If a path was entered, make sure it is a valid alias or
		// a valid node.
		//
		if (!empty($data["conduct_path"])) {
			if (!drupal_lookup_path("source", $data["conduct_path"])) {
				$results = explode("/", $data["conduct_path"]);
				$nid = $results[1];
				if (empty($nid) || !node_load($nid)) {
					form_set_error("conduct_path", 
						"Invalid path entered for Standards of Conduct");
				}
			}
		}

		//form_set_error("fake_cc", "test2");
		//print_r($data);

	} // End of form_validate()


	/**
	* This function is called after our form has been successfully validated.
	*
	* It should make any necessary changes to the database.  At the 
	* conclusion of this funciton, the user is redirected back to the 
	* form page.
	*/
	static function settings_form_submit($form_id, $data) {
		variable_set(reg_form::FORM_ADMIN_FAKE_CC, $data["fake_cc"]);
		variable_set(reg_form::FORM_ADMIN_CONDUCT_PATH, $data["conduct_path"]);
		drupal_set_message("Settings updated");
	}


	/**
	* Display the most recent registrations.
	*
	* @return string HTML of the list of recent registrations.
	*/
	static function recent() {

		$header = array();
		$header[] = array("data" => "Id #", "field" => "id",
			"sort" => "desc");
		$header[] = array("data" => "Badge #", "field" => "badge_num");
		$header[] = array("data" => "Badge Name", "field" => "badge_name");
		$header[] = array("data" => "Real Name");
		$header[] = array("data" => "Member Type", "field" => "member_type");
		$header[] = array("data" => "Status", "field" => "status");

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
                
		$cursor = pager_query($query, reg::ITEMS_PER_PAGE);
                
		while ($row = db_fetch_array($cursor)) {
			$id = $row["id"];
			$badge_num = $row["badge_num"];
			$badge_name = $row["badge_name"];
			$real_name = $row["first"] . " " . $row["middle"] . " "
				. $row["last"];

			$link = "admin/reg/members/view/" . $id . "/view";
                        
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

			$rows[] = array(
				l($id, $link),
				l($badge_num, $link),
				l($badge_name, $link),
				$real_name,
				$row["member_type"],
				$row["status"],
				);
                
		}
                
		$retval = theme("table", $header, $rows);

		$retval .= theme_pager();

		return($retval);

	} // End of recent()


	/**
	* Load a single registration.
	*
	* @param integer $id The registration ID
	*
	* @return array Array of Registration info
	*/
	static function load_reg($id) {

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
	static function view_reg($id) {

		$retval = "";

		$row = self::load_reg($id);

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
			$row["year"] . "-" . sprintf("%04d", $row["badge_num"])
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
			$row["birthdate"]
			);

		$rows[] = array(
			array("data" => "Address", "header" => true, "valign" => "top"),
			$row["address1"] . " " . $row["address2"] . "<br>\n"
				. $row["city"] . ", " . $row["state"] . " " . $row["zip"] 
					. "<br>\n"
				. $row["country"]
			);

		$rows[] = array(
			array("data" => "Email", "header" => false),
			$row["email"]
			);

		$rows[] = array(
			array("data" => "Phone", "header" => true),
			$row["phone"]
			);

		$shirt_sizes = reg::get_shirt_sizes();
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

		$retval .= "<h2>Member Info</h2>";
		$retval .= theme("table", array(), $rows);

		//
		// Load up log entries and transactions for this user.
		//
		$retval .= "<h2>Log Entries</h2>";
		$retval .= reg_log::log_recent($row["id"]);

		$retval .= "<h2>Transactions</h2>";
		$retval .= reg_log::trans_recent($row["id"]);

		return($retval);

	} // End of view_reg()


	/**
	* Edit a current registration.
	*
	* @param integer $id The reg_id of the record to edit.
	*/
	static function edit_reg($id) {

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
	static function add($id = "") {

		$retval = "";

		$retval .= "<h2>Manually Add a Registration</h2>";
		$retval .= drupal_get_form("reg_registration_form");

		return($retval);

	} // End of update()


} // End of reg_admin class

