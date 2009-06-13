<?php

/**
* This class holds the main reigstration forms.
*
* The reason why this extends the reg class is because the reg class also
* depends on this class, and we can't have any circular dependencies.  
* That would be bad.
*/
class reg_form extends reg {

	function __construct($fake, $log, $admin_member, $member, $captcha, 
		$message, &$watchlist, &$form_core) {
		$this->fake = $fake;
		$this->log = $log;
		$this->admin_member = $admin_member;
		$this->member = $member;
		$this->captcha = $captcha;
		$this->watchlist = $watchlist;
		$this->form_core = $form_core;

		parent::__construct($message, $fake, $log);

	}


	/**
	* This function creates the data structure for our main registration form.
	*
	* @param integer $id Our registration ID, if we are editing a member.
	*
	* @param object $cc_gateway Our credit card gateway.
	*
	* @return array Associative array of registration form.
	*/
	function reg($id, $cc_gateway) {

		$retval = array();

		$retval = $this->form_core->getForm($id, $cc_gateway);

		return($retval);

	} // End of reg()


	/**
	* This function is called to validate the form data.
	* If there are any issues, form_set_error() should be called so
	* that form processing does not continue.
	*
	* @param string $form_id Our unique form ID
	*
	* @param array $data Our form data.
	*
	* @param object $cc_gateway Our credit card gateway.
	*
	*/
	function reg_validate(&$form_id, &$data, &$cc_gateway) {

		$log = $this->log;

		//
		// Assume everything is okay, unless proven otherwise.
		//
		$okay = true;

		//
		// If there are any pre-existing form errors (required fields 
		// missing, etc.), note that.
		//
		if (form_get_errors()) {
			$okay = false;
		}

		//
		// If we have no payment type (such as coming through the public
		// interface), set it to credit card.
		//
		if (empty($data["reg_payment_type_id"])
			&& !$this->form_core->in_admin()
			) {
			$data["reg_payment_type_id"] = 1;
		}

		//
		// If we have no transaction type, set it to "purchase".
		//
		if (empty($data["reg_trans_type_id"])) {
			$data["reg_trans_type_id"] = 1;
		}

		//
		// Check our captcha submission.
		//
		if (!$this->form_core->in_admin()) {
			if (!$this->form_core->checkCaptcha($data["reg_captcha"])) {
				$okay = false;
			}
		}

		//
		// Make sure our email addresses match.
		//
		if (!$this->form_core->checkEmailAddresses($data["email"], 
			$data["email2"])) {
			$okay = false;
		}

		//
		// If the payment type is a credit card, make sure that we have
		// card information.
		//
		$payment_type = $this->get_payment_type(
			$data["reg_payment_type_id"]);

		//
		// If the we are paying with the credit card, make sure that
		// that the card type and number have been entered.
		// The reason for this extra check is that when manually adding
		// a registration, these fields are not flagged as required since
		// it could be a non-paying membership, such as staff or 
		// a Guest of Honor.
		//
		if ($payment_type == "Credit Card") {

			if (empty($data["cc_type_id"])) {
				$error = t("No credit card type selected.");
				form_set_error("cc_type_id", $error);
				$log->log($error, "", WATCHDOG_WARNING);
			}

			if (empty($data["cc_num"])) {
				$error = t("No credit card number entered.");
				form_set_error("cc_num", $error);
				$log->log($error, "", WATCHDOG_WARNING);
			}

			//
			// Make sure the card isn't expired.
			//
			$month = date("n");
			$year = date("Y");

			$data_year = $data["cc_exp"]["year"];
			$data_month = $data["cc_exp"]["month"];

			if ($data_year < $year) {
				$this->set_cc_expired($data_month, $data_year);
				$okay = false;

			} else if ($data_year == $year) {
				//
				// The current month is okay.
				//
				if ($data_month < $month) {
					$this->set_cc_expired($data_month, $data_year);
					$okay = false;
				}
			}

		}
	
		//	
		// Sanity check on our birth date.
		//	
		if (!$this->form_core->checkBirthDate($data["birthdate"])) {
			$okay = false;
		}


		//
		// If we're in the admin, we can skip alot of this stuff.
		//
		if ($this->form_core->in_admin()) {

			//
			// Make sure the badge nuber is valid.
			//
			$this->is_badge_num_valid($data["badge_num"]);
			$this->is_badge_num_available($data["reg_id"], 
				$data["badge_num"]);

			//
			// Log the transaction.  Note that wer are NOT charging
			// the card here.
			//
			// Also, if there is a registration ID, that means we're
			// editing a registration, and should not log anything for 
			// that.
			//
			if (empty($data["reg_id"])) {
				$reg_trans_id = $log->log_trans($data);
				$_SESSION["reg"]["reg_trans_id"] = $reg_trans_id;
			}

			$message = t("In the admin interface.  Bailing out early.");
			$log->log($message);

			return(null);

		}


		//
		// Sanity checking on our donation amount.
		//
		if (!$this->form_core->checkDonation($data["donation"])) {
			$okay = false;

		}


		//
		// Make sure our registration level is valid
		//
		if ($this->form_core->checkLevel($data["reg_level_id"])) {
			$okay = false;
		}


		//
		// If something failed, stop here and do NOT try to charge
		// the credit card.
		//
		if (empty($okay)) {
			$message = t("One or more form errors found.  Stopping and NOT "
				. "charging the credit card.");
			$log->log($message, "", WATCHDOG_WARNING);
			return(null);
		}

		//
		// Make the transaction.  If it is successful, then add a new member.
		//
		$reg_trans_id = $this->charge_cc($data, $cc_gateway);
		$_SESSION["reg"]["reg_trans_id"] = $reg_trans_id;

	} // End of registration_form_validate()


