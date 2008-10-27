<?php

/**
* This class is responsible for our admin cancellation form and code.
*/
class reg_admin_cancel {

	function __construct() {
		$factory = new reg_factory();
		$this->log = $factory->get_object("log");
		$this->form = $factory->get_object("form");
	}


	/**
	* Cancel an existing membership.
	*/
	static function cancel($id) {

		$retval = "";
		$retval .= drupal_get_form("reg_admin_members_cancel_form", $id);

		return($retval);

	} // End of cancel()


	static function form($id) {

		$retval = array();
		$data = reg_admin_member::load_reg($id);

		if ($data["badge_cost"] == 0) {
			$message = t("Notice: This user's badge cost is currently ZERO. ")
				. t("Be careful with that badge cost number!");
			drupal_set_message($message);
		}

		$retval["reg_id"] = array(
			"#type" => "hidden",
			"#value" => $id,
			);

		$retval["note"] = array(
			"#value" => 
				t("Cancel this user's membership. ")
				. t("Note that credit cards will have to be manually "
					. "refunded through our Merchant Terminal, or a check "
					. "written, etc.  Please do that BEFORE recording the "
					. "membership as cancelled.")
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

		$statuses = reg_data::get_statuses();
		$statuses[""] = t("Select");
		//ksort($statuses);

		$retval["reg_status_id"] = array(
			"#title" => "Status",
			"#type" => "select",
			"#options" => $statuses,
			"#description" => "Select a new status for this membership.",
			"#required" => true,
			"#default_value" => array_search("Refund", $statuses),
			);

		$types = reg_data::get_trans_types();
		$types[""] = t("Select");
		$retval["reg_trans_type_id"] = array(
			"#title" => "Transaction Type",
			"#type" => "select",
			"#options" => $types,
			"#description" => "Select a transaction type",
			"#required" => true,
			"#default_value" => array_search("Refund", $types),
			);

		$types = reg_data::get_payment_types();
		$types[""] = t("Select");
		$retval["reg_payment_type_id"] = array(
			"#title" => "Payment Type",
			"#type" => "select",
			"#options" => $types,
			"#description" => "How are we issuing the refund?",
			"#required" => true,
			);

		$retval["badge_cost"] = array(
			"#title" => "Badge Cost",
			"#description" => t("How much of the badge cost is the user "
				. "being refunded?"),
			"#type" => "textfield",
			"#size" => reg_form::FORM_TEXT_SIZE_SMALL,
			"#default_value" => $data["badge_cost"],
			"#required" => true,
			);

		$retval["donation"] = array(
			"#title" => "Donation",
			"#description" => t("How much of the donation is the user "
				. "being refunded?"),
			"#type" => "textfield",
			"#size" => reg_form::FORM_TEXT_SIZE_SMALL,
			"#default_value" => $data["donation"],
			"#required" => true,
			);

		$retval["notes"] = array(
			"#title" => "Notes",
			"#description" => "Any additional notes you want to add. (optional)",
			"#type" => "textarea",
			);

		$retval["submit"] = array(
			"#type" => "submit",
			"#value" => t("Cancel Membership")
			);

		return($retval);

	} // End of form()


	static function form_validate($form_id, &$data) {

		//
		// Check for a valid badge cost number
		//
		if (!reg::is_valid_float($data["badge_cost"])) {
			$error = t("Badge cost '%cost%' is not a valid number!",
				array("%cost%" => $data["badge_cost"]));
			form_set_error("badge_cost", $error);
		}

		if (reg::is_negative_number($data["badge_cost"])) {
			$error = t("Badge cost cannot be a negative number!");
			form_set_error("badge_cost", $error);
		}

		//
		// Check for a valid donation number
		//
		if (!reg::is_valid_float($data["donation"])) {
			$error = t("Donation amount '%cost%' is not a valid number!",
				array("%cost%" => $data["donation"]));
			form_set_error("donation", $error);
		}

		if (reg::is_negative_number($data["donation"])) {
			$error = t("Donation amount cannot be a negative number!");
			form_set_error("donation", $error);
		}

	} // End of form_validate()


	/**
	* Save the new note.
	*/ 
	static function form_submit($form_id, &$data) {

		//
		// Invert the numbers, since we're refunding money here..
		//
		$data["badge_cost"] *= -1;
		$data["donation"] *= -1;

		//
		// Write a transaction record.
		//
		$factory = new reg_factory();
		$log = $factory->get_object("log");
		$log->log_trans($data);

		$reg_id = $data["reg_id"];
		$message = t("Registration cancelled.");
		if (!empty($data["notes"])) {
			$message .= t(" Notes: ") . $data["notes"];
		}

		//
		// Write a log entry for this member.
		//
		$log->log($message, $reg_id);

		$message = t("Registration cancelled.");
		drupal_set_message($message);

		//
		// Update the member's status.
		//
		$query = "UPDATE {reg} "
			. "SET "
			. "reg_status_id='%s' "
			. "WHERE "
			. "id='%s' "
			;
		$query_args = array($data["reg_status_id"], $data["reg_id"]);
		db_query($query, $query_args);


		//
		// Redirect the user back to the viewing page.
		//
		$uri = "admin/reg/members/view/" . $reg_id . "/view";
		reg::goto_url($uri);

	} // End of form_submit()


} // End of reg_admin_cancel class

