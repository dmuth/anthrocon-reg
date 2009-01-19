<?php

/**
* This class holds functions related to our search functionality.
*/
class reg_admin_search extends reg {


	function __construct(&$message, &$fake, &$log, &$admin_member) {
		$this->log = $log;
		$this->admin_member = $admin_member;
		parent::__construct($message, $fake, $log);
	}


	/**
	* Our search page.
	*/
	function search() {

		$retval .= "<h2>Search Registrations</h2>";
		$retval .= drupal_get_form("reg_admin_search_form");

		return($retval);

	} // End of search()


	function search_form() {

		$arg = $this->get_args_string();
		$search_data = $this->get_data_to_array($arg);

		$retval = array();

		$search = array(
			"#title" => "Search",
			"#type" => "fieldset",
			"#tree" => true,
			"#collapsible" => true,
			"#collapsed" => false,
			"#theme" => "reg_theme",
			);

		//
		// Collapse the form if a search was done.
		//
		//if (!empty($search_data)) {
		//	$search["#collapsed"] = true;
		//}

		$search["badge_num"] = array(
			"#title" => "Badge Number",
			"#type" => "textfield",
			"#description" => t("Just the core badge number.  ")
				. t("Do NOT include the year."),
			"#size" => $this->get_constant("FORM_TEXT_SIZE_SMALL"),
			"#default_value" => $search_data["badge_num"],
			);

		$search["name"] = array(
			"#title" => "Name",
			"#type" => "textfield",
			"#description" => "Badge name or real name.",
			"#size" => $this->get_constant("FORM_TEXT_SIZE_SMALL"),
			"#default_value" => $search_data["name"],
			);

		$search["address"] = array(
			"#title" => "Address",
			"#type" => "textfield",
			"#description" => "Address, city, state, or country.",
			"#size" => $this->get_constant("FORM_TEXT_SIZE_SMALL"),
			"#default_value" => $search_data["address"],
			);

		$search["email"] = array(
			"#title" => "Email",
			"#type" => "textfield",
			"#description" => "Email address.",
			"#size" => $this->get_constant("FORM_TEXT_SIZE_SMALL"),
			"#default_value" => $search_data["email"],
			);

		$types = $this->get_types();
		$types[""] = "Select";
		ksort($types);
		$search["reg_type_id"] = array(
			"#title" => "Badge Type",
			"#type" => "select",
			"#options" => $types,
			"#description" => "The registration type.",
			"#default_value" => $search_data["reg_type_id"],
			);

		$statuses = $this->get_statuses();
		$statuses[""] = "Select";
		ksort($statuses);
		$search["reg_status_id"] = array(
			"#title" => "Status",
			"#type" => "select",
			"#options" => $statuses,
			"#description" => "The member's status.",
			"#default_value" => $search_data["reg_status_id"],
			);

		//
		// If we are doing a search, make a link to download the arguments.
		//
		if ($this->get_args_string()) {
			$url = "admin/reg/members/search/download/" 
				. $this->get_args_string();
			$link = l("Download!", $url);
			$search["test"] = array(
				"#type" => "item",
				"#title" => t("Download these results?"),
				"#value" => $link,
				);
		}

		$search["submit"] = array(
			"#type" => "submit",
			"#value" => t("Search")
			);

		$retval["search"] = $search;

		return($retval);

	} // End of search_form()


	/**
	* This function gets the string of arguments that were passed in
	*	to the URL.
	*
	* @return string The argument of search arguments.
	*/
	protected function get_args_string($offset = 0) {

		$retval = arg(4);

		//
		// If we are doing a download, the args are pushed over by one
		// in the URL.
		//
		if ($retval == "download") {
			$retval = arg(5);
		}

		return($retval);

	} // End of get_args_string()


	/**
	* Make sure we have valid search criteria.
	*/
	function search_validate($form_id, &$data) {
	} // End of search_validate()


	/**
	* Handle submissions of our search form.
	* Basically, we're going to encode some data into a URL and 
	* redirect ourselves to that URL.  That's because Drupal only allows
	* submit functions to redirect and not display data. :-(
	*/
	function search_submit($form_id, &$data) {

		$get_data = $this->array_to_get_data($data["search"]);

		$url = "admin/reg/members/search/" . $get_data;
		//print $url; // Debugging

		$this->goto_url($url);

	} // End of search_submit()


