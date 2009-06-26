<?php
/**
* This class holds the core function that generates our registration form.
*/
class Reg_FormCore {

	function __construct(&$reg, &$admin_member, &$watchlist, &$fake, &$captcha, &$log) {
		$this->reg = $reg;
		$this->admin_member = $admin_member;
		$this->watchlist = $watchlist;
		$this->fake = $fake;
		$this->captcha = $captcha;
		$this->log = $log;
	}


	/**
	* Our main function for rendering a form.
	* 
	* @param integer $id Our registration ID, if we are editing a member.
	*
	* @param object $cc_gateway Our credit card gateway.
	*
	* @return array Associative array of registration form.
	*/
	function getForm($id, $cc_gateway) {

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

	} // End of getForm()


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
			"#size" => $this->reg->get_constant("FORM_TEXT_SIZE_SMALL"),
			"#default_value" => $data["badge_name"],
			);

		//
		// Display additional options for the admin to set.
		//
		if ($this->in_admin()) {

			//
			// Is this person on the watchlist?
			//
			$watchlist_data = array(
				"first" => $data["first"],
				"last" => $data["last"],
				);
			$this->watchlist_match = $this->watchlist->search($watchlist_data);

			$retval["badge_num"] = array(
				"#title" => t("Badge Number"),
				"#type" => "textfield",
				"#description" => t("This must be UNIQUE.  If unsure, leave blank and "
					. "one will be assigned."),
				"#size" => $this->reg->get_constant("FORM_TEXT_SIZE_SMALL"),
				"#default_value" => $data["badge_num"],
				);

			$retval["reg_type_id"] = array(
				"#title" => t("Badge Type"),
				"#type" => "select",
				"#default_value" => $data["reg_type_id"],
				"#options" => $this->reg->get_types(),
				"#description" => t("The registration type.")
				);

			$retval["reg_status_id"] = array(
				"#title" => t("Status"),
				"#type" => "select",
				"#default_value" => $data["reg_status_id"],
				"#options" => $this->reg->get_statuses(),
				"#description" => t("The member's status.")
				);

		}

		$retval["first"] = array(
			"#type" => "textfield",
			"#title" => t("First Name"),
			"#description" => t("Your real first name"),
			"#size" => $this->reg->get_constant("FORM_TEXT_SIZE_SMALL"),
			"#required" => true,
			"#default_value" => $data["first"],
			);
		$retval["middle"] = array(
			"#type" => "textfield",
			"#title" => t("Middle Name"),
			"#size" => $this->reg->get_constant("FORM_TEXT_SIZE_SMALL"),
			"#default_value" => $data["middle"],
			);
		$retval["last"] = array(
			"#type" => "textfield",
			"#title" => t("Last Name"),
			"#description" => t("Your real last name"),
			"#size" => $this->reg->get_constant("FORM_TEXT_SIZE_SMALL"),
			"#required" => true,
			"#default_value" => $data["last"],
			);

		//
		// Explode our date into an array for the dropdowns.
		//
		$date_array = array();
		if (!empty($data["birthdate"])) {
			$date_array = $this->reg->get_date_array($data["birthdate"]);
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
			"#size" => $this->reg->get_constant("FORM_TEXT_SIZE_SMALL"),
			"#required" => true,
			"#default_value" => $data["email"],
			);
		$retval["email2"] = array(
			"#type" => "textfield",
			"#title" => t("Confirm email address"),
			"#size" => $this->reg->get_constant("FORM_TEXT_SIZE_SMALL"),
			"#required" => true,
			"#default_value" => $data["email"],
			);

		$shirt_sizes = $this->reg->get_shirt_sizes();
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
			$this->reg->get_constant("FORM_ADMIN_CONDUCT_PATH"), "");

		if (!empty($path) 
			&& empty($id)
			&& !$this->in_admin()
			) {
			$retval["conduct"] = array(
				"#type" => "checkbox",
				//"#type" => "textfield",
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
	* Are we allowing fake data to be created?
	*
	* @return boolean True if yes, false if no.
	*/
	function is_fake_data() {
		$retval = variable_get(
			$this->reg->get_constant("FORM_ADMIN_FAKE_DATA"), "");
		return($retval);
	}


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
			"#size" => $this->reg->get_constant("FORM_TEXT_SIZE_SMALL"),
			"#required" => true,
			"#default_value" => $data["address1"],
			);

		$retval["address2"] = array(
			"#type" => "textfield",
			"#title" => t("Billing Address Line 2"),
			"#size" => $this->reg->get_constant("FORM_TEXT_SIZE_SMALL"),
			"#default_value" => $data["address2"],
			);

		$retval["city"] = array(
			"#type" => "textfield",
			"#title" => t("City"),
			"#size" => $this->reg->get_constant("FORM_TEXT_SIZE_SMALL"),
			"#required" => true,
			"#default_value" => $data["city"],
			);

		$retval["state"] = array(
			"#type" => "textfield",
			"#title" => t("State"),
			"#size" => $this->reg->get_constant("FORM_TEXT_SIZE_SMALL"),
			"#required" => true,
			"#default_value" => $data["state"],
			);

		$retval["zip"] = array(
			"#type" => "textfield",
			"#title" => t("Zip Code"),
			"#size" => $this->reg->get_constant("FORM_TEXT_SIZE_SMALL"),
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
			"#size" => $this->reg->get_constant("FORM_TEXT_SIZE_SMALL"),
			"#required" => true,
			"#default_value" => $data["country"],
			);

		$retval["phone"] = array(
			"#type" => "textfield",
			"#title" => t("Your phone number"),
			"#description" => t("A phone number where you can be reached."),
			"#size" => $this->reg->get_constant("FORM_TEXT_SIZE_SMALL"),
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

			//
			// If on the watchlist, disable the saving button.
			//
			if ($this->watchlist_match) {
				$retval["submit"]["#disabled"] = true;
				$retval["submit"]["#value"] = 
					t("Disabled for members on watchlist");
			}

		}

		return($retval);

	} // End of form_address_billing()


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
			"#size" => $this->reg->get_constant("FORM_TEXT_SIZE_SMALL"),
			"#description" => t("If there is a company name or similar, please "
				. "enter it here."),
			"#default_value" => $data["shipping_name"],
			);

		$retval["shipping_address1"] = array(
			"#type" => "textfield",
			"#title" => t("Shipping Address Line 1"),
			"#size" => $this->reg->get_constant("FORM_TEXT_SIZE_SMALL"),
			"#description" => t("Fill this out if the address of the "
				. "person being registered is different from the billing "
				. "address."),
			"#default_value" => $data["shipping_address1"],
			);

		$retval["shipping_address2"] = array(
			"#type" => "textfield",
			"#title" => t("Shipping Address Line 2"),
			"#size" => $this->reg->get_constant("FORM_TEXT_SIZE_SMALL"),
			"#default_value" => $data["shipping_address2"],
			);

		$retval["shipping_city"] = array(
			"#type" => "textfield",
			"#title" => t("Shipping City"),
			"#size" => $this->reg->get_constant("FORM_TEXT_SIZE_SMALL"),
			"#default_value" => $data["shipping_city"],
			);

		$retval["shipping_state"] = array(
			"#type" => "textfield",
			"#title" => t("Shipping State"),
			"#size" => $this->reg->get_constant("FORM_TEXT_SIZE_SMALL"),
			"#default_value" => $data["shipping_state"],
			);

		$retval["shipping_zip"] = array(
			"#type" => "textfield",
			"#title" => t("Shipping Zip Code"),
			"#size" => $this->reg->get_constant("FORM_TEXT_SIZE_SMALL"),
			"#default_value" => $data["shipping_zip"],
			);

		$retval["shipping_country"] = array(
			"#type" => "textfield",
			"#title" => t("Shipping Country"),
			"#default_value" => "USA",
			"#size" => $this->reg->get_constant("FORM_TEXT_SIZE_SMALL"),
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

			//
			// If on the watchlist, disable the saving button.
			//
			if ($this->watchlist_match) {
				$retval["submit"]["#disabled"] = true;
				$retval["submit"]["#value"] = 
					t("Disabled for members on watchlist");
			}

		}

		return($retval);

	} // End of form_address_shipping()


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

			$types = $this->reg->get_payment_types();
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
			"#options" => $this->reg->get_cc_types(),
			"#default_value" => $data["cc_type_id"],
			);

		$retval["cc_num"] = array(
			"#title" => t("Credit Card Number"),
			"#description" => t("Your Credit Card Number"),
			"#type" => "textfield",
			"#size" => $this->reg->get_constant("FORM_TEXT_SIZE_SMALL"),
			"#default_value" => $data["cc_num"],
			);


		if ($this->reg->is_test_mode()) {
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
			"#options" => $this->reg->get_cc_exp_months(),
			"#type" => "select",
			"#default_value" => $data["cc_exp"]["month"],
			);

		$retval["cc_exp"]["year"] = array(
			"#options" => $this->reg->get_cc_exp_years(),
			"#type" => "select",
			"#default_value" => $data["cc_exp"]["year"],
			);

		//
		// Create our captcha.
		//
		if (!$this->in_admin() && !$this->no_captcha()) {

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
				"#size" => $this->reg->get_constant("FORM_TEXT_SIZE_SMALL"),
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
				"#size" => $this->reg->get_constant("FORM_TEXT_SIZE_SMALL"),
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
			"#size" => $this->reg->get_constant("FORM_TEXT_SIZE_SMALL"),
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
			"#size" => $this->reg->get_constant("FORM_TEXT_SIZE_SMALL"),
			"#disabled" => true,
			);

		$retval["submit"] = array(
			"#type" => "submit",
			"#value" => "Register"
			);

		return($retval);

	} // End of form_cc()


	/**
	* Get level options for a membership.
	*
	* @return array An array representing a single form element of 
	*	radio buttons.
	*/
	function get_level_options(&$data) {

		$levels = $this->reg->get_valid_levels();
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
			if ($this->reg->is_admin()) {
				$url = "admin/reg/settings/levels/list/" . $id . "/edit";
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
	* Are we suppressing the captcha?
	*
	* @return boolean True if there is to be no captcha, false otherwise.
	*/
	function no_captcha() {

		$retval = variable_get($this->reg->get_constant(
			"form_admin_no_captcha"), "");

		return($retval);

	} // End of no_captcha()


	/**
	* Check our captcha.
	*
	* @param integer $captcha The captcha value entered by the user.
	*
	* @return boolean True if the captcha was passed.  False otherwise.
	*/
	function checkCaptcha($captcha) {

		if ($this->no_captcha()) {
			return(true);
		}

		if (!$this->captcha->check($captcha)) {
			$message = t("Incorrect answer to math question.");
			form_set_error("reg_captcha", $message);
			$this->log->log($message, "", WATCHDOG_WARNING);
			return(false);
		}

		//
		// Assume success
		//
		return(true);

	} // End of checkCaptcha()


	/**
	* Check to see if our Standards of Conduct were agreed to.
	*
	* @param boolea $conduct Did the user agree to follow our 
	*	Standards of Conduct?
	*
	* @return boolean True if the user agreed (or there was no 
	*	standards of conduct).  False otherwise.
	*/
	function checkStandardsOfConduct($conduct) {

		$path = variable_get(
			$this->reg->get_constant("FORM_ADMIN_CONDUCT_PATH"), "");

		//
		// No standards of conduct to check.  All is well.
		//
		if (empty($path)) {
			return(true);
		}

		//
		// We're in the admin.  All is well.	
		//
		if ($this->in_admin()) {
			return(true);
		}

		//
		// We agreed.  Return true.
		//
		if (!empty($conduct)) {
			return(true);

		} else {
			//
			// We had a standards of conduct and didn't agree.  That's bad.
			//
			$message = t("You must agree to the Standards of Conduct");
			form_set_error("conduct", $message);
			$this->log->log($message, "", WATCHDOG_WARNING);
			return(false);

		}

	} // End of checkStandardsOfConduct()


	/**
	* Check our email addresses to make sure they match.
	*
	* @param string $email Our email address.
	*
	* @param string $email2 The email address entered a second time
	*
	* @return boolean True if the email addresses match.  False otherwise.
	*/
	function checkEmailAddresses($email, $email2) {

		if ($email != $email2) {
			$error = t("Email addresses do not match!");
			form_set_error("email2", $error);
			$this->log->log($error, "", WATCHDOG_WARNING);

			$error = t("Email Address '!address' != '!address2'",
				array(
					"!address" => $email,
					"!address2" => $email2,
				)
				);
			$this->log->log($error, "", WATCHDOG_WARNING);
			return(false);
		}

		//
		// Assume success.
		//
		return(true);

	} // End of checkEmailAddresses()


	/**
	* Check to make sure that our birthdate is not today.
	*
	* @param array $birth Our array of birth year, month, and day.
	*
	* @return boolean True if the birthdate is okay, false otherwise.
	*/
	function checkBirthDate(&$birth) {

		if ($birth["year"] == date("Y")
			&& $birth["month"] == date("n")
			&& $birth["day"] == date("j")
			) {
			$error = t("Date of birth is set to today. ")
				. t("Did you forget to enter it?")
				;
			form_set_error("birthdate][year", $error);
			$this->log->log($error, "", WATCHDOG_WARNING);
			return(false);
		}

		//
		// Assume success
		//
		return(true);

	} // End of checkBirthDate()


	/**
	* Check our donation amount for sanity.
	*
	* @param float $donation The amount the user has donated.
	*
	* @return boolean True if the donation amount is sane.  False otherwise.
	*/
	function checkDonation($donation) {

		if (!$this->reg->is_valid_float($donation)
			&& $donation != ""
			) {
			$error = t("Donation '%donation%' is not a number!",
				array("%donation%" => $donation)
				);
			form_set_error("donation", $error);
			$this->log->log($error, "", WATCHDOG_WARNING);
			return(false);

		} else if ($this->reg->is_negative_number($donation)) {
			$error = t("Donation '%donation%' cannot be a negative amount!",
				array("%donation%" => $donation)
				);
			form_set_error("donation", $error);
			$this->log->log($error, "", WATCHDOG_WARNING);
			return(false);

		} else if ($donation > $this->reg->get_constant("DONATION_MAX")) {
			$error = t("Donations over %max% may not be made online.  "
				. "If you wish to donate a larger amount, please "
				. "contact us directly.",
				array(
					"%max%" => "$" . $this->reg->get_constant("DONATION_MAX")
					)
				);
			form_set_error("donation", $error);
			$this->log->log($error, "", WATCHDOG_WARNING);
			return(false);
        }

		//
		// Assume true
		//
		return(true);

	} // End of checkDonation()


	/**
	* Check our current registration level for sanity.
	*
	* @param integer $reg_level_id Our selected registration level.
	*
	* @return boolean True if the level is okay, false otherwise.
	*/
	function checkLevel($reg_level_id) {

		if (empty($reg_level_id)) {
			$error = t("Membership type is required.");
			form_set_error("reg_level_id", $error);
			$this->log->log($error, "", WATCHDOG_WARNING);
			return(false);
		}

		$levels = $this->reg->get_valid_levels();
		if (empty($levels[$reg_level_id])) {
			$error = t("Registration level ID '%level%' is invalid.",
				array("%level%" => $reg_level_id)
				);
			form_set_error("reg_level_id", $error);
			$this->log->log($error, "", WATCHDOG_ERROR);
			return(false);
		}

		//
		// Assume true
		//
		return(true);

	} // End of checkLevel()

} // End of Reg_FormCore class


