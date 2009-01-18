<?php

/**
* This class extends the reg_admin_log class and adds in some 
*	search functionality.
*/
class reg_admin_log_search extends reg_admin_log_view {


	function __construct($message, $fake, $log) {
        parent::__construct($message, $fake, $log);
	}


	/**
	* Our log viewer.  Lists the most recent log entries.
	*
	* @param integer $id Optional registration ID to limit results
	*	to a single membership.
	*
	* @return string HTML code of the log entry.
	*/
	function log_recent($id = "") {

		$retval = "";

		$retval .= drupal_get_form("reg_admin_log_search_form");

		$search_data = $this->get_data_to_array(arg(3));

		if (empty($search_data["submit"])) {
			$search_data = array();
		} else {
			unset($search_data["submit"]);
		}

		$retval .= parent::log_recent($id, $search_data);

		return($retval);

	} // End of log_recent()


	/**
	* Create our search form for searching logs.
	*/
	function search_form() {

		$rertval = array();

		$search_data = $this->get_data_to_array(arg(3));

		$search = array(
			"#title" => t("Search Log Entries"),
			"#type" => "fieldset",
			"#tree" => true,
			"#collapsible" => true,
			"#collapsed" => false,
			//"#theme" => "reg_theme",
			);

		$search["text"] = array(
			"#title" => t("Search String"),
			"#type" => "textfield",
			"#description" => t("Text to search for."),
			"#size" => $this->get_constant("FORM_TEXT_SIZE_SMALL"),
			"#default_value" => $search_data["text"],
			);

		$users = $this->get_log_users();

		$search["uid"] = array(
			"#title" => t("User"),
			"#type" => "select",
			"#options" => $users,
			"#description" => "The user who made the log entry",
			"#default_value" => $search_data["uid"],
			);

		$search["submit"] = array(
			"#type" => "submit",
			"#value" => t("Search Logs")
			);

		$retval["search"] = $search;

		return($retval);

	} // End of search_form()


	function search_form_validate($form_id, &$data) {
	} // End of search_form_validate()


	function search_form_submit($form_id, &$data) {

		$get_data = $this->array_to_get_data($data["search"]);
		$url = "admin/reg/logs/" . $get_data;

		$this->goto_url($url);

	} // End of search_form_submit()


	/**
	* Return a list of all unique users from the reg log.
	*
	* @return array The key is the user ID and the value is the user name.
	*/
	function get_log_users() {

		$retval = array();
		$retval[""] = t("All Users");

		$query = "SELECT "
			. "users.uid, users.name, count(*) as num "
			." FROM reg_log "
			. "JOIN users ON reg_log.uid = users.uid "
			. "GROUP BY users.uid "
			. "ORDER BY name"
			;
		$cursor = db_query($query);
		while ($row = db_fetch_array($cursor)) {
			$name = $row["name"];
			if (empty($name)) {
				$name = t("(Anonymous)");
			}

			$name .= t(" - !num entries",
				array("!num" => $row["num"]));

			$id = $row["uid"];

			$retval[$id] = $name;

		}

		return($retval);

	} // End of get_log_users()


} // End of reg_admin_log_search


