<?php

/**
* This function creates our menu structure for authorize.net stuff.
*/
class authorize_net_menu {

	function __construct() {
	}

	public function get_menu($may_cache) {

		$retval = array();

		if ($may_cache) {
			$retval[] = array(
				"path" => "admin/reg/settings/gateways/authorize_net",
				"title" => t("Authorize.net"),
				"callback" => "authorize_net_settings",
				"type" => MENU_LOCAL_TASK,
				"weight" => 3,
				);

		}

		return($retval);

	} // End of get_menu()


} // End of authorize_net_menu class

