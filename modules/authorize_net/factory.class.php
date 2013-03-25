<?php

/**
* This class is a factory for generating authorize.net objects.
*
* All of the object creation is done here, so that it is easier
*	to keep track of class dependencies.
*/
class reg_authorize_net_factory {

	/**
	* This is the main function that is called by the outside world.
	* 
	* @param string $name The name of the object to instantiate.
	*
	* @return object The object
	*/
	function get_object($name) {

		if ($name == "reg_authorize_net") {
			$retval = $this->get_authorize_net();

		} else if ($name == "menu") {
			$retval = $this->get_menu();

		} else if ($name == "settings") {
			$retval = $this->get_settings();

		} else {
			$error = "Unknown object name: $name";
			throw new Exception($error);

		}

		return($retval);	

	} // End of get_object()


	protected function get_authorize_net() {

		$factory = new reg_factory();
		$reg = $factory->get_object("reg");
		$log = $factory->get_object("log");

		$retval = new reg_authorize_net($reg, $log);
		return($retval);
	}


	protected function get_menu() {

		//
		// Load the registration autoloader so that we acan access 
		// registration objects. This is only needed when installing 
		// this module.
		// Incidentally, things like this happen when software 
		// undergoes drastic rearchitecture, as this software did. :-(
		//
		if (!function_exists("reg_autoload")) {
			require_once(dirname(__FILE__) . "/../../reg/autoload.php");
			spl_autoload_register("reg_autoload");
		}

		$factory = new reg_factory();
		$reg = $factory->get_object("reg");

		$retval = new reg_authorize_net_menu($reg);
		return($retval);
	}


	protected function get_settings() {

		$factory = new reg_factory();
		$reg = $factory->get_object("reg");
		$log = $factory->get_object("log");

		$retval = new reg_authorize_net_settings($reg, $log);

		return($retval);

	} 

} // End of reg_authorize_net_factory class

