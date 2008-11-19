<?php

/**
* This class is used for managing our messages that are displayed to 
*	the user.
*/
class reg_admin_settings_message extends reg {


	function __construct(&$message, &$log) {
		$this->message = $message;
		$this->log = $log;
	}


	/**
	* List all messages.
	*/
	function messages() {

		$retval = t("Messages which will be displayed to users.  "
			. "Some will be displayed through the web interface, "
			. "others will be emailed.<p/>\n");

		$header = array();
		$header[] = array("data" => "ID #", "field" => "id",);
		$header[] = array("data" => "Type", "field" => "type",);
		$header[] = array("data" => "Name", "field" => "name",);
		$header[] = array("data" => "Subject", "field" => "subject",);
		$header[] = array("data" => "Preview",);

		$order_by = tablesort_sql($header);

		$rows = array();
		$query = "SELECT * FROM {reg_message} $order_by";
		$cursor = db_query($query);

		while ($row = db_fetch_array($cursor)) {

			$link = "admin/reg/settings/messages/" . $row["id"] . "/edit";

			$message = truncate_utf8($row["value"], 60) . "...";

			$subject = "N/A";
			if ($row["type"] == "email") {
				$subject = l($row["subject"], $link);
			}

			$rows[] = array(
				l($row["id"], $link),
				l($row["type"], $link),
				l($row["name"], $link),
				$subject,
				$message,
				);
		}

		if (empty($rows)) {
			$message = t("No messages found.");
			$rows[] = array(
				array(
					"data" => $message,
					"colspan" => count($header),
					)
				);
		}

		$retval .= theme("table", $header, $rows);
		return($retval);

	} // End of messages()


	/**
	* Edit a message.
	*/
	function edit($id) {

		$retval = drupal_get_form("reg_admin_settings_message_form", $id);
		return($retval);

	} // End of edit()


	/**
	* Our form for editing a message.
	*/
	function form($id) {

		$retval = array();
		$row = array();

		//
		// Retrieve our existing row of data.
		//
		$query = "SELECT * FROM {reg_message} WHERE id='%d'";
		$args = array($id);
		$cursor = db_query($query, $args);
		$row = db_fetch_array($cursor);

		$retval["id"] = array(
			"#title" => "id",
			"#type" => "hidden",
			"#value" => $id,
			);

		$title = "Edit Message ID '$id'";
		drupal_set_title($title);

		$retval["name"]  = array(
			"#title" => "Name",
			"#type" => "item",
			"#size" => reg_form::FORM_TEXT_SIZE,
			"#required" => true,
			"#value" => $row["name"],
			);

		if ($row["type"] == "email") {
			$retval["subject"] = array(
				"#title" => "Subject",
				"#description" => t("The subject line in the email of the "
					. "message."),
				"#type" => "textfield",
				"#size" => reg_form::FORM_TEXT_SIZE,
				"#required" => true,
				"#default_value" => $row["subject"],
				);
		}

		$retval["value"] = array(
			"#title" => "Message",
			"#description" => t("The message that will be shown/emailed "
				. "to the user.  Note that different messages may do "
				. "variable substitutions with different !-style "
				. "variables."),
			"#type" => "textarea",
			"#rows" => 20,
			"#required" => true,
			"#default_value" => $row["value"],
			);

		//
		// Retrieve tokens for this message and add in them and their
		// descriptions.
		//
		$message = $this->message;
		$tokens = $message->get_tokens($row["name"]);
		$token_string = "";
		foreach ($tokens as $key => $value) {
			$token_string .= t("<b>!key</b> - !value<br>\n", 
				array(
				"!key" => $key,
				"!value" => $value,
				));

		}

		if (!empty($token_string)) {
			$retval["value"]["#description"] .= "<p/>\n"
				. t("Available tokens:<p/>\n")
				. $token_string
				;
		}

		$retval["notes"] = array(
			"#title" => "Notes",
			"#description" => t("Notes about this message that will NOT be "
				. "shown to the public."),
			"#type" => "textarea",
			"#default_value" => $row["notes"],
			);


		$retval["submit"] = array(
			"#type" => "submit",
			"#value" => "Save"
			);

		return($retval);

	} // End of form()


	/**
	* This function validates a submitted form.
	*/
	function form_validate($form_id, &$data) {
	} // End of form_validate()


	/**
	* Everything in the form checks out, save the data.
	*/
	function form_submit($form_id, $data) {

		$message = $this->message;
		$old_data = $message->load_by_id($data["id"]);

		$query = "UPDATE {reg_message} "
			. "SET "
			. "subject='%s', value='%s', notes='%s' "
			. "WHERE "
			. "id='%s' ";
		$query_args = array($data["subject"], $data["value"], 
			$data["notes"], $data["id"]);
		db_query($query, $query_args);

		$message = t("Message '!name' updated.",
			array("!name" => $old_data["name"]));
		drupal_set_message($message);

		$message = t("Message '!name' updated.", 
			array("!name" => $old_data["name"]));

		//
		// Unset values we're not checking.
		//
		if (!empty($old_data["name"])) {
			unset($old_data["name"]);
		}
		if (!empty($old_data["type"])) {
			unset($old_data["type"]);
		}

		$message .= " " . $this->get_changed_data($data, $old_data);
		$log = $this->log;
		$log->log($message);

	} // End of form_submit()


} // End of reg_admin_settings_message class


