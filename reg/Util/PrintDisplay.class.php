<?php
/**
* This class class handles displaying print jobs.
*/
class Reg_Util_PrintDisplay {


	function __construct(&$reg, &$util, &$log) {
		$this->reg = $reg;
		$this->util = $util;
		$this->log = $log;
	}



	/**
	* This function displays our main page.
	*/
	function getPage() {

		$retval = t("<h2>Pending Print Jobs</h2>");


		$form_output .= drupal_get_form("reg_admin_utils_print_queue_form");

		$retval .= theme_pager();
		$retval .= $form_output;
		$retval .= theme_pager();

		return($retval);

	} // End of getPage()


	/**
	* Create our form data structure for all pending jobs.
	*
	* @return array Data structure for the form.
	*/
	function getForm() {

		$retval = array();

		$header = $this->getHeader();

		$order_by = tablesort_sql($header);

		$rows = $this->util->getAllJobs("new", "", $order_by, true);

		//
		// Loop through our results, creating a form element for each.
		// We'll also be storing options in a separate form element, 
		// which will be accessed in the themeing function.
		//
		$retval["rows"] = array();
		$options = array();
		$reg_ids = array();
		foreach ($rows as $key => $value) {

			$row = array();
			$id = $value["id"];
			$reg_id = $value["reg_id"];

			$options[$id] = "";

			$row["id"] = array(
				"#value" => $id,
				);
			$row["reg_id"][$id] = array(
				"#type" => "hidden",
				"#value" => $reg_id,
				);
			$row["status"] = array(
				"#value" => $value["status"],
				);
			$row["printer"] = array(
				"#value" => $value["printer"],
				);
			$row["badge_name"] = array(
				"#value" => $value["badge_name"],
				);
			$row["badge_num"] = array(
				"#value" => $value["year"] . "-" 
					. $this->reg->format_badge_num($value["badge_num"]),
				);
			$row["member_type"] = array (
				"#value" => $value["member_type"],
				);

			$retval["rows"][$id] = $row;

		}

		//
		// Don't worry, these won't go down here, our form rendering
		// function will display them "inline" with the rows.
		//
		$retval["cancel"] = array(
			"#type" => "checkboxes",
			"#options" => $options,
			);

		//
		// Only show this button if we have no outstanding print jobs.
		//
		if (!empty($rows)) {
			$retval["submit"] = array(
				"#type" => "submit",
				"#value" => t("Cancel Selected Jobs"),
				);
		}

		return($retval);

	} // End of getForm()


	/**
	* Our theme function for the form.  This will let us create the 
	* form in the style we want, which happens to be a table with 
	* checkboxes to cancel print jobs.
	*
	* @param array $form The data structure of the form
	*
	* @return string HTML code of the form
	*/
	function getFormTheme(&$form) {

		$retval = "";

		//
		// Loop through our form elements and print each one up.
		// 
		$rows = array();
		foreach (element_children($form["rows"]) as $key) {

			$reg_id = $form["rows"][$key]["reg_id"][$key]["#value"];
			$url = "admin/reg/members/view/" . $reg_id . "/view";

			$row = array();

			//
			// Render just our checkbox.
			//
			$row[] = array("data" => 
				drupal_render($form["cancel"][$key]),
				);
			$row[] = array("data" => 
				drupal_render($form["rows"][$key]["id"]),
				);
			$row[] = array("data" => 
				drupal_render($form["rows"][$key]["status"]),
				);
			$row[] = array("data" => 
				drupal_render($form["rows"][$key]["printer"]),
				);

			$link = l(drupal_render($form["rows"][$key]["badge_name"]), $url);
			$row[] = array("data" => $link);

			$link = l(drupal_render($form["rows"][$key]["badge_num"]), $url);
			$row[] = array("data" => $link);

			$row[] = array("data" => 
				drupal_render($form["rows"][$key]["member_type"]),
				);

			$rows[] = $row;

		}

		if (empty($rows)) {
			$rows = array();
			$row[] = array(
				"data" => t("No pending print jobs found."),
				"colspan" => 7,
				);
			$rows[] = $row;
		}

		$retval .= theme("table", $this->getHeader(), $rows);

		//
		// Render anything else left in the form
		//
		$retval .= drupal_render($form);

		return($retval);

	} // End of getFormTheme()


	/**
	* Nothing to validate at the present time.
	*/
	function getFormValidate($form_id, &$data) {
	}


	/**
	* Cancel anything that was marked marked.
	*/
	function getFormSubmit($form_id, &$data) {

		foreach ($data["cancel"] as $key => $value) {

			if (empty($value)) {
				continue;
			}

			$id = $value;
			$this->util->updateJob($id, "cancelled");

			$message = t("Print job ID !id cancelled.",
				array("!id" => $id)
				);

			$reg_id = $data[$id];
			$this->log->log($message, $reg_id);

			drupal_set_message($message);

		}

		//
		// Send us back to the page we came from
		//
		$uri = "admin/reg/utils/print";
		$this->reg->goto_url($uri);

	} // End of getFormSubmit()


	/**
	* Return our array of headers.
	*/
	function getHeader() {

		$retval = array();

		$retval[] = array("data" => t("Cancel?"));
		$retval[] = array("data" => t("Job ID"),
			"field" => "id", 
			"sort" => "asc",
			);
		$retval[] = array("data" => t("Status")
			);
		$retval[] = array("data" => t("Printer"),
			"field" => "printer",
			);
		$retval[] = array("data" => t("Badge Name"),
			"field" => "badge_name",
			);
		$retval[] = array("data" => t("Badge Number"),
			"field" => "badge_num",
			);
		$retval[] = array("data" => t("Member Type")
			);

		return($retval);

	} // End of getHeader()


} // End of Reg_Util_PrintDisplay class

