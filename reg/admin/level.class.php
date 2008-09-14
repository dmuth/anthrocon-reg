<?php

/**
* This class holds functions that relate to registration levels.
*/
class reg_admin_level {

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
		$query = "SELECT {reg_level}.*, {reg_type}.member_type "
			. "FROM {reg_level} "
			. "JOIN {reg_type} ON {reg_level}.reg_type_id={reg_type}.id "
			. "$order_by";
		$cursor = db_query($query);

		$date_format = "r";

		while ($row = db_fetch_array($cursor)) {

			$link = "admin/reg/levels/list/" . $row["id"] . "/edit";
			$rows[] = array(
				l($row["id"], $link),
				l($row["name"], $link),
				l($row["year"], $link),
				array(
					"data" => l("$" . $row["price"], $link), 
					"align" => "right"
					), 
				l($row["member_type"], $link), 
				format_date($row["start"], "custom", $date_format),
				format_date($row["end"], "custom", $date_format),
				);
		}

		if (empty($rows)) {
			$message = t("No levels found.");
			$rows[] = array(
				array(
					"data" => $message,
					"colspan" => count($header),
					)
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
	* Load a specific level by ID, and return the resulting row as an array.
	*/
	static function load($id) {

		$query = "SELECT * FROM {reg_level} WHERE id='%d'";
		$args = array($id);
		$cursor = db_query($query, $args);
		$row = db_fetch_array($cursor);

		return($row);

	} // End of load()


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

			$row = self::load($id);

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
			"#size" => reg_form::FORM_TEXT_SIZE,
			"#required" => true,
			"#default_value" => $row["name"],
			);

		$retval["year"] = array(
			"#title" => "Convention Year",
			"#description" => "This is so that we can keep *proper* historic "
				. "data from past years.",
			"#type" => "textfield",
			"#size" => reg_form::FORM_TEXT_SIZE_SMALL,
			"#required" => true,
			"#default_value" => $row["year"] ? $row["year"] : date("Y"),
			);

		$types = reg_data::get_types();
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
			"#size" => reg_form::FORM_TEXT_SIZE_SMALL,
			"#required" => true,
			"#default_value" => $row["price"],
			);

		$retval["start"] = array(
			"#title" => "Starting Date",
			"#description" => "The level will be available to the public "
				. "on or after this date.",
			"#type" => "date",
			"#required" => true,
			);
		if (!empty($id)) {
			$start = explode("-", $row["start"]);
			$start_date = array(
				"year" => format_date($row["start"], "custom", "Y"),
				"month" => format_date($row["start"], "custom", "n"),
				"day" => format_date($row["start"], "custom", "j"),
				);
			$retval["start"]["#default_value"] = $start_date;
		}


		$retval["end"] = array(
			"#title" => "End Date",
			"#description" => "After 11:59 PM on this date, this level "
				. "will no logner be available to the public.",
			"#type" => "date",
			"#required" => true,
			);
		if (!empty($id)) {
			$end = explode("-", $row["end"]);
			$end_date = array(
				"year" => format_date($row["end"], "custom", "Y"),
				"month" => format_date($row["end"], "custom", "n"),
				"day" => format_date($row["end"], "custom", "j"),
				);
			$retval["end"]["#default_value"] = $end_date;
		}


		$retval["description"] = array(
			"#title" => "Description",
			"#description" => t("The description shown to the users."),
			"#type" => "textarea",
			"#required" => true,
			"#default_value" => $row["description"],
			);

		$retval["notes"] = array(
			"#title" => "Notes",
			"#description" => t("Notes about this level that will NOT be shown to "
				. "the public."),
			"#type" => "textarea",
			"#default_value" => $row["notes"],
			);


		$retval["submit"] = array(
			"#type" => "submit",
			"#value" => "Save"
			);

		return($retval);

	} // End of level_form()


	/**
	* This function validates a submitted level form.
	*/
	static function level_form_validate($form_id, &$data) {

		//
		// Make sure our year and price are numbers
		//
		if (!reg::is_valid_number($data["year"])) {
			$error = t("Year must be a number!");
			form_set_error("year", $error);
			reg_log::log($error, "", WATCHDOG_WARNING);
		}

		if (!reg::is_valid_float($data["price"])) {
			$error = t("Price must be a number!");
			form_set_error("price", $error);
			reg_log::log($error, "", WATCHDOG_WARNING);
		}

		if ($data["price"] == 0) {
			$error = t("Price cannot be 0!");
			form_set_error("price", $error);
			reg_log::log($error, "", WATCHDOG_WARNING);
		}

		if (reg::is_negative_number($data["price"])) {
			$error = t("Price '%price%' cannot be a negative amount!",
				array("%price%" => $data["price"])
				);
			form_set_error("price", $error);
			reg_log::log($error, "", WATCHDOG_WARNING);
		}

		//
		// Check our data order
		//
		$start = $data["start"];
		$start_time = reg_data::get_time_t($start["year"], $start["month"], 
			$start["day"]);

		$end = $data["end"];
		$end_time = reg_data::get_time_t($end["year"], $end["month"], 
			$end["day"]);

		//
		// The last day of this membership level will stop just past 
		// 11:59:59 PM on that day.
		//
		$end_time += 86399;

		if ($start_time > $end_time) {
			$error = t("Start date is after end date!");
			form_set_error("start][day", $error);
			reg_log::log($error, "", WATCHDOG_WARNING);
		}

	} // End of level_form_validate()



	/**
	* Everything in the form checks out, save the data.
	*/
	static function level_form_submit($form_id, $data) {

		//
		// Turn the data arrays into strings
		//
		$start = $data["start"];
		$data["start"] = reg_data::get_time_t($start["year"], 
			$start["month"], $start["day"]);

		$end = $data["end"];
		$data["end"] = reg_data::get_time_t($end["year"], $end["month"], 
			$end["day"]);

		//
		// The last day of this membership level will stop just past 
		// 11:59:59 PM on that day.
		//
		$data["end"] += 86399;


		//
		// Create an insert or an update, depending on if we have an ID
		// present.
		//
		if (empty($data["id"])) {
			$query = "INSERT INTO {reg_level} "
				. "(name, year, reg_type_id, "
				. "price, start, end, "
					. "description, notes) "
				. "VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')";
			$args = array(
				$data["name"], $data["year"], $data["reg_type_id"],
				$data["price"], $data["start"], $data["end"],
				$data["description"], $data["notes"]);

		} else {
			//
			// Load our old data for later use.
			//
			$old_data = self::load($data["id"]);

			$query = "UPDATE {reg_level} "
				. "SET "
				. "name='%s', year='%s', reg_type_id='%s', price='%s', "
				. "start='%s', end='%s', description='%s', notes='%s' "
				. "WHERE "
				. "id='%d'";
			$args = array($data["name"], $data["year"], $data["reg_type_id"],
				$data["price"], 
				$data["start"], $data["end"], $data["description"], 
					$data["notes"],
				$data["id"],
				);

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

		//
		// Create an audit log entry and write it out.
		//
		if (!empty($old_data)) {
			$message_log = $message . " " . reg_data::get_changed_data(
				$data, $old_data);
			reg_log::log($message_log);
		}

		$uri = "admin/reg/levels";
		reg::goto_url($uri);

	} // End of level_form_submit()


} // End of reg_level class

