<?php

/**
* This class is used so that users can verify their current registrations.
*/
class reg_verify extends reg {

	/*
	* @var Our log object.
	*/
	protected $log;

	function __construct($message, $log, $email) {
		$this->message = $message;
		$this->log = $log;
		$this->email = $email;
	}


	/**
	* Our registration verification page.
	* Print up the search form, followed up any results.
	*
	* @param integer $id_email
	*/
	function verify($id_email) {

		$email = variable_get(reg::VAR_EMAIL, "");

		$factory = new reg_factory();
		$message = $factory->get_object("message");
		$message = t($message->load_display("verify",
			array(
				"!email" => $email,
				)
			));
		$retval .= nl2br($message["value"]);
		$retval .= drupal_get_form("reg_verify_form");
		$retval .= $this->results($id_email);

		return($retval);

	} // End of search()


	/**
	* Our main user-facing form to verify registrations.
	* 
	* This function only returns a form data structure, so we cannot 
	*	perform the search here.
	*/
	function verify_form() {

		$search_data = array();
		if (!empty($_SESSION["reg"]["verify"])) {
			$search_data = $_SESSION["reg"]["verify"];
		}
		
		$retval = array();

		$search = array(
			"#title" => t("Registration Info"),
			"#type" => "fieldset",
			"#tree" => true,
			//"#collapsible" => true,
			"#collapsed" => false,
			"#theme" => "reg_theme",
			);

		$search["last"] = array(
			"#title" => t("Last Name"),
			"#type" => "textfield",
			"#required" => true,
			"#description" => t("Enter your last name"),
			"#size" => reg_form::FORM_TEXT_SIZE_SMALL,
			"#default_value" => $search_data["last"],
			);

		$search["cc_num"] = array(
			"#title" => t("Last 4 digits of credit card"),
			"#type" => "textfield",
			"#required" => true,
			"#description" => t("Enter the last 4 digits of the credit card "
				. "used to pay for your registration."),
			"#size" => reg_form::FORM_TEXT_SIZE_SMALL,
			"#default_value" => $search_data["cc_num"],
			);

		$search["cc_exp"] = array(
			"#title" => t("Credit Card Expiration"),
			//
			// This is set so that when the child elements are processed,
			// they know they have a parent, and hence get stored
			// properly in the resulting array.
			//
			"#type" => "cc_exp",
			"#tree" => "true",
			"#required" => true,
			"#description" => t("The expiration date of the credit card used to "
				. "pay for your registration."),
			);
		$search["cc_exp"]["month"] = array(
			"#options" => reg_data::get_cc_exp_months(),
			"#type" => "select",
			"#default_value" => $search_data["cc_exp"]["month"],
			);

		$search["cc_exp"]["year"] = array(
			"#options" => reg_data::get_cc_exp_years(),
			"#type" => "select",
			"#default_value" => $search_data["cc_exp"]["year"],
			);

		$search["submit"] = array(
			"#type" => "submit",
			"#value" => t("Check registrations")
			);

		$retval["search"] = $search;

		return($retval);

	} // End of verify_form()


	function verify_validate($form_id, &$data) {

		//
		// Wipe our session array on every form submission.
		// In the future, maybe I could do this conditionally in a "reset" 
		// button.
		//
		unset($_SESSION["reg"]["verify"]);

		//
		// Check to make sure that our credit card number is valid.
		//
		if (!$this->is_valid_number($data["search"]["cc_num"])) {
			$error = t("Invalid credit card number '%num%'",
				array("%num%" => $data["search"]["cc_num"])
				);
			form_set_error("search][cc_num", $error);
		}

	} // End of verify_validate()


	/**
	* Store the current search criteria in session data and then redirect
	* the user back to the main search form, at which point the search 
	* will be performed.
	*/
	function verify_submit($form_id, &$data) {

		//
		// The very first thing we're going to do here is chop off all but
		// the last 4 numbers of the credit card, just in case the user 
		// entered the entire number.  I don't want credit card data 
		// anyhwere NEAR the session data.
		//
		$data["search"]["cc_num"] = reg_data::get_cc_last_4(
			$data["search"]["cc_num"]);

		$_SESSION["reg"]["verify"] = $data["search"];

		$url = "reg/verify";
		$this->goto_url($url);

	} // End of verify_submit()


