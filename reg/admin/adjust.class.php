<?php

/**
* This class is used for performing balance adjustments on specific 
*	memberships.
* Note that it borrows heavily from the forms in the reg_admin_cancel class.
*/
class reg_admin_adjust {

	static function adjust($id) {

		$retval = "";
		$retval .= drupal_get_form("reg_admin_members_adjust_form", $id);

		return($retval);

	}


	/**
	* Our member adjustment form.  We are extending the cancellation form and
	* tweaking a few values.
	*/
	static function form($id) {

		$retval = reg_admin_cancel::form($id);

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
			. "Increasing the amount corresponds to a payment from the member. "
			. "Decreasing the amount corresponds to a refund to the member. "
			);

		$retval["donation"]["#default_value"] = 0.00;
		$retval["donation"]["#description"] = t("Adjustment amount.  "
			. "Increasing the amount corresponds to a payment from the member. "
			. "Decreasing the amount corresponds to a refund to the member. "
			);

		$retval["reg_payment_type_id"]["#description"] = 
			t("How is the money changing hands?  If unsure or N/A, "
				. "choose \"Other\".")
			;

		$retval["submit"]["#value"] = t("Perform Balance Adjustment");

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

		//
		// Check for a valid donation number
		//
		if (!reg::is_valid_float($data["donation"])) {
			$error = t("Donation amount '%cost%' is not a valid number!",
				array("%cost%" => $data["donation"]));
			form_set_error("donation", $error);
		}

	} // End of form_validate()


	static function form_submit($form_id, &$data) {

		//
		// Write a transaction record.
		//
		reg_log::log_trans($data);

		$reg_id = $data["reg_id"];
		$message = t("Balance adjustment.");
		if (!empty($data["notes"])) {
			$message .= t(" Notes: ") . $data["notes"];
		}

		//
		// Write a log entry for this member.
		//
		reg_log::log($message, $reg_id);

		$message = t("Balance adjustment.");
		drupal_set_message($message);

		//
		// Redirect the user back to the viewing page.
		//
		$uri = "admin/reg/members/view/" . $reg_id . "/view";
		reg::goto_url($uri);

	} // End of form_submit()


} // End of reg_admin_adjust class

