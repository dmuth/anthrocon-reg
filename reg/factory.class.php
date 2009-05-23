<?php

/**
* This factory class will (eventually) be responsible for all object creation
*	in this module.  In addition to having object creation done here, we will
*	also have dependency injection done here so that the relationship between
*	different classes becomes clearer.
*
*/
class reg_factory {

	function __construct() {
	}


	/**
	* This is the main function that instantiates and object and treutns it.
	*
	* @param $name The name of the class to instantiate. (minus the "reg_" 
	*	prefix)
	*
	* @return object The instantiated object.
	*/
	public function get_object($name) {

		$retval = "";

		//
		// Since we may have issues while transferring to CamelCase, let's 
		// make this function case-insensitive to be safe.
		//
		$name = strtolower($name);

		if ($name == "theme") {
			$retval = $this->get_theme();

		} else if ($name == "admin") {
			$retval = $this->get_admin();

		} else if ($name == "admin_adjust") {
			$retval = $this->get_admin_adjust();

		} else if ($name == "admin_cancel") {
			$retval = $this->get_admin_cancel();

		} else if ($name == "admin_level") {
			$retval = $this->get_admin_level();

		} else if ($name == "admin_log") {
			$retval = $this->get_admin_log();

		} else if ($name == "admin_log_view") {
			$retval = $this->get_admin_log_view();

		} else if ($name == "admin_log_search") {
			$retval = $this->get_admin_log_search();

		} else if ($name == "admin_member") {
			$retval = $this->get_admin_member();

		} else if ($name == "admin_search") {
			$retval = $this->get_admin_search();

		} else if ($name == "admin_stats") {
			$retval = $this->get_admin_stats();

		} else if ($name == "admin_search_download") {
			$retval = $this->get_admin_search_download();

		} else if ($name == "admin_settings") {
			$retval = $this->get_admin_settings();

		} else if ($name == "admin_settings_message") {
			$retval = $this->get_admin_settings_message();

		} else if ($name == "captcha") {
			$retval = $this->get_captcha();

		} else if ($name == "email") {
			$retval = $this->get_email();

		} else if ($name == "fake") {
			$retval = $this->get_fake();

		} else if ($name == "form") {
			$retval = $this->get_form();

		} else if ($name == "log") {
			$retval = $this->get_log();

		} else if ($name == "member") {
			$retval = $this->get_member();

		} else if ($name == "menu") {
			$retval = $this->get_menu();

		} else if ($name == "message") {
			$retval = $this->get_message();

		} else if ($name == "reg") {
			$retval = $this->get_reg();

		} else if ($name == "success") {
			$retval = $this->get_success();

		} else if ($name == "verify") {
			$retval = $this->get_verify();

		} else if ($name == "util_unusedbadgenums") {
			$retval = $this->getUtil_UnusedBadgeNums();

		} else if ($name == "util_unusedbadgenumsdisplay") {
			$retval = $this->getUtil_UnusedBadgeNumsDisplay();

		} else {
			$error = "Unknown object name: $name";
			throw new Exception($error);

		}

		return($retval);

	} // End of get_object()


	protected function get_theme() {
		$message = $this->get_message();
		$fake = $this->get_fake();
		$log = $this->get_log();
		$retval = new reg_theme($message, $fake, $log);
		return($retval);
	}


	protected function get_admin() {
		$log = $this->get_admin_log_view();
		$log_write = $this->get_log();
		$retval = new reg_admin($log, $log_write);
		return($retval);
	}


	protected function get_admin_adjust() {
		$log = $this->get_log();
		$reg_admin_member = $this->get_admin_member();
		$retval = new reg_admin_adjust($log, $reg_admin_member);
		return($retval);
	}


	protected function get_admin_cancel() {
		$log = $this->get_log();
		$reg_admin_member = $this->get_admin_member();
		$retval = new reg_admin_cancel($log, $reg_admin_member);
		return($retval);
	}


	protected function get_admin_level() {
		$log = $this->get_log();
		$retval = new reg_admin_level($log);
		return($retval);
	}


	protected function get_admin_log() {
		$message = $this->get_message();
		$fake = $this->get_fake();
		$log = $this->get_log();
		$retval = new reg_admin_log($message, $fake, $log);
		return($retval);
	}

