<?php

/**
* This class holds functions related to our search functionality.
*/
class reg_admin_search {


	/**
	* Our search page.
	*/
	static function search() {

		$retval .= "<h2>Search Registrations</h2>";
		$retval .= drupal_get_form("reg_admin_search_form");

		return($retval);

	} // End of search()


	static function search_form() {

		$search_data = self::search_get_args();

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
		if (!empty($search_data)) {
			$search["#collapsed"] = true;
		}

		$search["badge_num"] = array(
			"#title" => "Badge Number",
			"#type" => "textfield",
			"#description" => t("Just the core badge number.  ")
				. t("Do NOT include the year."),
			"#size" => reg_form::FORM_TEXT_SIZE_SMALL,
			"#default_value" => $search_data["badge_num"],
			);

		$search["name"] = array(
			"#title" => "Name",
			"#type" => "textfield",
			"#description" => "Badge name or real name.",
			"#size" => reg_form::FORM_TEXT_SIZE_SMALL,
			"#default_value" => $search_data["name"],
			);

		$search["address"] = array(
			"#title" => "Address",
			"#type" => "textfield",
			"#description" => "Address, city, state, or country.",
			"#size" => reg_form::FORM_TEXT_SIZE_SMALL,
			"#default_value" => $search_data["address"],
			);

		$types = reg_data::get_types();
		$types[""] = "Select";
		ksort($types);
		$search["reg_type_id"] = array(
			"#title" => "Badge Type",
			"#type" => "select",
			"#options" => $types,
			"#description" => "The registration type.",
			"#default_value" => $search_data["reg_type_id"],
			);

		$statuses = reg_data::get_statuses();
		$statuses[""] = "Select";
		ksort($statuses);
		$search["reg_status_id"] = array(
			"#title" => "Status",
			"#type" => "select",
			"#options" => $statuses,
			"#description" => "The member's status.",
			"#default_value" => $search_data["reg_status_id"],
			);

		$search["submit"] = array(
			"#type" => "submit",
			"#value" => t("Search")
			);

		$retval["search"] = $search;

		return($retval);

	} // End of search_form()


	/**
	* If search arguments were passed in, decode them and return an
	*       array with the data.
	*/
	private static function search_get_args() {
		$arg = arg(4);
		$arg = rawurldecode($arg);
		$arg = html_entity_decode($arg);
		parse_str($arg, $retval);
		return($retval);
	}


	/**
	* Make sure we have valid search criteria.
	*/
	static function search_validate($form_id, &$data) {
	} // End of search_validate()


	/**
	* Handle submissions of our search form.
	* Basically, we're going to encode some data into a URL and 
	* redirect ourselves to that URL.  That's because Drupal only allows
	* submit functions to redirect and not display data. :-(
	*/
	static function search_submit($form_id, &$data) {

		$get_data = http_build_query($data["search"]);
		$get_data = rawurlencode($get_data);

		$url = "admin/reg/members/search/" . $get_data;
		reg::goto_url($url);

	} // End of search_submit()


	/**
	* Run our search and return search results.
	*/
	static function results() {

		$retval = "";

		$search = self::search_get_args();
                
		if (empty($search)) {
			return(null);
		}

		$header = reg_admin_member::get_member_table_header();

		$order_by = tablesort_sql($header);

		$cursor = self::get_cursor($search, $order_by);

		while ($row = db_fetch_array($cursor)) {
			$rows[] = reg_admin_member::get_member_table_row($row);
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

		$retval = theme("table", $header, $rows);

		$retval .= theme_pager();

		return($retval);

	} // End of search_results()


	/**
	* Return a SQL statement for searching the database.
	*
	* @param array $search Array of search criteria.
	*
	* @param string $order_by How are we ordering the results?
	*/
	static function get_cursor(&$search, $order_by) {

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
			. "reg_status.status "
			. "FROM "
			. "{reg} "
			. "LEFT JOIN {reg_type} ON reg.reg_type_id = reg_type.id "
			. "LEFT JOIN {reg_status} ON reg.reg_status_id = reg_status.id "
			. $where_string . " "
			. $order_by
			;

		$retval = pager_query($query, reg::ITEMS_PER_PAGE, 0, null, $args);

		return($retval);

	} // End of get_cursor()


} // End of class reg_search

