<?php

/**
* This class holds the main reigstration forms.
*/
class reg_form {

	/**
	* Define constants for form values
	*/
	const FORM_ADMIN_FAKE_CC = "reg_fake_cc";
	const FORM_ADMIN_CONDUCT_PATH = "reg_conduct_path";
	const FORM_ADMIN_FAKE_DATA = "reg_fake_data";

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
			$data = reg_member::load_reg($id);
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
		if (empty($id)) {
			$retval["cc"] = self::form_cc($id, $data);
		}

		return($retval);

	} // End of reg()


	/**
	* Are we in the admin section?  If we are, certain checks are not
	* done and certain fields are not displayed.
	*
	* @return boolean True is we are in the admin section, false otherwise.
	*/
	static function in_admin() {

		if (arg(0) == "admin") {
			return(true);
		}

		return(false);

	} // End in_admin()


	/**
	* Return the current Drupal path.
	*/
	static function get_path() {

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
	static function in_fake_form() {

		//
		// If fake data isn't set/allowed, then stop right here.
		//
		if (!self::is_fake_data()) {
			return(false);
		}

		//
		// If the last "page" in our URL is "fake", return true.
		//
		$uri = self::get_path();
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
	*/
	static function reg_validate(&$form_id, &$data) {

		//
		// If we have no payment type (such as coming through the public
		// interface), set it to credit card.
		//
		if (empty($data["reg_payment_type_id"])) {
			$data["reg_payment_type_id"] = 1;
		}

		//
		// If we have not transaction type, set it to "purchase".
		//
		if (empty($data["reg_trans_type_id"])) {
			$data["reg_trans_type_id"] = 1;
		}

		if ($data["email"] != $data["email2"]) {
			$error = t("Email addresses do not match!");
			form_set_error("email2", $error);
			reg_log::log($error, "", WATCHDOG_WARNING);
		}

		//
		// If we're in the admin, we can skip alot of this stuff.
		//
		if (self::in_admin()) {

			//
			// Make sure the badge nuber is valid.
			//
			reg::is_badge_num_valid($data["badge_num"]);
			reg::is_badge_num_available($data["reg_id"], 
				$data["badge_num"]);

			//
			// Charge this early since we're baiing out.
			//
			$reg_trans_id = reg::charge_cc($data, true);
			self::$reg_trans_id = $reg_trans_id;

			return(null);
		}

		//
		// Assume everything is okay, unless proven otherwise.
		//
		$okay = true;

		//
		// Sanity checking on our donation amount.
		//
		if (!reg::is_valid_float($data["donation"])
			&& $data["donation"] != ""
			) {
			$error = t("Donation '%donation%' is not a number!",
				array("%donation%" => $data["donation"])
				);
			form_set_error("donation", $error);
			reg_log::log($error, "", WATCHDOG_WARNING);
			$okay = false;
		} 

		if (reg::is_negative_number($data["donation"])) {
			$error = t("Donation '%donation%' cannot be a negative amount!",
				array("%donation%" => $data["donation"])
				);
			form_set_error("donation", $error);
			reg_log::log($error, "", WATCHDOG_WARNING);
			$okay = false;
		}
        
		//
		// Sanity checking on the credit card expiration.
		//
		$month = date("n");
		$year = date("Y");

		if ($data["cc_exp"]["year"] == $year) {
			if ($data["cc_exp"]["month"] <= $month) {
				$error = t("Credit card is expired");
				form_set_error("cc_exp][month", $error);
				reg_log::log($error, "", WATCHDOG_WARNING);
				$okay = false;
			}
		}

		//
		// Make sure our registration level is valid
		//
		if (empty($data["reg_level_id"])) {
			$error = t("Membership type is required.");
			form_set_error("reg_level_id", $error);
			reg_log::log($error, "", WATCHDOG_WARNING);
		}

		$levels = reg_data::get_valid_levels();
		if (empty($levels[$data["reg_level_id"]])) {
			$error = t("Registration level ID '%level%' is invalid.",
				array("%level%" => $data["reg_level_id"])
				);
			form_set_error("reg_level_id", $error);
			reg_log::log($error, "", WATCHDOG_ERROR);
			$okay = false;
		}

		//
		// If something failed, stop here and do NOT try to charge
		// the credit card.
		//
		if (empty($okay)) {
			return(null);
		}

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

		//
		// The URI to send ourselves to
		//
		$uri = "";

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
				$uri = "admin/reg/members/view/" . $data["reg_id"] . "/edit";

			} else {
				self::reg_submit_new($data);
				$uri = "admin/reg/members";
			}
		}

		//
		// Send the user back to the front page.
		//
		//
		// TODO: Set redirection to verify page?
		// 
		reg::goto_url($uri);

	} // End of registration_form_submit()


	/**
	* Set up messages for a successful new registration.
	*/
	static function reg_submit_new(&$data) {

		$badge_num = reg_member::add_member($data, self::$reg_trans_id);

		$message = t("Congratulations!  Your registration was successful, "
			. "and your badge number is %badge_num%.  ",
			array("%badge_num%" => $badge_num)
			);
		drupal_set_message($message);


		if (!empty($data["cc_type_id"])
			&& !self::in_admin()
			) {
			$data["cc_name"] = reg_data::get_cc_name($data["cc_type_id"], 
				$data["cc_num"]);

			$message = t("Your credit card (%cc_name%) was successfully "
				. "charged for %total_cost%.",
				array("%cc_name%" => $data["cc_name"],
				"%total_cost%" => "$" . $data["total_cost"],
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

		$badge_num = reg_member::update_member($data, $reg_trans_id);

		$message = t("Registration updated!");
		drupal_set_message($message);

	} // End of reg_submit_update()


	/**
	* Are we allowing fake data to be created?
	*
	* @return boolean True if yes, false if no.
	*/
	static function is_fake_data() {
		$retval = variable_get(reg_form::FORM_ADMIN_FAKE_DATA, "");
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
	static function form($id = "", &$data) {

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
		if (self::is_fake_data()) {
			if (!self::in_fake_form()) {

				if (empty($id)) {
					$url = self::get_path() . "/fake";
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
		if (self::in_fake_form()
			&& empty($id)
			) {
			reg_fake::get_data($data);
		}

		$retval["badge_name"] = array(
			"#title" => t("Badge Name"),
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
				"#title" => t("Badge Number"),
				"#type" => "textfield",
				"#description" => t("This must be UNIQUE.  If unsure, leave blank and "
					. "one will be assigned."),
				"#size" => self::FORM_TEXT_SIZE_SMALL,
				"#default_value" => $data["badge_num"],
				);

			$retval["reg_type_id"] = array(
				"#title" => t("Badge Type"),
				"#type" => "select",
				"#default_value" => $data["reg_type_id"],
				"#options" => reg_data::get_types(),
				"#description" => t("The registration type.")
				);

			$retval["reg_status_id"] = array(
				"#title" => t("Status"),
				"#type" => "select",
				"#default_value" => $data["reg_status_id"],
				"#options" => reg_data::get_statuses(),
				"#description" => t("The member's status.")
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

		if (!self::in_admin()) {

			$levels = reg_data::get_valid_levels();
			$level_options = array();
			foreach ($levels as $key => $value) {
				$name = $value["name"];
				$price = $value["price"];
				$desc = $value["description"];
				$string = "$name <b>(\$$price USD)</b><br>\n"
					. "$desc"
					;
				$level_options[$key] = $string;
			}

			$retval["reg_level_id"] = array(
				"#type" => "radios",
				"#title" => t("Membership Type"),
				"#description" => t("Which membership type would you like?"),
				"#options" => $level_options,
				"#default_value" => $data["reg_level_id"],
				);

		}

		$shirt_sizes = reg_data::get_shirt_sizes();
		$shirt_sizes[""] = t("Select");
		ksort($shirt_sizes);
		$retval["shirt_size_id"] = array(
			"#type" => "select",
			"#title" => t("Shirt Size"),
			"#description" => t("(For Sponsors and Super Sponsors only.)"),
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
				"#default_value" => $data["conduct"],
			);
		}

		//
		// Only display our registration button early if we are editing
		// or adding from the admin.
		//
		if (!empty($id)
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
	static function form_cc($id, $data) {

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
			"#title" => "Payment Information",
			//
			// Render elements with our custom theme code
			//
			"#theme" => "reg_theme"
			);

		if (self::in_admin()) {

			$types = reg_data::get_payment_types();
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
			"#options" => reg_data::get_cc_types(),
			"#required" => true,
			"#default_value" => $data["cc_type_id"],
			);

		$retval["cc_num"] = array(
			"#title" => t("Credit Card Number"),
			"#description" => t("Your Credit Card Number"),
			"#type" => "textfield",
			"#size" => self::FORM_TEXT_SIZE,
			"#required" => true,
			"#default_value" => $data["cc_num"],
			);

		if (reg::is_test_mode()) {
			$retval["cc_num"]["#description"] = t("Running in test mode.  "
				. "Just enter any old number.");
		}

		if (self::in_admin()) {
			$retval["cc_num"]["#description"] = t("Just the last 4 digits "
				. "are necessary.  This card will NOT be charged, since we "
				. "are in the admin.");
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
			"#options" => reg_data::get_cc_exp_months(),
			"#type" => "select",
			"#default_value" => $data["cc_exp"]["month"],
			);

		$retval["cc_exp"]["year"] = array(
			"#options" => reg_data::get_cc_exp_years(),
			"#type" => "select",
			"#default_value" => $data["cc_exp"]["year"],
			);

		if (self::in_admin()) {
			$retval["badge_cost"] = array(
				"#title" => t("Badge Cost"),
				"#type" => "textfield",
				"#description" => t("How much did the member pay for this "
					. "membership?<br>"
					. "If they are staff, Guest, etc. this number should "
					. "normally be ZERO."),
				"#required" => true,
				"#size" => self::FORM_TEXT_SIZE_SMALL,
				"#default_value" => $data["badge_cost"],
				);

		} else {
			//
			// This field and the total field are disabled, since we don't 
			// want the user editing them.
			// 
			$retval["badge_cost"] = array(
				"#title" => t("Membership Cost (USD)"),
				"#type" => "textfield",
				"#description" => t("The cost for your membership."),
				"#size" => self::FORM_TEXT_SIZE_SMALL,
				"#disabled" => true,
				);

		}

		$retval["donation"] = array(
			"#title" => t("Donation (USD)"),
			"#type" => "textfield",
			"#description" => t("Would you like to make an additional "
				. "donation?"),
			"#default_value" => $data["donation"],
			"#size" => self::FORM_TEXT_SIZE_SMALL,
			);

		$retval["total"] = array(
			"#title" => t("Total (USD)"),
			"#type" => "textfield",
			"#description" => t("The total cost of your membership, plus "
				. "any donation.<br>\n")
				. t("<b>This will be billed to your credit card when you click the button below!</b>")
				,
			"#size" => self::FORM_TEXT_SIZE_SMALL,
			"#disabled" => true,
			);

		$retval["submit"] = array(
			"#type" => "submit",
			"#value" => "Register"
			);

		return($retval);

	} // End of _registration_form_cc()


} // End of reg_form class

