<?php

/**
* This class holds the main reigstration forms.
*
* The reason why this extends the reg class is because the reg class also
* depends on this class, and we can't have any circular dependencies.  
* That would be bad.
*/
class reg_form extends reg {

	function __construct($fake, $log, $admin_member, $member, $captcha) {
		$this->fake = $fake;
		$this->log = $log;
		$this->admin_member = $admin_member;
		$this->member = $member;
		$this->captcha = $captcha;
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

		if (!empty($id)) {
			$data = $this->admin_member->load_reg($id);
			$retval["reg_id"] = array(
				"#type" => "hidden",
				"#value" => $id,
				);

		}

		$retval["member"] = $this->form($id, $data);

		$retval["billing"] = $this->form_address_billing($id, $data);
		$retval["shipping"] = $this->form_address_shipping($id, $data);

		//
		// Don't display our credit card info when we're editing, as
		// only admins can edit a registration.
		//
		if (empty($id)) {
			$retval["cc"] = $this->form_cc($id, $data, $cc_gateway);
		}

		return($retval);

	} // End of reg()


	/**
	* Are we in the admin section?  If we are, certain checks are not
	* done and certain fields are not displayed.
	*
	* @return boolean True is we are in the admin section, false otherwise.
	*/
	function in_admin() {

		if (arg(0) == "admin") {
			return(true);
		}

		return(false);

	} // End in_admin()


	/**
	* Return the current Drupal path.
	*/
	function get_path() {

		//
		// Remove the leading base path.
		//
		$retval = request_uri();
		$base_path = base_path();
		$retval = ereg_replace("^" . $base_path, "", $retval);

		return($retval);

	} // End of get_path()


	/**
	* Check and see if we are currently in a fake form or not.
	*
	* @return boolean True if we are in a fake form.
	*/
	function in_fake_form() {

		//
		// If fake data isn't set/allowed, then stop right here.
		//
		if (!$this->is_fake_data()) {
			return(false);
		}

		//
		// If the last "page" in our URL is "fake", return true.
		//
		$uri = $this->get_path();
		$fields = split("/", $uri);
		$index = count($fields) - 1;

		if ($fields[$index] != "fake") {
			return(false);
		}
		
		return(true);

	} // End of in_fake_form()


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
			&& !$this->in_admin()
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
		if (!$this->in_admin()) {

			if (!$this->captcha->check($data["reg_captcha"])) {
				$message = t("Incorrect answer to math question.");
				form_set_error("reg_captcha", $message);
				$this->log->log($message, "", WATCHDOG_WARNING);
				$okay = false;
			}

		}

