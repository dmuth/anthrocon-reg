<?php

/**
* This function creates our menu structure for authorize.net stuff.
*/
class reg_authorize_net_menu {

	function __construct(&$reg) {
		$this->reg = $reg;
	}

	public function get_menu() {

		$retval = array();

		$retval["admin/reg/settings/gateways/authorize_net"] = array(
			"title" => "Authorize.net",
			"page callback" => "reg_authorize_net_settings_page",
			"access arguments" => array($this->reg->get_constant("perm_admin")),
			"type" => MENU_LOCAL_TASK,
			"weight" => 3,
			);

		return($retval);

	} // End of get_menu()


} // End of reg_authorize_net_menu class

