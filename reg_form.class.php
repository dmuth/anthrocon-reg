<?php

/**
* This class is responsible for holding registration system-related
* forms.
*/
class reg_form {

	/**
	* Define constants for form values
	*/
	const FORM_ADMIN_FAKE_CC = "reg_fake_cc";
	const FORM_ADMIN_CONDUCT_PATH = "reg_conduct_path";

	/**
	* Define other constants
	*/
	const FORM_TEXT_SIZE = 40;
	const FORM_TEXT_SIZE_SMALL = 20;

	/**
	* Temporarily store this value for going between the validation
	*	and submission functions.
	*/
	static private $reg_trans_id;


	/**
	* This function creates the data structure for our main registration form.
	*
	* @return array Associative array of registration form.
	*/
	static function reg($id = "") {

		$retval = array();

		if (!empty($id)) {
			$data = reg_admin::load_reg($id);
			$retval["reg_id"] = array(
				"#type" => "hidden",
				"#value" => $id,
				);

		}

		$retval["member"] = self::form($id, $data);

		//
		// Don't display our credit card info when we're editing, as
		// only admins can edit a registration.
		//
		if (!self::in_admin()) {
			$retval["cc"] = self::form_cc();
		}

		$retval["member"]["TEST"] = array(
			"#type" => "item",
			"#title" => "NOTE",
			"#value" => "What do I want down here for payment processing, anyway?"
			);

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
	* This function is called to validate the form data.
	* If there are any issues, form_set_error() should be called so
	* that form processing does not continue.
	*/
	static function reg_validate(&$form_id, &$data) {

		//
		// If we're in the admin, we can skip alot of this stuff.
		//
		if (self::in_admin()) {

			//
			// Make sure the badge nuber is valid.
			//
			if (isset($data["badge_num"])) {
				$badge_num = intval($data["badge_num"]);
				if ($data["badge_num"] != (string)$badge_num) {
					$error = t("Badge number '%num%' is not a number!",
						array("%num%" => $data["badge_num"])
						);
					form_set_error("badge_num", $error);
				}

				if ($badge_num < 0) {
					$error = t("Badge number cannot be negative!");
					form_set_error("badge_num", $error);
				}

				if (!reg::is_badge_num_available(
					$data["reg_id"], $data["badge_num"])) {
					$error = t("Badge number '%num%' is already in use!",
						array("%num%" => $data["badge_num"])
						);
					form_set_error("badge_num", $error);
				}

			}

			if ($data["email"] != $data["email2"]) {
				$error = "Email addresses do not match!";
				form_set_error("email2", $error);
			}

			return(null);
		}

		//
		// Assume everything is okay, unless proven otherwise.
		//
		$okay = true;

		//
		// Sanity checking on our donation amount.
		//
		$donation_float = floatval($data["donation"]);
		if ($data["donation"] != (string)$donation_float) {
			$error = "Donation '" . $data["donation"] . "' is not a number!";
			form_set_error("donation", $error);
			$okay = false;

		} else if ($data["donation"] < 0) {
			form_set_error("donation", "Donation cannot be a negative amount!");
			$okay = false;

		}
        
		//
		// Sanity checking on the credit card expiration.
		//
		$month = date("n");
		$year = date("Y");

		if ($data["cc_exp"]["year"] == $year) {
			if ($data["cc_exp"]["month"] <= $month) {
				form_set_error("cc_exp][month", "Credit card is expired");
				$okay = false;
			}
		}

		//
		// If something failed, stop here and do NOT try to charge
		// the credit card.
		//
		if (empty($okay)) {
			return(null);
		}

//
// TODO:
// We eventually need to ask for a registration level on the reg form.
//
$data["reg_level_id"] = 3;

		//
		// Make the transaction.  If it is successful, then add a new member.
		//
		$reg_trans_id = reg::charge_cc($data);

		self::$reg_trans_id = $reg_trans_id;

	} // End of registration_form_validate()


	/**
	* All the registration form data checks out.  
	*/
	static function reg_submit(&$form_id, &$data) {

		if (!self::in_admin()) {
			//
			// Front-end submissions are always new.
			//
			self::reg_submit_new($data);

		} else {
			//
			// Submission from the admin.  Are we updating or creating a 
			// new member?
			//
			if (!empty($data["reg_id"])) {
				self::reg_submit_update($data);
				return("admin/reg/members/view/" . $data["reg_id"] . "/edit");

			} else {
				self::reg_submit_new($data);
				return("admin/reg/members");
			}
		}

		//
		// Send the user back to the front page.
		//
		//
		// TODO: Set redirection to verify page?
		// 
		return("");

	} // End of registration_form_submit()


	/**
	* Set up messages for a successful new registration.
	*/
	static function reg_submit_new(&$data) {

		$badge_num = reg::add_member($data, self::$reg_trans_id);

		$message = t("Congratulations!  Your registration was successful, "
			. "and your badge number is %badge_num%.  ",
			array("%badge_num%" => $badge_num)
			);
		drupal_set_message($message);

		if (!empty($data["cc_name"])) {
			$message = t("Your credit card (%cc_name%) was successfully "
				. "charged for %total_cost%.",
				array("%cc_name%" => $data["cc_name"],
				"%total_cost%" => "$" . self::$data["total_cost"],
				));
			drupal_set_message($message);
		}

		$message = t("You will receive a conformation email sent "
			. "to %email% shortly.",
			array("%email%" => $data["email"])
			);
		drupal_set_message($message);

	} // End of reg_submit_new()


	/**
	* Process an updated registration.
	* We will update the database and log this.
	*/
	static function reg_submit_update(&$data) {

		$badge_num = reg::update_member($data, $reg_trans_id);

		$message = t("Registration updated!");
		drupal_set_message($message);

	} // End of reg_submit_update()


	/**
	* This function function creates the membership section of our 
	*	registration form.
	*
	* @param integer $id reg_id if we are editing a record.
	*
	* @param array $data Associative array of membership info.
	*/
	static function form($id = "", $data = "") {

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

		$retval["badge_name"] = array(
			"#title" => "Badge Name",
			"#type" => "textfield",
			"#description" => t("The name printed on your conbadge.  ")
				. t("This may be your real name or a nickname. ")
				. t("It may be blank. "),
			"#size" => self::FORM_TEXT_SIZE_SMALL,
			"#default_value" => $data["badge_name"],
			);

		//
		// Display additional options for the admin to set.
		//
		if (self::in_admin()) {
			$retval["badge_num"] = array(
				"#title" => "Badge Number",
				"#type" => "textfield",
				"#description" => "This must be UNIQUE.  If unsure, leave blank and "
					. "one will be assigned.",
				"#size" => self::FORM_TEXT_SIZE_SMALL,
				"#default_value" => $data["badge_num"],
				);

			$retval["reg_type_id"] = array(
				"#title" => "Badge Type",
				"#type" => "select",
				"#default_value" => $data["reg_type_id"],
				"#options" => reg::get_types(),
				"#description" => "The registration type."
				);

			$retval["reg_status_id"] = array(
				"#title" => "Status",
				"#type" => "select",
				"#default_value" => $data["reg_status_id"],
				"#options" => reg::get_statuses(),
				"#description" => "The member's status."
				);

		}

		$retval["first"] = array(
			"#type" => "textfield",
			"#title" => t("First Name"),
			"#description" => t("Your real first name"),
			"#size" => self::FORM_TEXT_SIZE,
			"#required" => true,
			"#default_value" => $data["first"],
			);
		$retval["middle"] = array(
			"#type" => "textfield",
			"#title" => t("Middle Name"),
			"#description" => t("Your real middle name"),
			"#size" => self::FORM_TEXT_SIZE,
			"#default_value" => $data["middle"],
			);
		$retval["last"] = array(
			"#type" => "textfield",
			"#title" => t("Last Name"),
			"#description" => t("Your real last name"),
			"#size" => self::FORM_TEXT_SIZE,
			"#required" => true,
			"#default_value" => $data["last"],
			);

		//
		// Explode our date into an array for the dropdowns.
		//
		$date_array = array();
		if (!empty($data["birthdate"])) {
			$date_array = explode("-", $data["birthdate"]);
			$date_array["year"] = $date_array[0];
			$date_array["month"] = $date_array[1];
			$date_array["day"] = $date_array[2];
		}
		
		$retval["birthday"] = array(
			"#type" => "date",
			"#title" => t("Date of Birth"),
			"#description" => t("Your date of birth"),
			"#size" => self::FORM_TEXT_SIZE,
			"#required" => true,
			"#default_value" => $date_array,
			);
		$retval["address1"] = array(
			"#type" => "textfield",
			"#title" => t("Address Line 1"),
			"#description" => t("Your mailing address"),
			"#size" => self::FORM_TEXT_SIZE,
			"#required" => true,
			"#default_value" => $data["address1"],
			);
		$retval["address2"] = array(
			"#type" => "textfield",
			"#title" => t("Address Line 2"),
			"#description" => t("Additional address information, "
				. "such as P.O Box number"),
			"#size" => self::FORM_TEXT_SIZE,
			"#default_value" => $data["address2"],
			);
		$retval["city"] = array(
			"#type" => "textfield",
			"#title" => t("City"),
			"#description" => t("Your city"),
			"#size" => self::FORM_TEXT_SIZE,
			"#required" => true,
			"#default_value" => $data["city"],
			);
		$retval["state"] = array(
			"#type" => "textfield",
			"#title" => t("State"),
			"#description" => t("Your state"),
			"#size" => self::FORM_TEXT_SIZE,
			"#required" => true,
			"#default_value" => $data["state"],
			);
		$retval["zip"] = array(
			"#type" => "textfield",
			"#title" => t("Zip Code"),
			"#description" => t("Your Zip/Postal code"),
			"#size" => self::FORM_TEXT_SIZE,
			"#required" => true,
			"#default_value" => $data["zip"],
			);
		$retval["country"] = array(
			"#type" => "textfield",
			"#title" => t("Country"),
			"#description" => t("Your country"),
			"#default_value" => "USA",
			"#size" => self::FORM_TEXT_SIZE,
			"#required" => true,
			"#default_value" => $data["country"],
			);
		$retval["email"] = array(
			"#type" => "textfield",
			"#title" => t("Your email address"),
			"#description" => "",
			"#size" => self::FORM_TEXT_SIZE,
			"#required" => true,
			"#default_value" => $data["email"],
			);
		$retval["email2"] = array(
			"#type" => "textfield",
			"#title" => t("Confirm email address"),
			"#description" => t("Please re-type your email address to "
				. "ensure there were no typos."),
			"#size" => self::FORM_TEXT_SIZE,
			"#required" => true,
			"#default_value" => $data["email"],
			);
		$retval["phone"] = array(
			"#type" => "textfield",
			"#title" => t("Your phone number"),
			"#description" => t("A phone number where we can reach you."),
			"#size" => self::FORM_TEXT_SIZE,
			"#required" => true,
			"#default_value" => $data["phone"],
			);

		$shirt_sizes = reg::get_shirt_sizes();
		$shirt_sizes[""] = t("Select");
		ksort($shirt_sizes);
		$retval["shirt_size_id"] = array(
			"#type" => "select",
			"#title" => "Shirt Size",
			"#description" => t("(For Sponsors and Super Sponsors)"),
			"#default_value" => $data["shirt_size_id"],
			"#options" => $shirt_sizes
			);

		$path = variable_get(self::FORM_ADMIN_CONDUCT_PATH, "");
		if (!empty($path) 
			&& empty($id)
			&& !self::in_admin()
			) {
			$retval["conduct"] = array(
				"#type" => "checkbox",
				"#title" => t("I agree with the") . "<br>" 
					. l(t("Standards of Conduct"), $path),
				"#description" => t("You must agree with the " 
					. l(t("Standards of Conduct"), $path))
					. t(" in order to purchase a membership."),
				"#required" => true,
			);
		}

		//
		// Only display our registration button early if we are editing
		// or adding from the admin.
		//
		if (!empty($id)
			|| self::in_admin()
			) {
			$retval["submit"] = array(
				"#type" => "submit",
				"#value" => t("Save")
				);
		}

		return($retval);

	} // End of form()


	/**
	* This internal function creates the credit card portion of the 
	*	registration form.
	*/
	static function form_cc() {

		$retval = array(
			"#type" => "fieldset",
			"#title" => "Payment Information",
			//
			// Render elements with our custom theme code
			//
			"#theme" => "reg_theme"
			);

		$retval["cc_type"] = array(
			"#title" => "Credit Card Type",
			"#type" => "select",
			"#options" => reg::get_cc_types(),
			"#required" => true,
			);

		$retval["cc_num"] = array(
			"#title" => "Credit Card Number",
			"#description" => "Your Credit Card Number",
			"#type" => "textfield",
			"#size" => self::FORM_TEXT_SIZE,
			"#required" => true,
			);

		if (reg::is_test_mode()) {
			$retval["cc_num"]["#description"] = "Running in test mode.  "
				. "Just enter any old number.";
		}

		$retval["cc_exp"] = array(
			"#title" => "Credit Card Expiration",
			//
			// This is set so that when the child elements are processed,
			// they know they have a parent, and hence get stored
			// properly in the resulting array.
			//
			"#type" => "cc_exp",
			"#tree" => "true",
			);
		$retval["cc_exp"]["month"] = array(
			"#options" => reg::get_cc_exp_months(),
			"#type" => "select",
			"#default_value" => date("n"),
			);

		$retval["cc_exp"]["year"] = array(
			"#options" => reg::get_cc_exp_years(),
			"#type" => "select",
			"#default_value" => date("Y"),
			);


		$retval["donation"] = array(
			"#title" => "Donation (USD)",
			"#type" => "textfield",
			"#description" => "Would you like to make an additional donation?",
			"#default_value" => "0.00",
			"#size" => self::FORM_TEXT_SIZE_SMALL,
			);

		$retval["submit"] = array(
			"#type" => "submit",
			"#value" => "Register"
			);

		return($retval);

	} // End of _registration_form_cc()


} // End of reg_form class

