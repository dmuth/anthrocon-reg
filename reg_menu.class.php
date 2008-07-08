<?php

/**
* This class is just for our menu function, since it is going to grow 
* quite big. :-)
*/
class reg_menu {


	/**
	* Generate our menu items and callbacks for this module.
	*
	* @return array Scalar array of menu data.
	*/
	static function menu() {

		$retval = array();

		//
		// Public link
		//
		$retval[] = array(
			"path" => "reg",
			"title" => t("Registration"),
			"callback" => "reg_registration",
			"access" => user_access(reg::PERM_REGISTER),
			"type" => MENU_NORMAL_ITEM,
			);

		//
		// Admin section
		//
		$retval[] = array(
			"path" => "admin/reg",
			"title" => t("Registration Admin"),
			"callback" => "reg_admin_stats",
			"access" => user_access(reg::PERM_ADMIN),
			"type" => MENU_NORMAL_ITEM,
			);

		$retval[] = array(
			"path" => "admin/reg/stats",
			"title" => t("Stats"),
			"callback" => "reg_admin_stats",
			"type" => MENU_DEFAULT_LOCAL_TASK,
			"weight" => -10,
			);

		$retval[] = array(
			"path" => "admin/reg/settings",
			"title" => t("Settings"),
			"callback" => "reg_admin_settings",
			"type" => MENU_LOCAL_TASK,
			"weight" => 4,
			);

		$retval[] = array(
			"path" => "admin/reg/levels",
			"title" => t("Membership Levels"),
			"callback" => "reg_admin_levels",
			"type" => MENU_LOCAL_TASK,
			"weight" => 3,
			);

		$retval[] = array(
			"path" => "admin/reg/levels/list",
			"title" => t("List"),
			"callback" => "reg_admin_levels",
			"type" => MENU_DEFAULT_LOCAL_TASK,
			"weight" => -10,
			);

		$retval[] = array(
			"path" => "admin/reg/levels/add",
			"title" => t("Add"),
			"callback" => "reg_admin_levels_edit",
			"type" => MENU_LOCAL_TASK,
			);

		//
		// Used for editing a membership level.
		//
		$retval[] = array(
			"path" => "admin/reg/levels/edit",
			"title" => t("Add"),
			"callback" => "reg_admin_levels_edit",
			"callback_arguments" => array(arg(4)),
			"type" => MENU_CALLBACK,
			);

		//
		// Used for interacting with registrations
		//
		$retval[] = array(
			"path" => "admin/reg/registrations",
			"title" => t("Registrations"),
			"callback" => "reg_admin_registrations",
			"type" => MENU_LOCAL_TASK,
			"weight" => 1,
			);

		$retval[] = array(
			"path" => "admin/reg/registrations/recent",
			"title" => t("Recent"),
			"type" => MENU_DEFAULT_LOCAL_TASK,
			"weight" => -10,
			);

		$retval[] = array(
			"path" => "admin/reg/registrations/search",
			"title" => t("Search"),
			"callback" => "reg_admin_registrations_search",
			"type" => MENU_LOCAL_TASK,
			"weight" => 1,
			);

		$retval[] = array(
			"path" => "admin/reg/registrations/add",
			"title" => t("Add"),
			"callback" => "reg_admin_registrations_add",
			"type" => MENU_LOCAL_TASK,
			"weight" => 2,
			);

		//
		// Viewing registration-related logs.
		//
		$retval[] = array(
			"path" => "admin/reg/logs",
			"title" => t("Logs"),
			"callback" => "reg_admin_log",
			"type" => MENU_LOCAL_TASK,
			"weight" => 2,
			);

		$retval[] = array(
			"path" => "admin/reg/logs/show",
			"title" => t("Registration Logs"),
			"callback" => "reg_admin_log",
			"type" => MENU_DEFAULT_LOCAL_TASK,
			"weight" => 2,
			);

		$retval[] = array(
			"path" => "admin/reg/logs/transactions",
			"title" => t("Transactions"),
			"callback" => "reg_admin_trans",
			"type" => MENU_LOCAL_TASK,
			"weight" => 2,
			);

		$retval[] = array(
			"path" => "admin/reg/logs/view",
			"title" => t("Logs Item Detail"),
			"callback" => "reg_admin_log_detail",
			"type" => MENU_CALLBACK,
			"callback_arguments" => array(arg(4)),
			"weight" => 2,
			);

		$retval[] = array(
			"path" => "admin/reg/transactions/view",
			"title" => t("Transaction Item Detail"),
			"callback" => "reg_admin_trans_detail",
			"type" => MENU_CALLBACK,
			"callback_arguments" => array(arg(4)),
			"weight" => 2,
			);

		return($retval);

	} // End of menu()


} // End of reg_menu class

