<?php
/**
* This class is used for staff members to validate registrations which 
*	were entered onsite.  This will print a slimmed down form with
*	key member details.
*/
class Reg_OnsiteValidate {


	function __construct(&$reg, &$admin_member, &$watchlist, &$log, 
		&$util_print) {
		$this->reg = $reg;
		$this->admin_member = $admin_member;
		$this->watchlist = $watchlist;
		$this->log = $log;
		$this->util_print = $util_print;
	}


	/**
	* Create our page for validating a membership.
	*
	* @param integer $id The ID from the reg table.
	*
	* @return string HTML code of the validation form.
	*/
	function getPage($id) {
	
		$retval = "";

		$retval .= "<h2>" . t("Validate Onsite Registration") . "</h2>";
		$retval .= drupal_get_form("reg_admin_members_validate_form", $id);

		return($retval);

	} // End of getPage()


	/**
	* This function creates our form.
	*
	* @param integer $id The ID from the reg table.
	*
	* @return array Array of form data.
	*/
	function getForm($id) {

		$retval = array();

		$data = $this->admin_member->load_reg($id);

		if ($data["status"] != t("New")) {
			$message = t("Only badges with a status of 'new' can be validated.");
			drupal_set_message($message);
			$uri = "admin/reg/members/view/" . $id . "/view";
			$this->reg->goto_url($uri);
		}

		$watchlist_data = array(
			"first" => $data["first"],
			"last" => $data["last"],
			);
		$match = $this->watchlist->search($watchlist_data);

		$retval["reg_id"] = array(
			"#type" => "hidden",
			"#value" => $id,
			);

		$retval["badge_name"] = array(
			"#type" => "textfield",
			"#title" => t("Badge Name"),
			"#size" => $this->reg->get_constant("FORM_TEXT_SIZE_SMALL"),
			"#description" => t("The name that will be printed on the member's badge."),
			"#default_value" => $data["badge_name"],
			);

		$retval["first"] = array(
			"#type" => "textfield",
			"#title" => t("First Name"),
			"#size" => $this->reg->get_constant("FORM_TEXT_SIZE_SMALL"),
			"#description" => t("Check to make sure this matches the member's ID"),
			"#default_value" => $data["first"],
			"#required" => true,
			);
		
		$retval["middle"] = array(
			"#type" => "textfield",
			"#title" => t("Middle Name"),
			"#size" => $this->reg->get_constant("FORM_TEXT_SIZE_SMALL"),
			"#description" => t("Check to make sure this matches the member's ID"),
			"#default_value" => $data["middle"],
			);

		$retval["last"] = array(
			"#type" => "textfield",
			"#title" => t("Last Name"),
			"#size" => $this->reg->get_constant("FORM_TEXT_SIZE_SMALL"),
			"#description" => t("Check to make sure this matches the member's ID"),
			"#default_value" => $data["last"],
			"#required" => true,
			);

        $retval["birthdate"] = array(
            "#type" => "textfield",
            "#title" => t("Date of Birth"),
			"#size" => $this->reg->get_constant("FORM_TEXT_SIZE_SMALL"),
            "#description" => t("DEFINITELY check this against ID"),
            "#value" => $data["birthdate"],
			"#disabled" => true,
            );

		$retval["member_type"] = array(
			"#type" => "item",
			"#title" => t("Membership Type"),
			"#value" => $data["member_type"],
			);

		$retval["amount_due"] = array(
			"#type" => "item",
			"#title" => t("Amount Due"),
			"#description" => t("The cost of the membership"),
			"#value" => "$" . $data["badge_cost_due"],
			);

		if (!empty($data["donation_due"])) {
			$retval["donation_due"] = array(
				"#type" => "item",
				"#title" => t("Donation Due"),
				"#description" => t("If the member entered a donation amount."),
				"#value" => "$" . $data["donation_due"],
				);
		}

		$retval["badge_cost"] = array(
			"#type" => "textfield",
			"#title" => t("Amount Paid"),
			"#size" => $this->reg->get_constant("FORM_TEXT_SIZE_SMALL"),
			"#default_value" => "0.00",
			"#required" => true,
			);

		$retval["donation"] = array(
			"#type" => "textfield",
			"#title" => t("Donation"),
			"#size" => $this->reg->get_constant("FORM_TEXT_SIZE_SMALL"),
			"#description" => t("Would the member like to make an additional donation?"),
			"#default_value" => "0.00",
			"#required" => true,
			);

		$types = $this->reg->get_payment_types();
		$types[""] = t("Select");
		$retval["reg_payment_type_id"] = array(
			"#title" => "Payment Type",
			"#type" => "select",
			"#options" => $types,
			"#description" => "What is our method of payment?",
			"#required" => true,
			);

		$retval["notes"] = array(
			"#type" => "textarea",
			"#title" => t("Any additional notes about this membership"),
			);

		$retval["save"] = array(
			"#type" => "submit",
			"#value" => t("Validate"),
			);

		$retval["save_and_print"] = array(
			"#type" => "submit",
			"#value" => t("Validate and Print Badge"),
			"#submit" => array("reg_admin_members_validate_form_submit_print"),
			);

		if ($this->reg->isMinor($data["birthdate"])) {
			$retval["save_and_print"]["#value"] = 
				t("Validate and Print MINOR Badge");
		}

		if ($match) {
			$retval["save"]["#value"] = t("Disabled for members on watchlist");
			$retval["save"]["#disabled"] = true;
			$retval["save_and_print"]["#value"] = t("Disabled for members on watchlist");
			$retval["save_and_print"]["#disabled"] = true;
		}

		return($retval);

	} // End of getForm()


