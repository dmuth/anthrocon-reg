<?php

/**
* This class is just for our menu function, since it is going to grow 
* quite big. :-)
*
* Do NOT name this class reg_menu!  If a function and class
*	share identical names in PHP 5.2.4, all sorts of bad things happen!
*	I learned this the hard way. :-(
*/
class reg_menus extends reg {


	/**
	* @var Our reg object.
	*/
	protected $reg;

	
	function __construct() {
	} // End of __construct()


	/**
	* Generate our menu items and callbacks for this module.
	*
	* @return array Scalar array of menu data.
	*/
	function menu($may_cache) {

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
		// Our public links
		//
		$this->get_public($retval, $may_cache);

		//
		// Admin section
		//
		$this->get_admin($retval, $may_cache);
		$this->get_membership_levels($retval, $may_cache);

		$this->get_members($retval, $may_cache);
		$this->get_logs(&$retval, $may_cache);

		$this->get_stats($retval, $may_cache);

		$this->get_settings($retval, $may_cache);

		$this->get_utilities($retval, $may_cache);

		$this->getOnsiteReg($retval, $may_cache);

		return($retval);

	} // End of menu()


	/**
	* Set our public links to the registration system.
	*/
	function get_public(&$retval, $may_cache) {

		if ($may_cache) {

			//
			// Public link
			//
			$retval[] = array(
				"path" => "reg",
				"title" => $this->get_constant("YEAR") ." " 
					. t("Pre-Registration"),
				"callback" => "reg_registration",
				"access" => user_access($this->get_constant("PERM_REGISTER")),
				"type" => MENU_NORMAL_ITEM,
				);

			//
			// Success page
			//
			$retval[] = array(
				"path" => "reg/success",
				"title" => t("Registration Successful!"),
				"callback" => "reg_success",
				"access" => user_access($this->get_constant("PERM_REGISTER")),
				"type" => MENU_CALLBACK,
				);

		} else {
			//
			// Since this URL involes an argument, it cannot be cached.
			//

			//
			// Verify a registraiton
			//
			$retval[] = array(
				"path" => "reg/verify",
				"title" => t("Verify an existing registration"),
				"callback" => "reg_verify",
				//
				// Optional argument to resend a receipt.
				//
				"callback arguments" => array(arg(3)),
				"access" => user_access($this->get_constant("PERM_REGISTER")),
				"type" => MENU_NORMAL_ITEM,
				);

		}

	} // End of get_public()


	/**
	* Create our "Stats" menu item.
	*/
	function get_stats(&$retval, $may_cache) {

		if ($may_cache) {

			$retval[] = array(
				"path" => "admin/reg/stats",
				"title" => t("Stats"),
				"access" => user_access($this->get_constant("perm_admin")),
				"callback" => "reg_admin_stats_badge",
				"type" => MENU_NORMAL_ITEM,
				"weight" => 2,
				);

			$retval[] = array(
				"path" => "admin/reg/stats/badge",
				"title" => t("Badge Breakdown"),
				"callback" => "reg_admin_stats_badge",
				"type" => MENU_DEFAULT_LOCAL_TASK,
				"weight" => 0,
				);

			$retval[] = array(
				"path" => "admin/reg/stats/registration/activity",
				"title" => t("Registration Activity"),
				"callback" => "reg_admin_stats_reg",
				"type" => MENU_LOCAL_TASK,
				"weight" => 1,
				);

			$retval[] = array(
				"path" => "admin/reg/stats/revenue",
				"title" => t("Revenue"),
				"callback" => "reg_admin_stats_revenue",
				"type" => MENU_LOCAL_TASK,
				"weight" => 2,
				);

		}

	} // End of get_stats()


	/**
	* Get the "Main" tab under the reg admin.
	*/
	function get_admin(&$retval, $may_cache) {

		if ($may_cache) {

			$retval[] = array(
				"path" => "admin/reg",
				"title" => t("Registration Admin"),
				"callback" => "reg_admin_main",
				"access" => user_access($this->get_constant("perm_staff")) 
					|| user_access($this->get_constant("perm_admin")),
				"type" => MENU_NORMAL_ITEM,
				);

			$retval[] = array(
				"path" => "admin/reg/main",
				"title" => t("Main"),
				"callback" => "reg_admin_main",
				"type" => MENU_DEFAULT_LOCAL_TASK,
				"weight" => -10,
				);

		}

	} // End of get_admin()


