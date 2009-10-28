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
	function menu() {

		$retval = array();

		//
		// Our public links
		//
		$this->get_public($retval);

		//
		// Admin section
		//
		$this->get_admin($retval);
		$this->get_membership_levels($retval);

		$this->get_members($retval);
		$this->get_logs($retval);

		$this->get_stats($retval);

		$this->get_settings($retval);

		$this->get_utilities($retval);

		$this->getOnsiteReg($retval);

		return($retval);

	} // End of menu()


	/**
	* Set our public links to the registration system.
	*/
	function get_public(&$retval) {

		//
		// Public link
		//
		$retval["reg"] = array(
			"title" => t("Pre-Registration"),
			"page callback" => "reg_registration",
			"access arguments" => array($this->get_constant("PERM_REGISTER")),
			"type" => MENU_NORMAL_ITEM,
			);

		//
		// Success page
		//
		$retval["reg/success"] = array(
			"title" => "Registration Successful!",
			"page callback" => "reg_success",
			"access arguments" => array($this->get_constant("PERM_REGISTER")),
			"type" => MENU_CALLBACK,
			);

		//
		// Verify a registraiton
		//
		$retval["reg/verify"] = array(
			"title" => "Verify an existing registration",
			"page callback" => "reg_verify",
			//
			// Optional argument to resend a receipt.
			//
			"page arguments" => array(3),
			"access arguments" => array($this->get_constant("PERM_REGISTER")),
			"type" => MENU_NORMAL_ITEM,
			);

	} // End of get_public()


	/**
	* Create our "Stats" menu item.
	*/
	function get_stats(&$retval) {

		$retval["admin/reg/stats"] = array(
			"title" => "Stats",
			"page callback" => "reg_admin_stats_badge",
			"access arguments" => array($this->get_constant("perm_admin")),
			"type" => MENU_NORMAL_ITEM,
			"weight" => 2,
			);

		$retval["admin/reg/stats/badge"] = array(
			"title" => "Badge Breakdown",
			"page callback" => "reg_admin_stats_badge",
			"access arguments" => array($this->get_constant("perm_admin")),
			"type" => MENU_DEFAULT_LOCAL_TASK,
			"weight" => 0,
			);

		$retval["admin/reg/stats/registration/activity"] = array(
			"title" => "Registration Activity",
			"page callback" => "reg_admin_stats_reg",
			"access arguments" => array($this->get_constant("perm_admin")),
			"type" => MENU_LOCAL_TASK,
			"weight" => 1,
			);

		$retval["admin/reg/stats/revenue"] = array(
			"title" => "Revenue",
			"page callback" => "reg_admin_stats_revenue",
			"access arguments" => array($this->get_constant("perm_admin")),
			"type" => MENU_LOCAL_TASK,
			"weight" => 2,
			);

	} // End of get_stats()


	/**
	* Get the "Main" tab under the reg admin.
	*/
	function get_admin(&$retval) {

		$retval["admin/reg"] = array(
			"title" => "Registration Admin",
			"page callback" => "reg_admin_main",
			"access arguments" => array($this->get_constant("perm_staff")),
			"type" => MENU_NORMAL_ITEM,
			);

		$retval["admin/reg/main"] = array(
			"title" => "Main",
			"page callback" => "reg_admin_main",
			"access arguments" => array($this->get_constant("perm_staff")),
			"type" => MENU_DEFAULT_LOCAL_TASK,
			"weight" => -10,
			);

	} // End of get_admin()


	/**
	* Get the "Logs" tab.
	*/
	function get_logs(&$retval) {

		//
		// Viewing registration-related logs.
		//
		$retval["admin/reg/logs"] = array(
			"title" => "Logs",
			"page callback" => "reg_admin_log",
			"access arguments" => array($this->get_constant("perm_staff")),
			"type" => MENU_NORMAL_ITEM,
			"weight" => 2,
			);

		$retval["admin/reg/logs/view"] = array(
			"title" => "Registration Logs",
			"page callback" => "reg_admin_log",
			"access arguments" => array($this->get_constant("perm_staff")),
			"type" => MENU_DEFAULT_LOCAL_TASK,
			"weight" => 2,
			);

		$retval["admin/reg/logs/transactions"] = array(
			"title" => "Transactions",
			"page callback" => "reg_admin_trans",
			"access arguments" => array($this->get_constant("perm_staff")),
			"type" => MENU_LOCAL_TASK,
			"weight" => 2,
			);

		$retval["admin/reg/logs/view/%/view"] = array(
			"title" => "Logs Item Detail",
			"page callback" => "reg_admin_log_detail",
			"access arguments" => array($this->get_constant("perm_staff")),
			"type" => MENU_LOCAL_TASK,
			"page arguments" => array(4),
			"weight" => 2,
			);

		$retval["admin/reg/logs/transactions/%/view"] = array(
			"title" => "Transaction Item Detail",
			"page callback" => "reg_admin_trans_detail",
			"access arguments" => array($this->get_constant("perm_staff")),
			"type" => MENU_LOCAL_TASK,
			"page arguments" => array(4),
			"weight" => 2,
			);

	} // End of get_logs()


	/**
	* This function gets the "Settings" menu item on the left.
	*/
	function get_settings(&$retval) {

		$retval["admin/reg/settings"] = array(
			"title" => "Settings",
			"page callback" => "reg_admin_settings",
			"access arguments" => array($this->get_constant("perm_admin")),
			"type" => MENU_NORMAL_ITEM,
			"weight" => 3,
			);

		$retval["admin/reg/settings/main"] = array(
			"title" => "Settings",
			"page callback" => "reg_admin_settings",
			"access arguments" => array($this->get_constant("perm_admin")),
			"type" => MENU_DEFAULT_LOCAL_TASK,
			"weight" => 0,
			);

		$retval["admin/reg/settings/messages"] = array(
			"title" => "Messages",
			"page callback" => "reg_admin_settings_messages",
			"access arguments" => array($this->get_constant("perm_admin")),
			"type" => MENU_LOCAL_TASK,
			"weight" => 1,
			);

		$retval["admin/reg/settings/watchlist"] = array(
			"title" => "Watchlist",
			"page callback" => "reg_admin_utils_watchlist",
			"access arguments" => array($this->get_constant("perm_admin")),
			"type" => MENU_LOCAL_TASK,
			"weight" => 1,
			);

		$retval["admin/reg/settings/watchlist/list"] = array(
			"title" => "List",
			"page callback" => "reg_admin_utils_watchlist",
			"access arguments" => array($this->get_constant("perm_admin")),
			"type" => MENU_DEFAULT_LOCAL_TASK,
			"weight" => -10,
			);

		$retval["admin/reg/settings/watchlist/add"] = array(
			"title" => "Add New Watchlist Entry",
			"page callback" => "reg_admin_utils_watchlist_edit",
			"access arguments" => array($this->get_constant("perm_admin")),
			"type" => MENU_LOCAL_TASK,
			"weight" => 0,
			);

		//
		// Used for editing a message
		//
		$retval["admin/reg/settings/messages/%/edit"] = array(
			"title" => "Edit",
			"page callback" => "reg_admin_settings_messages_edit",
			"page arguments" => array(4),
			"access arguments" => array($this->get_constant("perm_admin")),
			"type" => MENU_LOCAL_TASK,
			"weight" => 0,
			);

		//
		// Edit a watchlist entry
		//
		$retval["admin/reg/settings/watchlist/view/%/edit"] = array(
			//"title" => t("Edit"),
			"page callback" => "reg_admin_utils_watchlist_edit",
			"page arguments" => array(5),
			"access arguments" => array($this->get_constant("perm_admin")),
			"type" => MENU_CALLBACK,
			);

	} // End of get_settings()


	/**
	* Get our menu items under the "Membership Levels" tab.
	*/
	function get_membership_levels(&$retval) {

		$retval["admin/reg/settings/levels"] = array(
			"title" => "Membership Levels",
			"page callback" => "reg_admin_levels",
			"access arguments" => array($this->get_constant("perm_admin")),
			"type" => MENU_LOCAL_TASK,
			"weight" => 2,
			);

		$retval["admin/reg/settings/levels/list"] = array(
			"title" => "List",
			"page callback" => "reg_admin_levels",
			"access arguments" => array($this->get_constant("perm_admin")),
			"type" => MENU_DEFAULT_LOCAL_TASK,
			"weight" => -10,
			);

		$retval["admin/reg/settings/levels/add"] = array(
			"title" => "Add",
			"page callback" => "reg_admin_levels_edit",
			"access arguments" => array($this->get_constant("perm_admin")),
			"type" => MENU_LOCAL_TASK,
			);

		//
		// Used for editing a membership level.
		//
		$retval["admin/reg/settings/levels/list/%/edit"] = array(
			"title" => "Edit",
			"page callback" => "reg_admin_levels_edit",
			"page arguments" => array(5),
			"access arguments" => array($this->get_constant("perm_admin")),
			"weight" => -10,
			"type" => MENU_LOCAL_TASK,
			"weight" => 0,
			);

	} // End of get_membership_levels()


	/**
	* Menu items related to recent registrations.
	* This is the left-hand menu item called "Members".
	*/
	function get_members(&$retval) {

		$retval["admin/reg/members"] = array(
			"title" => "Members",
			"page callback" => "reg_admin_members",
			"access arguments" => array($this->get_constant("perm_staff")),
			"type" => MENU_NORMAL_ITEM,
			"weight" => 1,
			);

		$retval["admin/reg/members/view"] = array(
			"title" => "Recent",
			"page callback" => "reg_admin_members",
			"access arguments" => array($this->get_constant("perm_staff")),
			"type" => MENU_DEFAULT_LOCAL_TASK,
			"weight" => -10,
			);

		$retval["admin/reg/members/search"] = array(
			"title" => "Search",
			"access arguments" => array($this->get_constant("perm_staff")),
			"page callback" => "reg_admin_search",
			"type" => MENU_LOCAL_TASK,
			"weight" => 1,
			);

		$retval["admin/reg/members/search/download"] = array(
			"title" => "Search",
			"page callback" => "reg_admin_search_download",
			"access arguments" => array($this->get_constant("perm_staff")),
			"type" => MENU_LOCAL_TASK,
			"weight" => 1,
			);

		$retval["admin/reg/members/add"] = array(
			"title" => "Add",
			"page callback" => "reg_admin_members_add",
			"access arguments" => array($this->get_constant("perm_staff")),
			"type" => MENU_LOCAL_TASK,
			"weight" => 2,
			);

		//
		// If we have a member ID to view, add in some dynamic menu items.
		//
		$retval["admin/reg/members/view/%/view"] = array(
			"title" => "View",
			"access callback" => "reg_menu_display_member_menu",
			"access arguments" => array($this->get_constant("perm_staff"), 3),
			"page callback" => "reg_admin_members_view",
			"page arguments" => array(4),
			"weight" => -10,
			"type" => MENU_LOCAL_TASK,
			"weight" => 0,
			);

		$retval["admin/reg/members/view/%/edit"] = array(
			"title" => "Edit",
			"page callback" => "reg_admin_members_edit",
			"page arguments" => array(4),
			"access callback" => "reg_menu_display_member_menu",
			"access arguments" => array($this->get_constant("perm_staff"), 3),
			"type" => MENU_LOCAL_TASK,
			"weight" => 1,
			);

		$retval["admin/reg/members/view/%/add_note"] = array(
			"title" => "Add Note",
			"page callback" => "reg_admin_members_add_note",
			"page arguments" => array(4),
			"access callback" => "reg_menu_display_member_menu",
			"access arguments" => array($this->get_constant("perm_staff"), 3),
			"type" => MENU_LOCAL_TASK,
			"weight" => 2,
			);

		$retval["admin/reg/members/view/%/cancel"] = array(
			"title" => "Cancel Membership",
			"page callback" => "reg_admin_members_cancel",
			"page arguments" => array(4),
			"access callback" => "reg_menu_display_member_menu",
			"access arguments" => array($this->get_constant("perm_staff"), 3),
			"type" => MENU_LOCAL_TASK,
			"weight" => 2,
			);

		$retval["admin/reg/members/view/%/adjust"] = array(
			"title" => "Balance Adjustment",
			"page callback" => "reg_admin_members_adjust",
			"page arguments" => array(4),
			"access callback" => "reg_menu_display_member_menu",
			"access arguments" => array($this->get_constant("perm_staff"), 3),
			"type" => MENU_LOCAL_TASK,
			"weight" => 3,
			);

		$retval["admin/reg/members/view/%/print"] = array(
			"title" => "Print Badge",
			"page callback" => "reg_admin_members_print",
			"page arguments" => array(4),
			"access callback" => "reg_menu_display_member_menu",
			"access arguments" => array($this->get_constant("perm_staff"), 3),
			"type" => MENU_LOCAL_TASK,
			"weight" => 4,
			);

		$retval["admin/reg/members/view/%/validate"] = array(
			"title" => "Validate",
			"page callback" => "reg_admin_members_validate",
			"page arguments" => array(4),
			"access callback" => "reg_menu_display_member_menu",
			"access arguments" => array($this->get_constant("perm_staff"), 3),
			"type" => MENU_LOCAL_TASK,
			"weight" => 5,
			);

	} // End of get_members()


	/**
	* This function gets the "Utilities" menu item on the left.
	*/
	function get_utilities(&$retval) {

		$retval["admin/reg/utils"] = array(
			"title" => "Utilities",
			"page callback" => "reg_admin_utils_unused_badge_nums",
			"access arguments" => array($this->get_constant("perm_admin")),
			"type" => MENU_NORMAL_ITEM,
			"weight" => 4,
			);

		$retval["admin/reg/utils/unused_badge_nums"] = array(
			"title" => "Unused Badge Nums",
			"page callback" => "reg_admin_utils_unused_badge_nums",
			"access arguments" => array($this->get_constant("perm_admin")),
			"type" => MENU_DEFAULT_LOCAL_TASK,
			"weight" => -10,
			);

		$retval["admin/reg/utils/duplicate"] = array(
			"title" => "Duplicate Membership Search",
			"page callback" => "reg_admin_utils_duplicate",
			"access arguments" => array($this->get_constant("perm_admin")),
			"type" => MENU_LOCAL_TASK,
			"weight" => 1,
			);


		$retval["admin/reg/utils/print"] = array(
			"title" => "Badge Printing",
			"page callback" => "reg_admin_utils_print_queue",
			"access arguments" => array($this->get_constant("perm_admin")),
			"type" => MENU_LOCAL_TASK,
			"weight" => 2,
			);

		$retval["admin/reg/utils/print/queue"] = array(
			"title" => "Print Queue",
			"page callback" => "reg_admin_utils_print_queue",
			"access arguments" => array($this->get_constant("perm_admin")),
			"type" => MENU_DEFAULT_LOCAL_TASK,
			"weight" => 0,
			);

		$retval["admin/reg/utils/print/client"] = array(
			"title" => "Printer Client",
			"page callback" => "reg_admin_utils_print_client",
			"access arguments" => array($this->get_constant("perm_admin")),
			"type" => MENU_LOCAL_TASK,
			"weight" => 1,
			);

		//
		// Extra aguments on the end don't have to be specified here, 
		// according to the docs.
		//
		$retval["admin/reg/utils/print/client/ajax/fetch"] = array(
			"page callback" => "reg_admin_utils_print_ajax_fetch",
			"page arguments" => array(7),
			"access arguments" => array($this->get_constant("perm_admin")),
			"type" => MENU_CALLBACK,
			"weight" => 0,
			);

		$retval["admin/reg/utils/print/client/ajax/update"] = array(
			"page callback" => "reg_admin_utils_print_ajax_update",
			"page arguments" => array(7, 8),
			"access arguments" => array($this->get_constant("perm_admin")),
			"type" => MENU_CALLBACK,
			"weight" => 0,
			);

	} // End of get_utilties()


	/**
	* Onsite registration.
	*/
	function getOnsiteReg(&$retval) {

		$retval["onsitereg"] = array(
			"title" => "On-site Registration",
			"page callback" => "reg_onsitereg",
			"access arguments" => array($this->get_constant("perm_onsitereg")),
			"type" => MENU_NORMAL_ITEM,
			"weight" => 0,
			);

		$retval["onsitereg/success"] = array(
			"title" => "Success!",
			"page callback" => "reg_onsitereg_success",
			"access arguments" => array($this->get_constant("perm_onsitereg")),
			"type" => MENU_CALLBACK,
			"weight" => 0,
			);

	} // End of getOnsiteReg()


} // End of reg_menu class