	/**
	* If we have search criteria, perform a search and return the results.
	*
	* If no results are found, an error is thrown.
	*/
	function results($id_email) {

		$retval = "";

		//
		// Stop here if we have no search criteria.
		//
		if (empty($_SESSION["reg"]["verify"])) {
			return ($retval);
		}

		$search = $_SESSION["reg"]["verify"];

		$cursor = $this->get_cursor($search);

		$header = array(
			array("data" => t("Badge Number")),
			array("data" => t("Badge Name")),
			array("data" => t("Membership Type")),
			array("data" => t("Status")),
			array("data" => t("")),
			);

		$rows = array();
		$send_email_id = "";
		while ($row = db_fetch_array($cursor)) {

			$reg_id = $row["reg_id"];
			$url = "reg/verify/resend/$reg_id";

			//
			// If this matches our registration ID that the receipt is to
			// be resent to, do so.
			//
			if ($reg_id == $id_email) {
				$send_email_id = $reg_id;
			}

			$resend = l("Resend Receipt Email", $url);

			$rows[] = array(
				array("data" => $row["year"] . "-" . 
					reg_data::format_badge_num($row["badge_num"]),
					//"align" => "right"
					),
				array("data" => $row["badge_name"]),
				array("data" => $row["member_type"]),
				array("data" => $row["status"]),
				array("data" => $resend),
				);
		}

		//
		// If we have an ID to send email to, do so.
		//
		if (!empty($send_email_id)) {
			$this->send_email($send_email_id);	

		} else if (!empty($id_email)) {
			//
			// We couldn't find a registration for the requested ID.
			//
			$message = t("Unable to find valid registration information "
				. "for registration ID %id with the current credit card "
				. "information.",
				array(
					"%id" => $id_email,
				));
			form_set_error("", $message);
			$factory = new reg_factory();
			$log = $factory->get_object("log");
			$log->log($message, "", WATCHDOG_WARNING);

		}

		if (empty($rows)) {
			$this->handle_error($search);
		} else {
			$retval .= t("<h2>Memberships Found:</h2>");
			$retval .= theme("table", $header, $rows);
		}


		return($retval);

	} // End of results()


	/**
	* Send the reciept email to a specific registration ID.
	*/
	function send_email($id) {

		$data = reg_admin_member::load_reg($id);
		$url = reg_data::get_verify_url();

		$message_name = "email-receipt-no-cc";
		$email_data = array(
			"!name" => $data["first"] . " " . $data["middle"] . " "
				. $data["last"],
			"!badge_num" => $data["badge_num"],
			"!cc_name" => $data["cc_name"],
			"!total_cost" => $data["total_cost"],
			"!verify_url" => l($url, $url),
			);

		$email_sent = $this->email->email($data["email"], $message_name, 
			$data["id"], $email_data);

		$message = t("Your receipt has been re-sent to %email.",
			array(
				"%email" => $data["email"],
			));
		drupal_set_message($message);
		$factory = new reg_factory();
		$log = $factory->get_object("log");
		$log->log($message, $id);

	} // End of send_email()


	/**
	* Perform the actual query and return the cursor.
	*/
	function get_cursor($search) {

		$retval = "";

		//
		// If our month value is only 1 character long, prepend a 0 so 
		// that the SQL doesn't break.
		//
		if (strlen($search["cc_exp"]["month"]) == 1) {
			$search["cc_exp"]["month"] = "0" 
				. $search["cc_exp"]["month"];
		}

		$query = "SELECT "
			. "reg.*, "
			. "reg_type.member_type, "
			. "reg_status.status, "
			. "reg_trans.* "
			. "FROM "
			. "{reg} "
			. "LEFT JOIN {reg_type} ON reg.reg_type_id = reg_type.id "
			. "LEFT JOIN {reg_status} ON reg.reg_status_id = reg_status.id "
			. "LEFT JOIN {reg_trans} ON reg_trans.reg_id = reg.id "
			. "WHERE "
			. "reg.last='%s' "
			. "AND reg_trans.cc_num LIKE '%%%s' "
			//
			// Having to write queries like this are where I'm starting to
			// wonder if storing the expiration date as a time_t was such
			// a hot idea.
			//
			// At some point in the future, I should go through all the code
			// and analyze all the queries.
			//
			. "AND YEAR(FROM_UNIXTIME(reg_trans.card_expire)) ='%s' "
			. "AND MONTH(FROM_UNIXTIME(reg_trans.card_expire)) ='%s' "
			;

		$cc_exp_time = reg_data::get_time_t($search["cc_exp"]["year"], 
			$search["cc_exp"]["month"], 1);

		$args = array($search["last"], $search["cc_num"], 
			$search["cc_exp"]["year"], $search["cc_exp"]["month"])
			;

		$retval = db_query($query, $args);

		return($retval);

	} // End of get_cursor()


	/**
	* Handle any error condition where no members are found.
	*
	* @param array $header The array of the header, so we can 
	*	properly format our message.
	*
	* @param array $search Our search criteria.
	* 
	*/
	function handle_error(&$search) {

		$email = variable_get(reg::VAR_EMAIL, "");
		$message = t("No members found.<br>\n");
		if (!empty($email)) {
			$message .= t("If you think you are receiving this message in error, "
				. "please email %email% for further assistance.",
				array("%email%" => $email)
				);
		}

		form_set_error("", $message);

		//
		// Append debug info to the message and log it.
		//
		$message .= t("<br>(Last name: %last%, last 4 digits: %cc_num%, "
			. "expiration: %cc_exp%)",
			array(
				"%last%" => $search["last"],
				"%cc_num%" => $search["cc_num"],
				"%cc_exp%" => $search["cc_exp"]["year"] . "-" 
					. $search["cc_exp"]["month"],
			));
		$factory = new reg_factory();
		$log = $factory->get_object("log");
		$log->log($message, "", WATCHDOG_WARNING);

		return($rows);

	} // End of handle_error()


} // End of reg_verify class