	/**
	* Get the "Logs" tab.
	*/
	function get_logs(&$retval, $may_cache) {

		if ($may_cache) {

			//
			// Viewing registration-related logs.
			//
			$retval[] = array(
				"path" => "admin/reg/logs",
				"title" => t("Logs"),
				"callback" => "reg_admin_log",
				"access" => user_access($this->get_constant("perm_staff")) 
					|| user_access($this->get_constant("perm_admin")),
				"type" => MENU_NORMAL_ITEM,
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

	} // End of get_logs()


	/**
	* This function gets the "Settings" menu item on the left.
	*/
	function get_settings(&$retval, $may_cache) {

		if ($may_cache) {

			$retval[] = array(
				"path" => "admin/reg/settings",
				"title" => t("Settings"),
				"callback" => "reg_admin_settings",
				"access" => user_access($this->get_constant("perm_admin")),
				"type" => MENU_NORMAL_ITEM,
				"weight" => 3,
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
				"path" => "admin/reg/settings/watchlist",
				"title" => t("Watchlist"),
				"callback" => "reg_admin_utils_watchlist",
				"type" => MENU_LOCAL_TASK,
				"weight" => 1,
				);

			$retval[] = array(
				"path" => "admin/reg/settings/watchlist/list",
				"title" => t("List"),
				"callback" => "reg_admin_utils_watchlist",
				"type" => MENU_DEFAULT_LOCAL_TASK,
				"weight" => -10,
				);

			$retval[] = array(
				"path" => "admin/reg/settings/watchlist/add",
				"title" => t("Add New Watchlist Entry"),
				"callback" => "reg_admin_utils_watchlist_edit",
				"type" => MENU_LOCAL_TASK,
				"weight" => 0,
				);

		}

		if (arg(4)) {

			if (!$may_cache) {
	
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

		if (arg(5)) {

			if (!$may_cache) {
				$retval[] = array(
					"path" => "admin/reg/settings/watchlist/view/" . arg(5) . "/edit",
					//"title" => t("Edit"),
					"callback" => "reg_admin_utils_watchlist_edit",
					"callback arguments" => array(arg(5)),
					"type" => MENU_CALLBACK,
					);
			}
		}

	} // End of get_settings()


	/**
	* Get our menu items under the "Membership Levels" tab.
	*/
	function get_membership_levels(&$retval, $may_cache) {

		if ($may_cache) {

			$retval[] = array(
				"path" => "admin/reg/settings/levels",
				"title" => t("Membership Levels"),
				"callback" => "reg_admin_levels",
				"type" => MENU_LOCAL_TASK,
				"weight" => 2,
				);

			$retval[] = array(
				"path" => "admin/reg/settings/levels/list",
				"title" => t("List"),
				"callback" => "reg_admin_levels",
				"type" => MENU_DEFAULT_LOCAL_TASK,
				"weight" => -10,
				);

			$retval[] = array(
				"path" => "admin/reg/settings/levels/add",
				"title" => t("Add"),
				"callback" => "reg_admin_levels_edit",
				"type" => MENU_LOCAL_TASK,
				);

		}

		if (arg(5)) {

			if (!$may_cache) {
	
				//
				// Used for editing a membership level.
				//
				$retval[] = array(
					"path" => "admin/reg/settings/levels/list/" . arg(5) . "/edit",
					"title" => t("Edit"),
					"callback" => "reg_admin_levels_edit",
					"callback arguments" => array(arg(5)),
					"weight" => -10,
					"type" => MENU_LOCAL_TASK,
					"weight" => 0,
					);

			}

		}

	} // End of get_membership_levels()


	/**
	* Menu items related to recent registrations.
	* This is the left-hand menu item called "Members".
	*/
	function get_members(&$retval, $may_cache) {

		if ($may_cache) {
			$retval[] = array(
				"path" => "admin/reg/members",
				"title" => t("Members"),
				"callback" => "reg_admin_members",
				"access" => user_access($this->get_constant("perm_staff")) 
					|| user_access($this->get_constant("perm_admin")),
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
				"path" => "admin/reg/members/search/download",
				"title" => t("Search"),
				"callback" => "reg_admin_search_download",
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

				$retval[] = array(
					"path" => "admin/reg/members/view/" . arg(4) . "/print",
					"title" => t("Print Badge"),
					"callback" => "reg_admin_members_print",
					"callback arguments" => array(arg(4)),
					"type" => MENU_LOCAL_TASK,
					"weight" => 4,
					);

				$retval[] = array(
					"path" => "admin/reg/members/view/" . arg(4) . "/validate",
					"title" => t("Validate"),
					"callback" => "reg_admin_members_validate",
					"callback arguments" => array(arg(4)),
					"type" => MENU_LOCAL_TASK,
					"weight" => 5,
					);

			}

		}

	} // End of get_members()


	/**
	* This function gets the "Utilities" menu item on the left.
	*/
	function get_utilities(&$retval, $may_cache) {

		if ($may_cache) {

			$retval[] = array(
				"path" => "admin/reg/utils",
				"title" => t("Utilities"),
				"callback" => "reg_admin_utils_unused_badge_nums",
				"access" => user_access($this->get_constant("perm_admin")),
				"type" => MENU_NORMAL_ITEM,
				"weight" => 4,
				);

			$retval[] = array(
				"path" => "admin/reg/utils/unused_badge_nums",
				"title" => t("Unused Badge Nums"),
				"callback" => "reg_admin_utils_unused_badge_nums",
				"type" => MENU_DEFAULT_LOCAL_TASK,
				"weight" => -10,
				);

			$retval[] = array(
				"path" => "admin/reg/utils/duplicate",
				"title" => t("Duplicate Membership Search"),
				"callback" => "reg_admin_utils_duplicate",
				"type" => MENU_LOCAL_TASK,
				"weight" => 1,
				);


			$retval[] = array(
				"path" => "admin/reg/utils/print",
				"title" => t("Badge Printing"),
				"callback" => "reg_admin_utils_print_queue",
				"type" => MENU_LOCAL_TASK,
				"weight" => 2,
				);

			$retval[] = array(
				"path" => "admin/reg/utils/print/queue",
				"title" => t("Print Queue"),
				"callback" => "reg_admin_utils_print_queue",
				"type" => MENU_DEFAULT_LOCAL_TASK,
				"weight" => 0,
				);

			$retval[] = array(
				"path" => "admin/reg/utils/print/client",
				"title" => t("Printer Client"),
				"callback" => "reg_admin_utils_print_client",
				"type" => MENU_LOCAL_TASK,
				"weight" => 1,
				);

		} // if ($may_cache

		if (!$may_cache) {

			$action = arg(6);

			if ($action == "fetch") {
				$retval[] = array(
					"path" => "admin/reg/utils/print/client/ajax/fetch",
					"callback" => "reg_admin_utils_print_ajax_fetch",
					"callback arguments" => array(arg(7)),
					"type" => MENU_CALLBACK,
					"weight" => 0,
					);

			} else if ($action == "update") {

				$retval[] = array(
					"path" => "admin/reg/utils/print/client/ajax/update",
					"callback" => "reg_admin_utils_print_ajax_update",
					"callback arguments" => array(arg(7), arg(8)),
					"type" => MENU_CALLBACK,
					"weight" => 0,
					);

			}

		}

	} // End of get_utilties()


	/**
	* Onsite registration.
	*/
	function getOnsiteReg(&$retval, $may_cache) {

		if ($may_cache) {

			$retval[] = array(
				"path" => "onsitereg",
				"title" => t("On-site Registration"),
				"callback" => "reg_onsitereg",
				"access" => user_access($this->get_constant("perm_onsitereg")),
				"type" => MENU_NORMAL_ITEM,
				"weight" => 0,
				);

			$retval[] = array(
				"path" => "onsitereg/success",
				"title" => t("Success!"),
				"callback" => "reg_onsitereg_success",
				"access" => user_access($this->get_constant("perm_onsitereg")),
				"type" => MENU_CALLBACK,
				"weight" => 0,
				);
		}

	} // End of getOnsiteReg()


} // End of reg_menu class

