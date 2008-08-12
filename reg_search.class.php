<?php

/**
* This class holds functions related to our search functionality.
*/
class reg_search {


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

		$types = reg::get_types();
		$types[""] = "Select";
		ksort($types);
		$search["reg_type_id"] = array(
			"#title" => "Badge Type",
			"#type" => "select",
			"#options" => $types,
			"#description" => "The registration type.",
			"#default_value" => $search_data["reg_type_id"],
			);

		$statuses = reg::get_statuses();
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
	* 
	*/
	static function search_results() {

		$retval = "";

		$search = self::search_get_args();
                
		if (empty($search)) {
			return(null);
		}

		$retval = "Search results and SQL code goes here.";
		/**
		TODO:
		- Search criteria based on each search element.
		- Create table
		- Make links to the edit page: admin/reg/members/view/reg_id
		*/

		return($retval);

	} // End of search_results()


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
		drupal_goto($url);

	} // End of search_submit()


} // End of class reg_search

