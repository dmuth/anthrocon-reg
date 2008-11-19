<?php

/**
* This class is used for performing balance adjustments on specific 
*	memberships.
* Note that it borrows heavily from the forms in the reg_admin_cancel class.
*/
class reg_admin_adjust extends reg_admin_cancel {


	function __construct(&$log, $reg_admin_member) {
		parent::__construct($log, $reg_admin_member);
	}

	function adjust($id) {

		$retval = "";
		$retval .= drupal_get_form("reg_admin_members_adjust_form", $id);

		return($retval);

	}


	/**
	* Our member adjustment form.  We are extending the cancellation form and
	* tweaking a few values.
	*/
	function form($id) {

		$retval = parent::form($id);

		unset($retval["reg_status_id"]);

		$retval["note"]["#value"] = 
			t("This form is for performing a manual balanace adjustment on "
				. "a specific membership.  An example might be if we typo "
				. "the amount refunded on the cancellation form, or the "
				. "member later decides they want to have/have not their "
				. "donation refunded.");

		unset($retval["reg_trans_type_id"]["#default_value"]);

		$retval["badge_cost"]["#default_value"] = 0.00;
		$retval["badge_cost"]["#description"] = t("Adjustment amount.  "
			. "A positive number represents receiving a payment <b>from</b> "
				. "the member.  "
			. "A negative number represents refunding money <b>to</b> a "
				. "member."
			);

		$retval["donation"]["#default_value"] = 0.00;
		$retval["donation"]["#description"] = t("Adjustment amount.  "
			. "A positive number represents receiving a payment <b>from</b> "
				. "the member.  "
			. "A negative number represents refunding money <b>to</b> a "
				. "member."
			);

		$retval["reg_payment_type_id"]["#description"] = 
			t("How is the money changing hands?  If unsure or N/A, "
				. "choose \"Other\".")
			;

		$retval["submit"]["#value"] = t("Perform Balance Adjustment");

		return($retval);

	} // End of form()


	function form_validate($form_id, &$data) {
		//
		// Check for a valid badge cost number
		//
		if (!$this->is_valid_float($data["badge_cost"])) {
			$error = t("Badge cost '%cost%' is not a valid number!",
				array("%cost%" => $data["badge_cost"]));
			form_set_error("badge_cost", $error);
		}

		//
		// Check for a valid donation number
		//
		if (!$this->is_valid_float($data["donation"])) {
			$error = t("Donation amount '%cost%' is not a valid number!",
				array("%cost%" => $data["donation"]));
			form_set_error("donation", $error);
		}

	} // End of form_validate()


	function form_submit($form_id, &$data) {

		//
		// Write a transaction record.
		//
		$this->log->log_trans($data);

		$reg_id = $data["reg_id"];
		$message = t("Balance adjustment.");
		if (!empty($data["notes"])) {
			$message .= t(" Notes: ") . $data["notes"];
		}

		//
		// Write a log entry for this member.
		//
		$this->log->log($message, $reg_id);

		$message = t("Balance adjustment successful.");
		drupal_set_message($message);

		//
		// Redirect the user back to the viewing page.
		//
		$uri = "admin/reg/members/view/" . $reg_id . "/view";
		$this->goto_url($uri);

	} // End of form_submit()


} // End of reg_admin_adjust class