	protected function get_admin_log_search() {
		$message = $this->get_message();
		$fake = $this->get_fake();
		$log = $this->get_log();
		$retval = new reg_admin_log_search($message, $fake, $log);
		return($retval);
	}


	protected function get_admin_log_view() {
		$message = $this->get_message();
		$fake = $this->get_fake();
		$log = $this->get_log();
		$retval = new reg_admin_log_view($message, $fake, $log);
		return($retval);
	}


	protected function get_admin_member() {
		$log = $this->get_log();
		$admin_log_view = $this->get_admin_log_view();
		$retval = new reg_admin_member($log, $admin_log_view);
		return($retval);
	}


	protected function get_admin_search() {
		$admin_member = $this->get_admin_member();
		$message = $this->get_message();
		$fake = $this->get_fake();
		$log = $this->get_log();
		$retval = new reg_admin_search($message, $fake, $log, $admin_member);
		return($retval);
	}


	protected function get_admin_stats() {
		$message = $this->get_message();
		$fake = $this->get_fake();
		$log = $this->get_log();
		$search = $this->get_admin_search();
		$retval = new reg_admin_stats($message, $fake, $log, $search);
		return($retval);
	}


	protected function get_admin_search_download() {
		$message = $this->get_message();
		$fake = $this->get_fake();
		$log = $this->get_log();
		$admin_member = $this->get_admin_member();
		$retval = new reg_admin_search_download($message, $fake, $log, $admin_member);
		return($retval);
	}


	protected function get_admin_settings() {
		$admin = $this->get_admin();
		$form = $this->get_form();
		$retval = new reg_admin_settings($admin);
		return($retval);
	}

	protected function get_admin_settings_message() {
		$message = $this->get_message();
		$log = $this->get_log();
		$retval = new reg_admin_settings_message($message, $log);
		return($retval);
	}

	protected function get_captcha() {
		$log = $this->get_log();
		$retval = new reg_captcha($log);
		return($retval);
	}


	protected function get_email() {
		$log = $this->get_log();
		$message = $this->get_message();
		//$form = $this->get_form();
		//
		// Don't include the reg_form class due to circular dependencies.	
		//
		$retval = new reg_email($message, $log);
		return($retval);
	}

	protected function get_fake() {
		$retval = new reg_fake();
		return($retval);
	}


	protected function get_form() {

		$fake = $this->get_fake();
		$log = $this->get_log();
		$admin_member = $this->get_admin_member();
		$member = $this->get_member();
		$captcha = $this->get_captcha();
		$message = $this->get_message();

		$retval = new reg_form($fake, $log, $admin_member, $member, 
			$captcha, $message);
		return($retval);
	}

	protected function get_log() {
		$retval = new reg_log();
		return($retval);
	}

	protected function get_member() {
		$log = $this->get_log();
		$email = $this->get_email();
		//$form = $this->get_form();
		//
		// Don't include the reg_form class due to circular dependencies.	
		//
		$retval = new reg_member($log, $email);
		return($retval);
	}


	protected function get_menu() {
		$retval = new reg_menus();
		return($retval);
	}

	protected function get_message() {
		$log = $this->get_log();
		$retval = new reg_message($log);
		return($retval);
	}

	protected function get_reg() {
		$message = $this->get_message();
		$fake = $this->get_fake();
		$log = $this->get_log();
		$retval = new reg($message, $fake, $log);
		return($retval);
	}

	protected function get_success() {
		$message = $this->get_message();
		$log = $this->get_log();
		$retval = new reg_success($message, $log);
		return($retval);
	}

	protected function get_verify() {
		$message = $this->get_message();
		$log = $this->get_log();
		$email = $this->get_email();
		$retval = new reg_verify($message, $log, $email);
		return($retval);
	}

	protected function getUtil_UnusedBadgeNums() {
		$reg = $this->get_reg();
		$retval = new reg_Util_UnusedBadgeNums($reg);
		return($retval);
	}

	protected function getUtil_UnusedBadgeNumsDisplay() {
		$reg = $this->get_reg();
		$util = $this->getUtil_UnusedBadgeNums();
		$log = $this->get_log();
		$retval = new reg_Util_UnusedBadgeNumsDisplay($reg, $util, $log);
		return($retval);
	}


} // End of reg_factory class

