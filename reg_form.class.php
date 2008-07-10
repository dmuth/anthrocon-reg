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
	* Temporarily stores our badge number between form validation 
	*	and submission.
	*/
	static private $badge_num;

	/**
	* Also store our data for going between validation and 
	*	submission functions.
	*/ 
	static private $data;


	/**
	* This function creates the data structure for our main registration form.
	*
	* @return array Associative array of registration form.
	*/
	static function reg() {

		$retval = array();

		$retval["member"] = self::_registration_form();
		$retval["cc"] = self::_registration_form_cc();

		return($retval);

	} // End of registration_form()


	/**
	* This function is called to validate the form data.
	* If there are any issues, form_set_error() should be called so
	* that form processing does not continue.
	*/
	static function reg_validate(&$form_id, &$data) {

		//
		// Assume everything is okay, unless proven otherwise.
		//
		$okay = true;

		if ($data["email"] != $data["email2"]) {
			$error = "Email addresses do not match!";
			form_set_error("email2", $error);
			$okay = false;
		}

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

		if ($reg_trans_id) {
			$badge_num = reg::add_member($data, $reg_trans_id);
			//
			// Store our badge number, since we'll be referencing it again in
			// the submit funcition.
			//
			self::$badge_num = $badge_num;

			//
			// Heck, store our data too
			//
			self::$data = $data;
		}

// TEST
print_r($data);exit();

		//
		// TODO: Set redirection to verify page?
		// 

	} // End of registration_form_validate()


	/**
	* All the registration form data checks out.  
	*/
	static function reg_submit(&$form_id, &$data) {

		$message = t("Congratulations!  Your registration was successful, and your badge number is %badge_num%.  ",
			array("%badge_num%" => self::$badge_num)
			);
		drupal_set_message($message);

		$message = t("Your credit card (%cc_name%) was successfully charged for %total_cost%.",
		array("%cc_name%" => self::$data["cc_name"],
			"%total_cost%" => "$" . self::$data["total_cost"],
			));
		drupal_set_message($message);

		$message = t("You will receive a conformation email sent to %email% shortly.",
			array("%email%" => self::$data["email"])
			);
		drupal_set_message($message);

		//
		// Send the user back to the front page.
		//
		return("");

	} // End of registration_form_submit()


	/**
	* This function function creates the membership section of our 
	*	registration form.
	*/
	static private function _registration_form() {

		$retval = array(
			"#type" => "fieldset",
			"#title" => "Membership Information",
			//
			// Render elements with our custom theme code
			//
			"#theme" => "reg_theme"
			);

		$retval["badge_name"] = array(
			"#title" => "Badge Name",
			"#type" => "textfield",
			"#description" => "The name printed on your conbadge.  "
				. "This may be your real name or a nickname. "
				. "It may be blank. ",
			"#size" => self::FORM_TEXT_SIZE_SMALL,
			);
		$retval["first"] = array(
			"#type" => "textfield",
			"#title" => "First Name",
			"#description" => "Your real first name",
			"#size" => self::FORM_TEXT_SIZE,
			"#required" => true,
			);
		$retval["middle"] = array(
			"#type" => "textfield",
			"#title" => "Middle Name",
			"#description" => "Your real middle name",
			"#size" => self::FORM_TEXT_SIZE,
			);
		$retval["last"] = array(
			"#type" => "textfield",
			"#title" => "Last Name",
			"#description" => "Your real last name",
			"#size" => self::FORM_TEXT_SIZE,
			"#required" => true,
			);
		$retval["birthday"] = array(
			"#type" => "date",
			"#title" => "Date of Birth",
			"#description" => "Your date of birth",
			"#size" => self::FORM_TEXT_SIZE,
			"#required" => true,
			);
		$retval["address1"] = array(
			"#type" => "textfield",
			"#title" => "Address Line 1",
			"#description" => "Your mailing address",
			"#size" => self::FORM_TEXT_SIZE,
			"#required" => true,
			);
		$retval["address2"] = array(
			"#type" => "textfield",
			"#title" => "Address Line 2",
			"#description" => "Additional address information, "
				. "such as P.O Box number",
			"#size" => self::FORM_TEXT_SIZE,
			);
		$retval["city"] = array(
			"#type" => "textfield",
			"#title" => "City",
			"#description" => "Your city",
			"#size" => self::FORM_TEXT_SIZE,
			"#required" => true,
			);
		$retval["state"] = array(
			"#type" => "textfield",
			"#title" => "State",
			"#description" => "Your state",
			"#size" => self::FORM_TEXT_SIZE,
			"#required" => true,
			);
		$retval["zip"] = array(
			"#type" => "textfield",
			"#title" => "Zip Code",
			"#description" => "Your Zip/Postal code",
			"#size" => self::FORM_TEXT_SIZE,
			"#required" => true,
			);
		$retval["country"] = array(
			"#type" => "textfield",
			"#title" => "Country",
			"#description" => "Your country",
			"#default_value" => "USA",
			"#size" => self::FORM_TEXT_SIZE,
			"#required" => true,
			);
		$retval["email"] = array(
			"#type" => "textfield",
			"#title" => "Your email address",
			"#description" => "",
			"#size" => self::FORM_TEXT_SIZE,
			"#required" => true,
			);
		$retval["email2"] = array(
			"#type" => "textfield",
			"#title" => "Confirm email address",
			"#description" => "Please re-type your email address to ensure there "
				. "were no typos.",
			"#size" => self::FORM_TEXT_SIZE,
			"#required" => true,
			);
		$retval["phone"] = array(
			"#type" => "textfield",
			"#title" => "Your phone number",
			"#description" => "A phone number where we can reach you.",
			"#size" => self::FORM_TEXT_SIZE,
			"#required" => true,
			);

		$path = variable_get(self::FORM_ADMIN_CONDUCT_PATH, "");
		if (!empty($path)) {
			$retval["conduct"] = array(
				"#type" => "checkbox",
				"#title" => "I agree with the<br>" 
					. l("Standards of Conduct", $path),
				"#description" => "You must agree with the " 
					. l("Standards of Conduct", $path) 
					. " in order to purchase a membership.",
				"#required" => true,
			);
		}

		return($retval);

	} // End of _registration_form()


	/**
	* This internal function creates the credit card portion of the 
	*	registration form.
	*/
	static private function _registration_form_cc() {

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

