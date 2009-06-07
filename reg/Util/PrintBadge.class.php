<?php
/**
* This class is used to print single badges on a one-off basis.
*/
class Reg_Util_PrintBadge {


	function __construct(&$reg, &$admin_member, &$util_print, 
		&$util_watchlist, &$log) {
		$this->reg = $reg;
		$this->admin_member = $admin_member;
		$this->util_print = $util_print;
		$this->util_watchlist = $util_watchlist;
		$this->log = $log;
	}


	/**
	* Our main function.
	*/
	function getPage($id) {

		$retval = "";

		$retval .= drupal_get_form("reg_admin_members_print_form", $id);

		return($retval);

	} // End of getPage()


	/**
	* Return a data structure for a form to print the current member's 
	*	conbadge.
	*/
	function getForm($id) {

		$retval = array();

		$data = $this->admin_member->load_reg($id);

		if (empty($data)) {
			$error = t("Member ID '%id' not found!", array("%id" => $id));
			form_set_error("", $error);
			return($retval);
		}

		$args = array(
			"first" => $data["first"],
			"last" => $data["last"],
			);
		$match = $this->util_watchlist->search($args);

		$retval["set"] = array();
		//$retval["set"]["#type"] = "fieldset";
		$retval["set"]["#title"] = t("Badge Info");
		//$retval["set"]["#theme"] = "reg_theme";

		$retval["id"] = array(
			"#type" => "hidden",
			"#value" => $id,
			);

		$retval["set"]["name"] = array(
			"#title" => t("Legal Name"),
			"#type" => "item",
			"#value" => $data["first"] . " " . $data["middle"] . " " . $data["last"],
			);

		$retval["set"]["badge_name"] = array(
			"#title" => t("Badge Name"),
			"#type" => "item",
			"#value" => $data["badge_name"],
			);

		$badge_number = $data["year"] . "-" 
			. $this->reg->format_badge_num($data["badge_num"]);
		$retval["set"]["badge_number"] = array(
			"#title" => t("Badge Number"),
			"#type" => "item",
			"#value" => $badge_number,
			);

		$retval["set"]["membership_type"] = array(
			"#title" => t("Membership Type"),
			"#type" => "item",
			"#value" => $data["member_type"],
			);

		if ($this->reg->isMinor($data["birthdate"])) {
			$is_minor = t("Yes");
		} else {
			$is_minor = t("No");
		}

		$retval["set"]["minor"] = array(
			"#title" => t("Minor Badge?"),
			"#description" => t("Is this member under the age of 18?"),
			"#type" => "item",
			"#value" => $is_minor,
			);

		$retval["set"]["notes"] = array(
			"#title" => "Notes",
			"#description" => t("Any additional notes you want to add. (optional)"),
			"#type" => "textarea",
			);

		$retval["set"]["submit"] = array(
			"#type" => "submit",
			"#value" => t("Print Badge"),
			);

		if ($match) {
			$retval["set"]["submit"]["#disabled"] = true;
			$retval["set"]["submit"]["#value"] =
				t("Disabled for members on watchlist");
		}

		return($retval);

	} // End of getForm()


	/**
	* Nothing to do in this step for now...
	*/
	function getFormValidate($form_id, &$data) {
	}


	/**
	* Called when submitting a form
	*/
	function getFormSubmit($form_id, &$data) {

		$data_member = $this->admin_member->load_reg($data["id"]);

		$printer = "";
		if ($this->reg->isMinor($data_member["birthdate"])) {
			$printer = "minor";
		}

		$id = $this->util_print->addJob($data["id"], $printer);

		$message = t("Badge sent to printer. (Print Job ID: !id)", 
			array("!id" => $id)
			);
		drupal_set_message($message);

		if (!empty($data["notes"])) {
			$message .= t(" Notes: ") . $data["notes"];
		}

		$this->log->log($message, $data["id"]);

		$uri = "admin/reg/members/view/" . $data["id"] . "/view";
		$this->reg->goto_url($uri);

	} // End of getFormSubmit()


} // End of Reg_Util_PrintBadge class

