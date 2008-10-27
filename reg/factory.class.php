<?php

/**
* This factory class will (eventually) be responsible for all object creation
*	in this module.  In addition to having object creation done here, we will
*	also have dependency injection done here so that the relationship between
*	different classes becomes clearer.
*
*/
class reg_factory {


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

		if ($name == "theme") {
			$retval = $this->get_theme();

		} else if ($name == "admin") {
			$retval = $this->get_admin();

		} else if ($name == "admin_member") {
			$retval = $this->get_admin_member();

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

		} else {
			$error = "Unknown object name: $name";
			throw new Exception($error);

		}

		return($retval);

	} // End of get_object()


	protected function get_theme() {
		$retval = new reg_theme();
		return($retval);
	}


	protected function get_admin() {
		$log = $this->get_log();
		$retval = new reg_admin($log);
		return($retval);
	}


	protected function get_admin_member() {
		$log = $this->get_log();
		$retval = new reg_admin_member($log);
		return($retval);
	}


	protected function get_captcha() {
		$retval = new reg_captcha();
		return($retval);
	}


	protected function get_email() {
		$log = $this->get_log();
		$message = $this->get_message();
		$form = $this->get_form();
		$retval = new reg_email($message, $log, $form);
		return($retval);
	}

	protected function get_fake() {
		$retval = new reg_fake();
		return($retval);
	}


	protected function get_form() {
		$retval = new reg_form();
		return($retval);
	}

	protected function get_log() {
		$retval = new reg_log();
		return($retval);
	}

	protected function get_member() {
		$log = $this->get_log();
		//$form = $this->get_form();
		//
		// Don't include the reg_form class due to circular dependencies.	
		//
		$retval = new reg_member($log);
		return($retval);
	}


	protected function get_menu() {
		$retval = new reg_menus();
		return($retval);
	}

	protected function get_message() {
		$log = new reg_log();
		$retval = new reg_message($reg_log);
		return($retval);
	}

	protected function get_reg() {
		$retval = new reg();
		return($retval);
	}

	protected function get_success() {
		$message = $this->get_message();
		$log = $this->get_log();
		$retval = new reg_success($message, $log);
		return($retval);
	}


} // End of reg_factory class