	/**
	* Run our search and return search results.
	*/
	function results() {

		$retval = "";

		$arg = $this->get_args_string();
		$search = $this->get_data_to_array($arg);

		if (!empty($search["submit"])) {
			$this->log_search($search);
		}

		if (empty($search)) {
			return(null);
		}

		$header = $this->admin_member->get_member_table_header();

		$order_by = tablesort_sql($header);

		$cursor = $this->get_cursor($search, $order_by);

		while ($row = db_fetch_array($cursor)) {
			$rows[] = $this->admin_member->get_member_table_row($row);
		}

		if (empty($rows)) {
			$message = t("No members found.");
			$rows[] = array(
				array(
					"data" => $message,
					"colspan" => count($header),
					)
				);
		}

		$retval .= theme_pager();
		$retval .= theme("table", $header, $rows);
		$retval .= theme_pager();

		return($retval);

	} // End of results()


	/**
	* Return a SQL statement for searching the database.
	*
	* @param array $search Array of search criteria.
	*
	* @param string $order_by How are we ordering the results?
	*
	* @param $page boolean Set to true if we want to page the query,
	*	false if we want everything.
	*/
	function get_cursor(&$search, $order_by, $page = true) {

		$where = array();
		$args = array();

		if (isset($search["badge_num"])
			&& $search["badge_num"] != ""
			) {
			$where[] = "badge_num='%s'";
			$args[] = $search["badge_num"];
		}

		if (!empty($search["name"])) {
			$where[] = "("
				. "badge_name LIKE '%%%s%%' "
				. "OR first LIKE '%%%s%%' "
				. "OR middle LIKE '%%%s%%' "
				. "OR last LIKE '%%%s%%' "
				. ")"
				;
			$args[] = $search["name"];
			$args[] = $search["name"];
			$args[] = $search["name"];
			$args[] = $search["name"];
		}

		if (!empty($search["email"])) {
			$where[] = "("
				. "email LIKE '%%%s%%' "
				.")";
			$args[] = $search["email"];
		}

		if (!empty($search["address"])) {
			$where[] = "("
				. "address1 LIKE '%%%s%%' "
				. "OR address2 LIKE '%%%s%%' "
				. "OR city LIKE '%%%s%%' "
				. "OR state LIKE '%%%s%%' "
				. "OR zip LIKE '%%%s%%' "
				. "OR country LIKE '%%%s%%' "
				. ")";

			$args[] = $search["address"];
			$args[] = $search["address"];
			$args[] = $search["address"];
			$args[] = $search["address"];
			$args[] = $search["address"];
			$args[] = $search["address"];
		}

		if (!empty($search["reg_type_id"])) {
			$where[] = "reg_type_id='%s'";
			$args[] = $search["reg_type_id"];
		}

		if (!empty($search["reg_status_id"])) {
			$where[] = "reg_status_id='%s'";
			$args[] = $search["reg_status_id"];
		}

		$where_string = "";
		if (!empty($where)) {
			$where_string = join(" AND ", $where);
			$where_string = "WHERE $where_string";
		}

		$query = "SELECT "
			. "reg.*, "
			. "reg_type.member_type, "
			. "reg_status.status, "
			. "reg_shirt_size.shirt_size "
			. "FROM "
			. "{reg} "
			. "LEFT JOIN {reg_type} ON reg.reg_type_id = reg_type.id "
			. "LEFT JOIN {reg_status} ON reg.reg_status_id = reg_status.id "
			. "LEFT JOIN {reg_shirt_size} ON reg.shirt_size_id = reg_shirt_size.id "
			. $where_string . " "
			. $order_by
			;

		if ($page == true) {
			$retval = pager_query($query, $this->get_constant("ITEMS_PER_PAGE"), 
				0, null, $args);
		} else {
			$retval = db_query($query, $args);

		}

		return($retval);

	} // End of get_cursor()


	/**
	* Log any searches that are done.
	*/
	function log_search($search) {

		unset($search["submit"]);

		//
		// Turn our search criteria into text.
		//
		$search_text = "";
		foreach ($search as $key => $value) {
			if (!empty($value)) {
				if (!empty($search_text)) {
					$search_text .= ", ";
				}
				$search_text .= "$key: $value";
			}
		}

		$message = t("Audit log: Searched members. ");
		if (!empty($search_text)) {
			$message .= t("Criteria: ") . $search_text . ". ";
		}

		//
		// Note the page number as well.
		//
		$page = $_GET["page"];
		if (empty($page)) {
			$page = 0;
		}
		$message .= "Page: $page.";

		$this->log->log($message);

	} // End of log_search()

} // End of class reg_search

