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
	* List membership levels.
	*/
	static function levels() {

		$retval = "";

		//
		// Our table header, defaulted to sorting by year
		//
		$header = array();
		$header[] = array("data" => "ID #", "field" => "id",);
		$header[] = array("data" => "Name", "field" => "name");
		$header[] = array("data" => "Year", "field" => "year", "sort" => "desc");
		$header[] = array("data" => "Price", "field" => "price");
		$header[] = array("data" => "Membership Type", "field" => "member_type");
		$header[] = array("data" => "Start Date", "field" => "start");
		$header[] = array("data" => "End Date", "field" => "end");
		$header[] = array("data" => " ");

		$order_by = tablesort_sql($header);

		$rows = array();
		$query = "SELECT {reg_levels}.*, {reg_type}.member_type "
			. "FROM {reg_levels} "
			. "JOIN {reg_type} ON {reg_levels}.reg_type_id={reg_type}.id "
			. "$order_by";
		$cursor = db_query($query);
		while ($row = db_fetch_array($cursor)) {
			$rows[] = array($row["id"], $row["name"], $row["year"], 
				array("data" => "$" . $row["price"], "align" => "right"), 
				$row["member_type"], 
				$row["start"], $row["end"],
				l("Edit", "admin/reg/levels/edit/" . $row["id"]),
				);
		}

		$retval = theme("table", $header, $rows);
		return($retval);

	} // End of levels()


	/**
	* Add/edit a new membership level.
	*/
	static function levels_edit($id) {

		$retval = drupal_get_form("reg_admin_level_form", $id);
		return($retval);

	} // End of levels_add()


	/**
	* Create our level for adding/editing a form.
	*/
	static function level_form($id) {

		$retval = array();
		$row = array();

		if (empty($id)) {
			$title = "Add New Membership Level";

		} else {
			$title = "Edit Membership Level ID '$id'";

			//
			// Retrieve our existing row of data.
			//
			$query = "SELECT * FROM {reg_levels} WHERE id='%d'";
			$args = array($id);
			$cursor = db_query($query, $args);
			$row = db_fetch_array($cursor);

			$retval["id"] = array(
				"#title" => "id",
				"#type" => "hidden",
				"#value" => $id,
				);
		}

		drupal_set_title($title);

		//
		// TODO: Warn the user if there are any existing memberships
		//	purchaed with this level.
		//

		$retval["name"]  = array(
			"#title" => "Level Name",
			"#description" => "What the user sees.  i.e. Attending, Sponsor, etc.",
			"#type" => "textfield",
			"#size" => reg::FORM_TEXT_SIZE,
			"#required" => true,
			"#default_value" => $row["name"],
			);

		$retval["year"] = array(
			"#title" => "Convention Year",
			"#description" => "This is so that we can keep *proper* historic "
				. "data from past years.",
			"#type" => "textfield",
			"#size" => reg::FORM_TEXT_SIZE_SMALL,
			"#required" => true,
			"#default_value" => $row["year"] ? $row["year"] : date("Y"),
			);

		$types = reg::get_types();
		$retval["reg_type_id"] = array(
			"#title" => "Membership Type",
			"#description" => "The type of membership.  The user does NOT see this.",
			"#type" => "select",
			"#options" => $types,
			"#required" => true,
			"#default_value" => $row["reg_type_id"],
			);

		$retval["price"] = array(
			"#title" => "Price",
			"#description" => "The price of this membership",
			"#type" => "textfield",
			"#size" => reg::FORM_TEXT_SIZE_SMALL,
			"#required" => true,
			"#default_value" => $row["price"],
			);

		$retval["start"] = array(
			"#title" => "Starting Date",
			"#description" => "The membership will be available to the public "
				. "on or after this date.",
			"#type" => "date",
			"#required" => true,
			);
		if (!empty($id)) {
			$start = explode("-", $row["start"]);
			$start_date = array(
				"year" => (int)$start[0], 
				"month" => (int)$start[1], 
				"day" => (int)$start[2]
				);
			$retval["start"]["#default_value"] = $start_date;
		}


		$retval["end"] = array(
			"#title" => "End Date",
			"#description" => "After 11:59 PM on this date, this membership "
				. "will no logner be available to the public.",
			"#type" => "date",
			"#required" => true,
			);
		if (!empty($id)) {
			$end = explode("-", $row["end"]);
			$end_date = array(
				"year" => (int)$end[0], 
				"month" => (int)$end[1], 
				"day" => (int)$end[2]
				);
			$retval["end"]["#default_value"] = $end_date;
		}

		$retval["submit"] = array(
			"#type" => "submit",
			"#value" => "Save"
			);

		return($retval);

	} // End of level_form()


	/**
	* This function validates a submitted level form.
	*/
	function level_form_validate($form_id, &$data) {

		//
		// Make sure our year and price are numbers
		//
		$year = intval($data["year"]);
		if ($data["year"] != (string)$year) {
			form_set_error("year", "Year must be a number!");
		}

		$price = floatval($data["price"]);
		if ($data["price"] != (string)$price) {
			form_set_error("price", "Price must be a number!");
		}

		//
		// Check our data order
		//
		$start = $data["start"];
		$start_string = $start["year"] . "-" . $start["month"] 
			.  "-" . $start["day"];
		$start_date = strtotime($start_string);

		$end = $data["end"];
		$end_string = $end["year"] . "-" . $end["month"] 
			.  "-" . $end["day"];
		$end_date = strtotime($end_string);

		if ($start_date > $end_date) {
			$error = "Start date is after end date!";
			form_set_error("start][day", $error);
		}

	} // End of level_form_validate()


	/**
	* Everything in the form checks out, save the data.
	*/
	function level_form_submit($form_id, $data) {

		//
		// Turn the data arrays into strings
		//
		$start = $data["start"];
		$start_string = $start["year"] . "-" . $start["month"] 
			.  "-" . $start["day"];

		$end = $data["end"];
		$end_string = $end["year"] . "-" . $end["month"] 
			.  "-" . $end["day"];

		//
		// Create an insert or an update, depending on if we have an ID
		// present.
		//
		if (empty($data["id"])) {
			$query = "INSERT INTO {reg_levels} "
				. "(name, year, reg_type_id, price, start, end) "
				. "VALUES ('%s', '%s', '%s', '%s', '%s', '%s')";
			$args = array($data["name"], $data["year"], $data["reg_type_id"],
				$data["price"], $start_string, $end_string);

		} else {
			$query = "UPDATE {reg_levels} "
				. "SET "
				. "name='%s', year='%s', reg_type_id='%s', price='%s', "
				. "start='%s', end='%s' "
				. "WHERE "
				. "id='%d'";
			$args = array($data["name"], $data["year"], $data["reg_type_id"],
				$data["price"], $start_string, $end_string, $data["id"]);

		}
		
		db_query($query, $args);

		//
		// If we just inserted a row, fetch the ID.  Also prepare a message,
		// then go back to the main list of levels.
		//
		if (empty($data["id"])) {
			$cursor = db_query("SELECT LAST_INSERT_ID() AS id");
			$row = db_fetch_array($cursor);
			$id = $row["id"];

			$message = "Membership Level ID '${id}' saved!";

		} else {
			$id = $data["id"];
			$message = "Membership Level ID '${id}' updated!";

		}

		drupal_set_message($message);

		drupal_goto("admin/reg/levels");

	} // End of level_form_submit()


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
			"#default_value" => variable_get(reg::FORM_ADMIN_FAKE_CC, false),
			"#description" => "If set, credit card numbers will "
				. "not be processed.  Do NOT use in production!",
			);

		$retval["conduct_path"] = array(
			"#type" => "textfield",
			"#title" => "Standards of Conduct Path",
			"#default_value" => variable_get(reg::FORM_ADMIN_CONDUCT_PATH, ""),
			"#description" => "If a valid path is entered here, "
				. "the user will be forced to agree to the "
				. "Standards of Conduct before registering.  Do NOT use a "
				. "leading slash.",
			"#size" => reg::FORM_TEXT_SIZE,
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
		variable_set(reg::FORM_ADMIN_FAKE_CC, $data["fake_cc"]);
		variable_set(reg::FORM_ADMIN_CONDUCT_PATH, $data["conduct_path"]);
		drupal_set_message("Settings updated");
	}

} // End of reg_admin class