		if ($data["email"] != $data["email2"]) {
			$error = t("Email addresses do not match!");
			form_set_error("email2", $error);
			$log->log($error, "", WATCHDOG_WARNING);
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
		// Don't allow the default birthdate of today.
		//
		$birth = $data["birthdate"];
		if ($birth["year"] == date("Y")
			&& $birth["month"] == date("n")
			&& $birth["day"] == date("j")
			) {
			$error = t("Date of birth is set to today. ")
				. t("Did you forget to enter it?")
				;
			form_set_error("birthdate][year", $error);
			$log->log($error, "", WATCHDOG_WARNING);
			$okay = false;
		}

		//
		// If we're in the admin, we can skip alot of this stuff.
		//
		if ($this->in_admin()) {

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
		if (!$this->is_valid_float($data["donation"])
			&& $data["donation"] != ""
			) {
			$error = t("Donation '%donation%' is not a number!",
				array("%donation%" => $data["donation"])
				);
			form_set_error("donation", $error);
			$log->log($error, "", WATCHDOG_WARNING);
			$okay = false;

		} else if ($this->is_negative_number($data["donation"])) {
			$error = t("Donation '%donation%' cannot be a negative amount!",
				array("%donation%" => $data["donation"])
				);
			form_set_error("donation", $error);
			$log->log($error, "", WATCHDOG_WARNING);
			$okay = false;

		} else if ($data["donation"] > $this->get_constant("DONATION_MAX")) {
			$error = t("Donations over %max% may not be made online.  "
				. "If you wish to donate a larger amount, please "
				. "contact us directly.",
				array(
					"%max%" => "$" . $this->get_constant("DONATION_MAX")
					)
				);
			form_set_error("donation", $error);
			$log->log($error, "", WATCHDOG_WARNING);
			$okay = false;
        }


		//
		// Make sure our registration level is valid
		//
		if (empty($data["reg_level_id"])) {
			$error = t("Membership type is required.");
			form_set_error("reg_level_id", $error);
			$log->log($error, "", WATCHDOG_WARNING);
		}

		$levels = $this->get_valid_levels();
		if (empty($levels[$data["reg_level_id"]])) {
			$error = t("Registration level ID '%level%' is invalid.",
				array("%level%" => $data["reg_level_id"])
				);
			form_set_error("reg_level_id", $error);
			$log->log($error, "", WATCHDOG_ERROR);
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

		if (!$this->in_admin()) {

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
			&& !$this->in_admin()
			) {
			$data["cc_name"] = $this->get_cc_name($data["cc_type_id"], 
				$data["cc_num"]);

			$saved_data["cc_name"] = $data["cc_name"];
			$saved_data["total_cost"] = $data["total_cost"];
		}

		$saved_data["member_email"] = $data["email"];

		if ($this->in_admin()) {
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


	/**
	* Are we allowing fake data to be created?
	*
	* @return boolean True if yes, false if no.
	*/
	function is_fake_data() {
		$retval = variable_get(
			$this->get_constant("FORM_ADMIN_FAKE_DATA"), "");
		return($retval);
	}
	

	/**
	* This function function creates the membership section of our 
	*	registration form.
	*
	* @param integer $id reg_id if we are editing a record.
	*
	* @param array $data Associative array of membership info.
	*/
	function form($id = "", &$data) {

		if (empty($data)) {
			$data = array();
		}

		$retval = array(
			"#type" => "fieldset",
			"#title" => t("Membership Information"),
			//
			// Render elements with our custom theme code
			//
			"#theme" => "reg_theme"
			);

		//
		// If we're allowing fake data, print up the checbox.
		// The additional conditionals are if we're not already
		// in the "fake data" form (which generates fake data upon 
		//	being loaded) and we're not editing a current member.
		//
		if ($this->is_fake_data()) {
			if (!$this->in_fake_form()) {

				if (empty($id)) {
					$url = $this->get_path() . "/fake";
					$title = t("Fill form with fake data");
					$value = l($title, $url);

					$retval["fake_data"] = array(
						"#value" => $value,
						);

				}
			}
		}

		//
		// If we are in the fake form, generate fake data.
		//
		if ($this->in_fake_form()
			&& empty($id)
			) {
			$this->fake->get_data($data);
		}

		$retval["badge_name"] = array(
			"#title" => t("Badge Name"),
			"#type" => "textfield",
			"#description" => t("The name printed on your conbadge.  ")
				. t("This may be your real name, a nickname, or blank."),
			"#size" => $this->get_constant("FORM_TEXT_SIZE_SMALL"),
			"#default_value" => $data["badge_name"],
			);

		//
		// Display additional options for the admin to set.
		//
		if ($this->in_admin()) {
			$retval["badge_num"] = array(
				"#title" => t("Badge Number"),
				"#type" => "textfield",
				"#description" => t("This must be UNIQUE.  If unsure, leave blank and "
					. "one will be assigned."),
				"#size" => $this->get_constant("FORM_TEXT_SIZE_SMALL"),
				"#default_value" => $data["badge_num"],
				);

			$retval["reg_type_id"] = array(
				"#title" => t("Badge Type"),
				"#type" => "select",
				"#default_value" => $data["reg_type_id"],
				"#options" => $this->get_types(),
				"#description" => t("The registration type.")
				);

			$retval["reg_status_id"] = array(
				"#title" => t("Status"),
				"#type" => "select",
				"#default_value" => $data["reg_status_id"],
				"#options" => $this->get_statuses(),
				"#description" => t("The member's status.")
				);

		}

		$retval["first"] = array(
			"#type" => "textfield",
			"#title" => t("First Name"),
			"#description" => t("Your real first name"),
			"#size" => $this->get_constant("FORM_TEXT_SIZE_SMALL"),
			"#required" => true,
			"#default_value" => $data["first"],
			);
		$retval["middle"] = array(
			"#type" => "textfield",
			"#title" => t("Middle Name"),
			"#size" => $this->get_constant("FORM_TEXT_SIZE_SMALL"),
			"#default_value" => $data["middle"],
			);
		$retval["last"] = array(
			"#type" => "textfield",
			"#title" => t("Last Name"),
			"#description" => t("Your real last name"),
			"#size" => $this->get_constant("FORM_TEXT_SIZE_SMALL"),
			"#required" => true,
			"#default_value" => $data["last"],
			);

		//
		// Explode our date into an array for the dropdowns.
		//
		$date_array = array();
		if (!empty($data["birthdate"])) {
			$date_array = $this->get_date_array($data["birthdate"]);
		}
		
		$retval["birthdate"] = array(
			"#type" => "date",
			"#title" => t("Date of Birth"),
			"#description" => t("Your date of birth"),
			"#required" => true,
			"#default_value" => $date_array,
			);
		$retval["email"] = array(
			"#type" => "textfield",
			"#title" => t("Your email address"),
			"#description" => "",
			"#size" => $this->get_constant("FORM_TEXT_SIZE_SMALL"),
			"#required" => true,
			"#default_value" => $data["email"],
			);
		$retval["email2"] = array(
			"#type" => "textfield",
			"#title" => t("Confirm email address"),
			"#size" => $this->get_constant("FORM_TEXT_SIZE_SMALL"),
			"#required" => true,
			"#default_value" => $data["email"],
			);

		$shirt_sizes = $this->get_shirt_sizes();
		$shirt_sizes[""] = t("Select");
		ksort($shirt_sizes);
		$retval["shirt_size_id"] = array(
			"#type" => "select",
			"#title" => t("Shirt Size"),
			"#description" => t("(For Sponsors and Supersponsors only.)"),
			"#default_value" => $data["shirt_size_id"],
			"#options" => $shirt_sizes
			);

		$path = variable_get(
			$this->get_constant("FORM_ADMIN_CONDUCT_PATH"), "");
		if (!empty($path) 
			&& empty($id)
			&& !$this->in_admin()
			) {
			$retval["conduct"] = array(
				"#type" => "checkbox",
				"#title" => t("I agree to comply with the") . "<br>" 
					. l(t("Standards of Conduct"), $path),
				"#description" => t("You must agree to comply with the " 
					. l(t("Standards of Conduct"), $path))
					. t(" in order to purchase a membership."),
				"#required" => true,
				"#default_value" => $data["conduct"],
			);
		}

		return($retval);

	} // End of form()


	/**
	* Get level options for a membership.
	*
	* @return array An array representing a single form element of 
	*	radio buttons.
	*/
	function get_level_options(&$data) {

		$levels = $this->get_valid_levels();
		$level_options = array();

		$dest = drupal_get_destination();

		foreach ($levels as $key => $value) {
			$id = $value["id"];
			$name = $value["name"];
			$price = $value["price"];
			$desc = $value["description"];
			$string = "$name <b>(\$$price USD)</b>";

			//
			// If we an admin, give a link to edit the description.
			//
			if ($this->is_admin()) {
				$url = "admin/reg/levels/list/" . $id . "/edit";
				$string .= " " . l(t("[Edit this blurb]"), $url, "", 
					$dest);
			}

			$string .= "<br>\n"
				. nl2br($desc)
				;
			$level_options[$key] = $string;
		}

		$retval = array(
			"#type" => "radios",
			"#title" => t("Membership Type"),
			"#description" => t("Which membership type would you like?"),
			"#options" => $level_options,
			"#default_value" => $data["reg_level_id"],
			);

		return($retval);

	} // End of get_level_options()


	/**
	* This internal function creates the credit card portion of the 
	*	registration form.
	*/
	function form_cc($id, $data, $cc_gateway) {

		//
		// Set defaults if we don't have any
		//
		if (empty($data["donation"])) {
			$data["donation"] = "0.00";
		}

		if (empty($data["cc_exp"]["month"])) {
			$data["cc_exp"]["month"] = date("n");
		}

		if (empty($data["cc_exp"]["year"])) {
			$data["cc_exp"]["year"] = date("Y");
		}

		$retval = array(
			"#type" => "fieldset",
			//"#title" => "Billing Information",
			"#title" => "Payment Information",
			//
			// Render elements with our custom theme code
			//
			"#theme" => "reg_theme"
			);

		if (!$this->in_admin()) {
			$retval["reg_level_id"] = $this->get_level_options($data);
		}

		if ($this->in_admin()) {

			$types = $this->get_payment_types();
			$types[""] = t("Select");

			$retval["reg_payment_type_id"] = array(
				"#title" => t("Payment Type"),
				"#type" => "select",
				"#options" => $types,
				"#description" => t("How did the member pay for their "
					. "registration?"),
				"#required" => true,
				"#default_value" => $data["reg_payment_type_id"],
				);

		}

		$retval["cc_type_id"] = array(
			"#title" => t("Credit Card Type"),
			"#type" => "select",
			"#options" => $this->get_cc_types(),
			"#default_value" => $data["cc_type_id"],
			);

		$retval["cc_num"] = array(
			"#title" => t("Credit Card Number"),
			"#description" => t("Your Credit Card Number"),
			"#type" => "textfield",
			"#size" => $this->get_constant("FORM_TEXT_SIZE_SMALL"),
			"#default_value" => $data["cc_num"],
			);


		if ($this->is_test_mode()) {
			$retval["cc_num"]["#description"] = t("Running in test mode.  "
				. "Just enter any old number.");

		} else if ($cc_gateway->is_test_mode()) {
			$retval["cc_num"]["#description"] = t("Gateway is running in "
				. "test mode.  Your card will NOT be charged.  "
				. "Test CC num: 4222222222222");

		}

		if ($this->in_admin()) {
			$retval["cc_num"]["#description"] = t("Just the last 4 digits "
				. "are necessary.  This card will NOT be charged, since we "
				. "are in the admin.");

		} else {
			//
			// If this is the public facing form, we only accept credit card
			// payments, so require the values to be present.
			//
			$retval["cc_type_id"]["#required"] = true;
			$retval["cc_num"]["#required"] = true;

			$retval["cvv"] = array(
				"#title" => t("Security Code"),
				"#description" => t("The 3 digit code located on the back of "
					. "your credit card."),
				"#type" => "textfield",
				"#size" => 4,
				"#required" => true,
				"#default_value" => $data["cvv"],
				);

		}

		$retval["cc_exp"] = array(
			"#title" => t("Credit Card Expiration"),
			//
			// This is set so that when the child elements are processed,
			// they know they have a parent, and hence get stored
			// properly in the resulting array.
			//
			"#type" => "cc_exp",
			"#tree" => "true",
			);
		$retval["cc_exp"]["month"] = array(
			"#options" => $this->get_cc_exp_months(),
			"#type" => "select",
			"#default_value" => $data["cc_exp"]["month"],
			);

		$retval["cc_exp"]["year"] = array(
			"#options" => $this->get_cc_exp_years(),
			"#type" => "select",
			"#default_value" => $data["cc_exp"]["year"],
			);

		//
		// Create our captcha.
		//
		if (!$this->in_admin()) {

			$retval["reg_captcha"] = $this->captcha->create();
			$retval["reg_captcha"]["#theme"] = "reg_theme";

		}

		if ($this->in_admin()) {

			if (empty($data["badge_cost"])) {
				$data["badge_cost"] = "0.00";
			}

			$retval["badge_cost"] = array(
				"#title" => t("Badge Cost (USD)"),
				"#type" => "textfield",
				"#description" => t("How much did the member pay for this "
					. "membership?<br>"
					. "If they are Staff, Guest, etc. this number should "
					. "normally be <b>0.00</b>."),
				"#required" => true,
				"#size" => $this->get_constant("FORM_TEXT_SIZE_SMALL"),
				"#default_value" => $data["badge_cost"],
				);

		} else {
			//
			// This field and the total field are disabled, since we don't 
			// want the user editing them.
			// 
			$retval["badge_cost"] = array(
				"#title" => t("Membership Cost (USD)"),
				"#type" => "item",
				"#value" => "<span id=\"reg-membership-cost\"></span>",
				"#size" => $this->get_constant("FORM_TEXT_SIZE_SMALL"),
				"#disabled" => true,
				);

		}

		$retval["donation"] = array(
			"#title" => t("Donation (USD)"),
			"#type" => "textfield",
			"#description" => t("Would you like to add an additional "
				. "donation?  Every dollar extra helps us bring you "
				. "more events, more space, and a more sensational "
				. "convention experience overall!"),
			"#default_value" => $data["donation"],
			"#size" => $this->get_constant("FORM_TEXT_SIZE_SMALL"),
			);

		$retval["total"] = array(
			"#title" => t("Total (USD)"),
			"#type" => "item",
			"#value" => "<span id=\"reg-total\"></span>",
			"#description" => t("The total cost of your membership, plus "
				. "any donation.<br>\n"
				. "<b>This will be billed to your credit card when you "
				. "click the button below!</b>")
				,
			"#size" => $this->get_constant("FORM_TEXT_SIZE_SMALL"),
			"#disabled" => true,
			);

		$retval["submit"] = array(
			"#type" => "submit",
			"#value" => "Register"
			);

		return($retval);

	} // End of form_cc()


	/**
	* Our billing address section of form.
	*/
	function form_address_billing($id, &$data) {

		$retval = array(
			"#type" => "fieldset",
			"#title" => "Billing Information",
			//
			// Render elements with our custom theme code
			//
			"#theme" => "reg_theme"
			);

		$retval["address1"] = array(
			"#type" => "textfield",
			"#title" => t("Billing Address Line 1"),
			"#description" => t("The billing address on your credit card."),
			"#size" => $this->get_constant("FORM_TEXT_SIZE_SMALL"),
			"#required" => true,
			"#default_value" => $data["address1"],
			);

		$retval["address2"] = array(
			"#type" => "textfield",
			"#title" => t("Billing Address Line 2"),
			"#size" => $this->get_constant("FORM_TEXT_SIZE_SMALL"),
			"#default_value" => $data["address2"],
			);

		$retval["city"] = array(
			"#type" => "textfield",
			"#title" => t("City"),
			"#size" => $this->get_constant("FORM_TEXT_SIZE_SMALL"),
			"#required" => true,
			"#default_value" => $data["city"],
			);

		$retval["state"] = array(
			"#type" => "textfield",
			"#title" => t("State"),
			"#size" => $this->get_constant("FORM_TEXT_SIZE_SMALL"),
			"#required" => true,
			"#default_value" => $data["state"],
			);

		$retval["zip"] = array(
			"#type" => "textfield",
			"#title" => t("Zip Code"),
			"#size" => $this->get_constant("FORM_TEXT_SIZE_SMALL"),
			"#required" => true,
			"#default_value" => $data["zip"],
			);

		if (empty($data["country"])) {
			$data["country"] = "USA";
		}

		$retval["country"] = array(
			"#type" => "textfield",
			"#title" => t("Country"),
			"#default_value" => "USA",
			"#size" => $this->get_constant("FORM_TEXT_SIZE_SMALL"),
			"#required" => true,
			"#default_value" => $data["country"],
			);

		$retval["phone"] = array(
			"#type" => "textfield",
			"#title" => t("Your phone number"),
			"#description" => t("A phone number where you can be reached."),
			"#size" => $this->get_constant("FORM_TEXT_SIZE_SMALL"),
			"#required" => true,
			"#default_value" => $data["phone"],
			);


		//
		// If we have the first line of a shipping address, assume we're
		// showing the form.
		//
		if (!empty($data["shipping_address1"])) {
			$data["shipping_checkbox"] = true;
		}

		$retval["shipping_checkbox"] = array(
			"#type" => "checkbox",
			"#title" => t("Send receipt to a different address?"),
			"#description" => t("Is the address of the person being "
				. "registered different from the billing address?"),
			"#default_value" => $data["shipping_checkbox"],
			);

		$retval["no_receipt"] = array(
			"#type" => "checkbox",
			"#title" => t("Do NOT mail a receipt"),
			"#description" => t("Check this if you do NOT want a receipt "
				. "sent in the mail.  You will still receive an email "
				. "confirmation."),
			"#default_value" => $data["no_receipt"],
			);

		//
		// Only display our registration button early if we are editing
		// a registration.
		//
		if ($this->in_admin() && !empty($data["id"])) {
			$retval["submit"] = array(
				"#type" => "submit",
				"#value" => t("Save")
				);
		}

		return($retval);

	} // End of form_address_bill()


	/**
	* Our shipping address section of form.
	*/
	function form_address_shipping($id, &$data) {

		$retval = array(
			"#type" => "fieldset",
			"#title" => "Shipping Information",
			//"#collapsible" => true,
			//"#collapsed" => true,
			"#attributes" => array("class" => "reg-hidden"),
			//
			// Render elements with our custom theme code
			//
			"#theme" => "reg_theme"
			);

		$retval["shipping_name"] = array(
			"#type" => "textfield",
			"#title" => t("Shipping Name"),
			"#size" => $this->get_constant("FORM_TEXT_SIZE_SMALL"),
			"#description" => t("If there is a company name or similar, please "
				. "enter it here."),
			"#default_value" => $data["shipping_name"],
			);

		$retval["shipping_address1"] = array(
			"#type" => "textfield",
			"#title" => t("Shipping Address Line 1"),
			"#size" => $this->get_constant("FORM_TEXT_SIZE_SMALL"),
			"#description" => t("Fill this out if the address of the "
				. "person being registered is different from the billing "
				. "address."),
			"#default_value" => $data["shipping_address1"],
			);

		$retval["shipping_address2"] = array(
			"#type" => "textfield",
			"#title" => t("Shipping Address Line 2"),
			"#size" => $this->get_constant("FORM_TEXT_SIZE_SMALL"),
			"#default_value" => $data["shipping_address2"],
			);

		$retval["shipping_city"] = array(
			"#type" => "textfield",
			"#title" => t("Shipping City"),
			"#size" => $this->get_constant("FORM_TEXT_SIZE_SMALL"),
			"#default_value" => $data["shipping_city"],
			);

		$retval["shipping_state"] = array(
			"#type" => "textfield",
			"#title" => t("Shipping State"),
			"#size" => $this->get_constant("FORM_TEXT_SIZE_SMALL"),
			"#default_value" => $data["shipping_state"],
			);

		$retval["shipping_zip"] = array(
			"#type" => "textfield",
			"#title" => t("Shipping Zip Code"),
			"#size" => $this->get_constant("FORM_TEXT_SIZE_SMALL"),
			"#default_value" => $data["shipping_zip"],
			);

		$retval["shipping_country"] = array(
			"#type" => "textfield",
			"#title" => t("Shipping Country"),
			"#default_value" => "USA",
			"#size" => $this->get_constant("FORM_TEXT_SIZE_SMALL"),
			"#default_value" => $data["shipping_country"],
			);

		//
		// Only display our registration button early if we are editing
		// a registration.
		//
		if ($this->in_admin() && !empty($data["id"])) {
			$retval["submit"] = array(
				"#type" => "submit",
				"#value" => t("Save")
				);
		}

		return($retval);

	} // End of form_address_shipping()


} // End of reg_form class

