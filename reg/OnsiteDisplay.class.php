<?php
/**
* This class is used for displaying the form for onsite registrations, 
*	and processing them.
*/
class Reg_OnsiteDisplay {


	function __construct(&$reg, &$form_core, &$cc_gateway, &$log, &$message,
		&$captcha, &$level) {
		$this->reg = $reg;
		$this->form_core = $form_core;
		$this->cc_gateway = $cc_gateway;
		$this->log = $log;
		$this->message = $message;
		$this->captcha = $captcha;
		$this->level = $level;
	}


	/**
	* Our main page for the registration form.
	*
	* @return string HTML code of the page contents.
	*/
	function getPage() {

		$retval = "";

        $retval .= drupal_get_form("reg_onsitereg_form");

		return($retval);

	} // End of getPage()


	/**
	* Return our onsite registraiton form.
	*/
	function getForm() {

		$retval = array();

		$retval = $this->form_core->getForm("", $this->cc_gateway);

		//
		// Remove various fields that should not be in the onsite 
		// registration form.
		//
		unset($retval["billing"]["shipping_checkbox"]);
		unset($retval["billing"]["no_receipt"]);
		unset($retval["shipping"]);
		unset($retval["cc"]["cc_type_id"]);
		unset($retval["cc"]["cc_num"]);
		unset($retval["cc"]["cvv"]);
		unset($retval["cc"]["cc_exp"]);

		//
		// Adjust a few other fields since we're not requiring credit card
		// payments through the form.
		//
		$retval["billing"]["address1"]["#description"] = 
			t("Your current billing address.");
		$retval["cc"]["total"]["#description"] = 
			t("The total you will need to pay for your membership.");

		return($retval);

	} // End of getForm()


	/**
	* This function validates our form submission, making sure that all is 
	* well with email addresses and such.
	*/
	function getFormValidate(&$data) {

		$this->form_core->checkCaptcha($data["reg_captcha"]);

		//
		// Did the user agree to the Standards of Conduct?
		//
		// This has to be checked for, because it seems that the latest 
		// version of Drupal doesn't honor #required checkbox fields in 
		// forms. *sigh*
		//
		if (!$this->form_core->in_admin()) {
			if (!$this->form_core->checkStandardsOfConduct(
				$data["conduct"])) {
				$okay = false;
			}
		}

		//
		// Make sure our email addresses match.
		//
		$this->form_core->checkEmailAddresses($data["email"], 
			$data["email2"]);

		//  
		// Sanity check on our birth date.
		//  
		$this->form_core->checkBirthDate($data["birthdate"]);

		//
		// Make sure our registration level is valid
		//
		$this->form_core->checkLevel($data["reg_level_id"]);

	} // End of getFormValidate()


	/**
	* Everything's good.  Save the new membership and redirect to the success page.
	*/
	function getFormSubmit(&$data) {

		$this->addMember($data);

		$this->captcha->clear();

		$uri = "onsitereg/success";
		$this->reg->goto_url($uri);

	} // End of getFormValidate()


	/**
	* Add a member that we just created.
	*
	* This is a scaled down version of Reg_Member->add_membr(), as there
	* are no finances involved.
	*/
	function addMember(&$data) {

		//
		// If there's a level ID, grab the year from that.
		// Otherwise, an admin added it, get the year from the data.
		//
		if (!empty($data["reg_level_id"])) {
			$level_id = $data["reg_level_id"];
			$level_data = $this->level->load($data["reg_level_id"]);
			$year = $level_data["year"];
		}

		$levels = $this->reg->get_valid_levels();
		$reg_level_id = $data["reg_level_id"];
		$badge_cost_due = $levels[$reg_level_id]["price"];

		$query = "INSERT INTO {reg} "
			. "(created, modified, year, reg_type_id, reg_status_id, "
			. "badge_name, first, middle, last, "
			. "birthdate, "
			. "billing_name, "
			. "address1, address2, city, state, zip, country, "
			. "email, "
			. "phone, shirt_size_id, "
			. "badge_cost_due, donation_due "
			. ") "
			. "VALUES "
			. "( "
			. "UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), '%s', '%s', '%s', "
			. "'%s', '%s', '%s', '%s', "
			. "'%s', "
			. "'%s', "
			. "'%s', '%s', '%s', '%s', '%s', '%s', "
			. "'%s', "
			. "'%s', '%s', "
			. "'%s', '%s' "
			. ") "
			;
        
		$birth = $data["birthdate"];
        
		$date_string = $this->reg->get_date($birth["year"], $birth["month"],
			$birth["day"]);

		$data["reg_type_id"] = $this->reg->get_reg_type_id(
			$data["reg_level_id"]);

		$query_args = array($year,
			$data["reg_type_id"], 
			//
			// Badge status of "new"
			//
			4,
			$data["badge_name"], $data["first"], $data["middle"],
			$data["last"], $date_string,
			$data["billing_name"],
			$data["address1"], $data["address2"], $data["city"],
			$data["state"], $data["zip"], $data["country"],
			$data["email"], $data["phone"],
			$data["shirt_size_id"],
			$badge_cost_due, $data["donation"]
			);
        db_query($query, $query_args);

		$message = t("Received onsite registration for '%name'",
			array(
			"%name" => $data["first"] . " " . $data["middle"] 
				. " " . $data["last"]
			));

		$data["id"] = $this->reg->get_insert_id();
		$this->log->log($message, $data["id"]);

	} // End of addMember()


	/**
	* This function creates out page indicating a successful 
	*	onsite registration.
	*/
	function getSuccessPage() {

		$retval = "";

		$data = array();
		$data["!link"] = $this->reg->get_base() . "onsitereg";
		$message = $this->message->load_display("onsite-thankyou", $data);
		$retval = $message["value"];

		return($retval);

	} // End of getSuccessPage()


} // End of Reg_OnsiteDisplay class

