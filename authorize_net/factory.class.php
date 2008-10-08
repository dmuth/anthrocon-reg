<?php

/**
* This class is a factory for generating authorize.net objects.
*
* All of the object creation is done here, so that it is easier
*	to keep track of class dependencies.
*/
class authorize_net_factory {

	/**
	* This is the main function that is called by the outside world.
	* 
	* @param string $name The name of the object to instantiate.
	*
	* @return object The object
	*/
	function get_object($name) {

		if ($name == "menu") {
			$retval = $this->get_menu();

		} else if ($name == "settings") {
			$retval = $this->get_settings();

		} else {
			$error = "Unknown object name: $name";
			throw new Exception($error);

		}

		return($retval);	

	} // End of get_object()


	protected function get_menu() {
		$retval = new authorize_net_menu();
		return($retval);
	}


	protected function get_settings() {

		$reg = new reg();
		$log = new reg_log();

		$retval = new authorize_net_settings($reg, $log);

		return($retval);

	} 

} // End of authorize_net_factory class

