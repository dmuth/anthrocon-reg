<?php

/**
* This function creates our menu structure for authorize.net stuff.
*/
class authorize_net_menu {

	function __construct() {
	}

	public function get_menu() {

		$retval = array();

		$retval["admin/reg/settings/gateways/authorize_net"] = array(
			"title" => "Authorize.net",
			"page callback" => "authorize_net_settings_page",
			"type" => MENU_LOCAL_TASK,
			"weight" => 3,
			);

		return($retval);

	} // End of get_menu()


} // End of authorize_net_menu class

