<?php
/**
* Our class to display entries from the watchlist
*/
class Reg_Util_WatchlistDisplay {

	function __construct(&$reg, &$util, &$log) {
		$this->reg = $reg;
		$this->util = $util;
		$this->log = $log;
	}


	/**
	* Retrieve all of our records.
	*
	* @return text HTML of our watchlist.
	*/
	function getAll() {

		$retval = "";

		$header = $this->getHeader();
		$order_by = tablesort_sql($header);

		$data = $this->util->getAll($order_by);
		$rows = $this->getRows($data);

		$retval .= theme("table", $header, $rows);

		return($retval);

	} // End of getAll()


	/**
	* Create the editing page for a specific record.
	*
	* @param integer $id The ID of the record we are editing.  If empty,
	*	then we are creating a new record.
	*/
	function getEditPage($id) {

		$retval = "";

		if (!empty($id)) {
			$retval .= "<h2>" . t("Edit Watchlist Record") . "</h3>";
		}

		$retval .= drupal_get_form("reg_admin_utils_watchlist_form", $id);

		return($retval);

	} // End of getEditPage()


	/**
	* Return our form for editing a record or creating a new record.
	*
	* @return array Associative array of form data
	*/
	function getForm($id) {

		$retval = array();

		$data = array();
		if (!empty($id)) {
			$data = $this->util->load($id);
		}

		$retval["id"] = array(
			"#title" => "id",
			"#type" => "hidden",
			"#value" => $id,
			);

		$retval["first"] = array(
			"#type" => "textfield",
			"#title" => t("First Name"),
			"#description" => t("The person's legal first name"),
			"#default_value" => $data["first"],
			"#required" => true,
			);

		$retval["last"] = array(
			"#type" => "textfield",
			"#title" => t("Last Name"),
			"#description" => t("The person's legal last name"),
			"#default_value" => $data["last"],
			"#required" => true,
			);

		$retval["first_alias"] = array(
			"#type" => "textfield",
			"#title" => t("First Name Alias(es)"),
			"#description" => t("Any aliases for the first name.  "
				. "Regular expressions are allowed."),
			"#default_value" => $data["first_alias"],
			);

		$retval["action"] = array(
			"#type" => "textfield",
			"#title" => t("Action To Take"),
			"#default_value" => $data["action"],
			"#description" => t("Action that should be taken if this "
				. "person tries registering for a badge."),
			"#required" => true,
			);

		$retval["disabled"] = array(
			"#type" => "checkbox",
			"#title" => t("Disabled?"),
			"#description" => t("Is this entry disabled?  If disabled, "
				. "it will not be checked against when processing "
				. "memberships."),
			"#default_value" => $data["disabled"],
			);

		$retval["submit"] = array(
			"#type" => "submit",
			"#value" => t("Save")
			);

		return($retval);

	} // End of getForm()


	/**
	* Our validation function.
	*/
	function getFormValidate(&$data) {
	}


	function getFormSubmit(&$data) {

		//
		// Generate our log message, complete with audit log data.
		//
		if (empty($data["id"])) {
			$message = t("New watchlist entry created.  Name (!first !last)",
				array(
					"!first" => $data["first"],
					"!last" => $data["last"],
				));

			$id = $this->util->insert($data);
			drupal_set_message(t("Watchlist entry created."));

			$this->log->log($message);

		} else {
			$old_data = $this->util->load($data["id"]);
			$message = "Watchlist entry updated. ";
			$message .= $this->reg->get_changed_data($data, $old_data);

			$this->util->update($data["id"], $data);
			drupal_set_message(t("Watchlist entry updated."));

			$this->log->log($message);

		}

		$url = "admin/reg/settings/watchlist";
		$this->reg->goto_url($url);			

	} // End of getFormSubmit()


	/**
	* Get our table header.
	*/
	function getHeader() {

		$retval = array();
		$retval[] = array("data" => t("First Name"), "field" => "first");
		$retval[] = array("data" => t("Last Name"), "field" => "last"
			, "sort" => "asc");
		$retval[] = array("data" => t("Disabled?"), "field" => "disabled");
		$retval[] = array("data" => t("Action"), "field" => "action");

		return($retval);

	} // End of getHeader()


	/**
	* Get our table rows.
	*/
	function getRows(&$data) {

		$retval = array();

		foreach ($data as $key => $value) {

			$id = $value["id"];
			$url = "admin/reg/settings/watchlist/view/" . $id . "/edit";
			$first = l($value["first"], $url);
			$last = l($value["last"], $url);

			$disabled = "";
			if (!empty($value["disabled"])) {
				$disabled = t("YES");
			}

			$action = $value["action"];

			$row = array();
			$row[] = array("data" => $first);
			$row[] = array("data" => $last);
			$row[] = array("data" => $disabled);
			$row[] = array("data" => $action);

			$retval[] = $row;

		}

		return($retval);

	} // End getRows()


	/**
	* Wrapper for our search function.  This sets things to display if 
	*	there is a match.
	*
	* @param array $data Array of first and last name to search for.
	*
	* @return mixed Array of match data if there is a match, otherwise false.
	*/
	function search(&$data) {

		$match = $this->util->search($data);

		if ($match) {

			$error = t("Warning!  This member matches the watchlist entry for '%first %last'!",
				array(
					"%first" => $match["first"],
					"%last" => $match["last"],
				));
			drupal_set_message($error, "error");

			$error = t("The system will NOT let you continue with this member "
				. "until they are removed from the watchlist.");
			drupal_set_message($error, "error");

			$error = t("Recommended action: %action",
				array(
					"%action" => $match["action"]
					));
			drupal_set_message($error, "error");

        }

		return($match);

	} // End of search()


} // End of Reg_Util_WatchlistDisplay class