	/**
	* Run our validation.
	*/
	function getFormValidate(&$data) {

		//
		// Exempty "Comp" and "Guest" badges from payment.
		//
		if (
			$data["reg_payment_type_id"] == 4
			|| $data["reg_payment_type_id"] == 9
			) {
			$message = t("Badge payment type is a 'free' type, so let's not "
				. "require any money from this member.");
			$this->log->log($message, $data["reg_id"]);

		} else {
			if ($data["badge_cost"] == 0) {
				form_set_error("badge_cost", t("Amount Paid cannot be zero!"));
			}

		}

		if (!$this->reg->is_valid_float($data["badge_cost"])) {
			form_set_error("badge_cost", t("Badge cost is not a valid number!"));
		}

		if ($this->reg->is_negative_number($data["badge_cost"])) {
			form_set_error("badge_cost", t("Badge cost cannot be negative!"));
		}

		if (!$this->reg->is_valid_float($data["donation"])) {
			form_set_error("donation", t("Donation is not a valid number!"));
		}

		if ($this->reg->is_negative_number($data["donation"])) {
			form_set_error("donation", t("Donation cannot be negative!"));
		}

	} // End of getFormValidate()


	/**
	* Our submit handler.
	*
	* @param array $data Form data
	*
	* @param boolean $print_badge Are we also sending the current badge 
	*	to the printer?
	*/
	function getFormSubmit(&$data, $print_badge = false) {

		$this->updateMember($data);
		$this->updateLog($data);
		if ($print_badge) {
			$this->printBadge($data);
		}

		drupal_set_message("Registration validated.");

		$uri = "admin/reg/members/view/" . $data["reg_id"] . "/view";
		$this->reg->goto_url($uri);

	} // End of getFormSubmit()


	/**
	* Update the member's information, and set the badge as "Complete".
	*
	* @param array $data The data for the member
	*/
	function updateMember(&$data) {

		//
		// Get the next available badge number.
		//
		// NOTE: If we have problems with this onsite, do NOT adjust this
		// function call, adjust the constant in the main Reg class instead!
		//
		$data["badge_num"] = $this->reg->get_badge_num();

		$query = "UPDATE {reg} "
			. "SET "
			. "badge_name='%s', "
			. "badge_num='%s', "
			//
			// Set badge status to complete
			//
			. "reg_status_id=1, "
			. "first='%s', middle='%s', last='%s' "
			. "WHERE "
			. "id='%s'";
		$query_args = array($data["badge_name"], $data["badge_num"],
			$data["first"], $data["middle"], $data["last"],
			$data["reg_id"]);
		db_query($query, $query_args);

	} // End of updateMember()


	/**
	* Update the reg log and the transaction log indicating a payment.
	*
	* @param array $data The data for the member
	*/
	function updateLog(&$data) {

		//
		// Set the registration type for "payment"
		//
		$data["reg_trans_type_id"] = 1;

		$this->log->log_trans($data);

		$message = t("Validated onsite registration.");
		if (!empty($data["notes"])) {
			$message .= " " . t("Notes: %notes", 
				array("%notes" => $data["notes"]));
		}
		$this->log->log($message, $data["reg_id"]);

	} // End of updateLog()


	/**
	* Queue our badge up for printing.
	*
	* @param array $data The data for the member
	*/
	function printBadge(&$data) {

		if ($this->reg->isMinor($data["birthdate"])) {
			$printer = "minor";
		}

		$id = $this->util_print->addJob($data["reg_id"], $printer);

		$message = t("Badge queued for printing. (Print Job ID: !id)",
			array("!id" => $id));
		drupal_set_message($message);
		$this->log->log($message, $data["reg_id"]);

	} // End of printBadge()


} // End of Reg_OnsiteValidate()