	function set_cc_expired($month, $year) {

		$log = $this->log;

		$error = t("Credit card is expired (!month/!year)",
			array(
				"!month" => $month,
				"!year" => $year,
			));
		form_set_error("cc_exp][month", $error);
		$log->log($error, "", WATCHDOG_WARNING);
	}



	/**
	* All the registration form data checks out.  
	*/
	function reg_submit(&$form_id, &$data) {

		//
		// The URI to send ourselves to
		//
		$uri = "";

		if (!$this->form_core->in_admin()) {

			//
			// We're done with the captcha, clear it out.
			//
			$captcha = $this->captcha;
			$captcha->clear();

			//
			// Front-end submissions are always new.
			//
			$this->reg_submit_new($data);

			$uri = "reg/success";

		} else {
			//
			// Submission from the admin.  Are we updating or creating a 
			// new member?
			//
			if (!empty($data["reg_id"])) {
				$this->reg_submit_update($data);
				$uri = "admin/reg/members/view/" . $data["reg_id"] . "/view";

			} else {
				$this->reg_submit_new($data);
				$uri = "admin/reg/members";
			}
		}

		$this->goto_url($uri);

	} // End of registration_form_submit()


	/**
	* Add a new member and set messages in our session data for the
	* success page.
	* Also send an email out to the member.
	*/
	function reg_submit_new(&$data) {

		$member = $this->member;

		$data["badge_num"] = $member->add_member($data, 
			$_SESSION["reg"]["reg_trans_id"]);

		//
		// Store messages for the success page.
		//
		$saved_data = &$_SESSION["reg"]["success"];
		$saved_data["badge_num"] = $data["badge_num"];

		if (!empty($data["cc_type_id"])
			&& !$this->form_core->in_admin()
			) {
			$data["cc_name"] = $this->get_cc_name($data["cc_type_id"], 
				$data["cc_num"]);

			$saved_data["cc_name"] = $data["cc_name"];
			$saved_data["total_cost"] = $data["total_cost"];
		}

		$saved_data["member_email"] = $data["email"];

		if ($this->form_core->in_admin()) {
			$message = t("Member added successfully with badge number "
				. "'!badge_num'!",
				array(
					"!badge_num" => $data["badge_num"],
					)
				);
			drupal_set_message($message);
			
		}

	} // End of reg_submit_new()


	/**
	* Process an updated registration.
	* We will update the database and log this.
	*/
	function reg_submit_update(&$data) {

		$badge_num = $this->admin_member->update_member($data);

		$message = t("Registration updated!");
		drupal_set_message($message);

	} // End of reg_submit_update()






} // End of reg_form class

