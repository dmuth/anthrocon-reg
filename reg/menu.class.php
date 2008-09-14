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
	static function menu($may_cache) {

		$retval = array();

		// Debugging.  Note that this will break viewing of individual members.
		//$may_cache = 1; 
		if (!$may_cache) {
			//
			// Code that is in this block will be executed on every
			// page load.
			//

			//
			// Load our Javascript
			//
			$path = drupal_get_path("module", "reg");
			drupal_add_js($path . "/reg.js", "module");

			//
			// Include our CSS
			//
			$path = drupal_get_path("module", "reg") . "/reg.css";
			drupal_add_css($path, "module", "all", false);

		}
		
		//
		// Used for interacting with registrations
		//
		self::get_menu($retval, $may_cache);
		self::get_registrations($retval, $may_cache);

		return($retval);

	} // End of menu()


	/**
	* Our main menu items.
	*/
	static function get_menu(&$retval, $may_cache) {

			//
			// Public link
			//
			$retval[] = array(
				"path" => "reg",
				"title" => reg::YEAR ." " . t("Pre-Registration"),
				"callback" => "reg_registration",
				"access" => user_access(reg::PERM_REGISTER),
				"type" => MENU_NORMAL_ITEM,
				);

		if ($may_cache) {

			//
			// Verify a registraiton
			//
			$retval[] = array(
				"path" => "reg/verify",
				"title" => t("Verify an existing registration"),
				"callback" => "reg_verify",
				"access" => user_access(reg::PERM_REGISTER),
				"type" => MENU_NORMAL_ITEM,
				);

			//
			// Success page
			//
			$retval[] = array(
				"path" => "reg/success",
				"title" => t("Registration Successful!"),
				"callback" => "reg_success",
				"access" => user_access(reg::PERM_REGISTER),
				"type" => MENU_CALLBACK,
				);

			//
			// Admin section
			//
			$retval[] = array(
				"path" => "admin/reg",
				"title" => t("Registration Admin"),
				"callback" => "reg_admin_main",
				"access" => user_access(reg::PERM_ADMIN),
				"type" => MENU_NORMAL_ITEM,
				);

			$retval[] = array(
				"path" => "admin/reg/main",
				"title" => t("Main"),
				"callback" => "reg_admin_main",
				"type" => MENU_DEFAULT_LOCAL_TASK,
				"weight" => -10,
				);

/*
Uncomment this when we actually have stats.
		$retval[] = array(
			"path" => "admin/reg/stats",
			"title" => t("Stats"),
			"callback" => "reg_admin_stats",
			"type" => MENU_LOCAL_TASK,
			"weight" => -10,
			);
*/

			//
			// Our membership levels
			//
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

			$retval[] = array(
				"path" => "admin/reg/settings",
				"title" => t("Settings"),
				"callback" => "reg_admin_settings",
				"type" => MENU_NORMAL_ITEM,
				"weight" => 5,
				);

			$retval[] = array(
				"path" => "admin/reg/settings/main",
				"title" => t("Settings"),
				"callback" => "reg_admin_settings",
				"type" => MENU_DEFAULT_LOCAL_TASK,
				"weight" => 0,
				);

			$retval[] = array(
				"path" => "admin/reg/settings/messages",
				"title" => t("Messages"),
				"callback" => "reg_admin_settings_messages",
				"type" => MENU_LOCAL_TASK,
				"weight" => 1,
				);

			$retval[] = array(
				"path" => "admin/reg/settings/gateways/authorize.net",
				"title" => t("Authorize.net"),
				"callback" => "reg_admin_settings_authorize_net",
				"type" => MENU_LOCAL_TASK,
				"weight" => 3,
				);

		}

		if (arg(4)) {

			if (!$may_cache) {
	
				//
				// Used for editing a membership level.
				//
				$retval[] = array(
					"path" => "admin/reg/levels/list/" . arg(4) . "/edit",
					"title" => t("Edit"),
					"callback" => "reg_admin_levels_edit",
					"callback arguments" => array(arg(4)),
					"weight" => -10,
					"type" => MENU_LOCAL_TASK,
					"weight" => 0,
					);

				//
				// Used for editing a message
				//
				$retval[] = array(
					"path" => "admin/reg/settings/messages/" . arg(4) . "/edit",
					"title" => t("Edit"),
					"callback" => "reg_admin_settings_messages_edit",
					"callback arguments" => array(arg(4)),
					"type" => MENU_LOCAL_TASK,
					"weight" => 0,
					);

			}

		}


		if ($may_cache) {

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
				"path" => "admin/reg/logs/view",
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

		}

		if (arg(4)) {

			if (!$may_cache) {
				$retval[] = array(
					"path" => "admin/reg/logs/view/" . arg(4) . "/view",
					"title" => t("Logs Item Detail"),
					"callback" => "reg_admin_log_detail",
					"type" => MENU_LOCAL_TASK,
					"callback arguments" => array(arg(4)),
					"weight" => 2,
					);

				$retval[] = array(
					"path" => "admin/reg/logs/transactions/" . arg(4) . "/view",
					"title" => t("Transaction Item Detail"),
					"callback" => "reg_admin_trans_detail",
					"type" => MENU_LOCAL_TASK,
					"callback arguments" => array(arg(4)),
					"weight" => 2,
					);

			}

		}


	} // End of get_menu()


	/**
	* Menu items related to recent registrations.
	*/
	static function get_registrations(&$retval, $may_cache) {

		if ($may_cache) {
			$retval[] = array(
				"path" => "admin/reg/members",
				"title" => t("Members"),
				"callback" => "reg_admin_members",
				"type" => MENU_NORMAL_ITEM,
				"weight" => 1,
				);

			$retval[] = array(
				"path" => "admin/reg/members/search",
				"title" => t("Search"),
				"callback" => "reg_admin_search",
				"type" => MENU_LOCAL_TASK,
				"weight" => 1,
				);

			$retval[] = array(
				"path" => "admin/reg/members/add",
				"title" => t("Add"),
				"callback" => "reg_admin_members_add",
				"type" => MENU_LOCAL_TASK,
				"weight" => 2,
				);

			$retval[] = array(
				"path" => "admin/reg/members/view",
				"title" => t("Recent"),
				"type" => MENU_DEFAULT_LOCAL_TASK,
				"weight" => -10,
				);

		}

		//
		// If we have a member ID to view, add in some dynamic menu items.
		//
		if (arg(4)) {

			if (!$may_cache) {

				$retval[] = array(
					"path" => "admin/reg/members/view/" . arg(4) . "/view",
					"title" => t("View"),
					"callback" => "reg_admin_members_view",
					"callback arguments" => array(arg(4)),
					"weight" => -10,
					"type" => MENU_LOCAL_TASK,
					"weight" => 0,
					);

				$retval[] = array(
					"path" => "admin/reg/members/view/" . arg(4) . "/edit",
					"title" => t("Edit"),
					"callback" => "reg_admin_members_edit",
					"callback arguments" => array(arg(4)),
					"type" => MENU_LOCAL_TASK,
					"weight" => 1,
					);

				$retval[] = array(
					"path" => "admin/reg/members/view/" . arg(4) . "/add_note",
					"title" => t("Add Note"),
					"callback" => "reg_admin_members_add_note",
					"callback arguments" => array(arg(4)),
					"type" => MENU_LOCAL_TASK,
					"weight" => 2,
					);

				$retval[] = array(
					"path" => "admin/reg/members/view/" . arg(4) . "/cancel",
					"title" => t("Cancel Membership"),
					"callback" => "reg_admin_members_cancel",
					"callback arguments" => array(arg(4)),
					"type" => MENU_LOCAL_TASK,
					"weight" => 2,
					);

				$retval[] = array(
					"path" => "admin/reg/members/view/" . arg(4) . "/adjust",
					"title" => t("Balance Adjustment"),
					"callback" => "reg_admin_members_adjust",
					"callback arguments" => array(arg(4)),
					"type" => MENU_LOCAL_TASK,
					"weight" => 3,
					);

			}

		}

	} // End of get_registrations()


} // End of reg_menu class

