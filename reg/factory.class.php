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

		} else if ($name == "fake") {
			$retval = $this->get_fake();

		} else if ($name == "form") {
			$retval = $this->get_form();

		} else if ($name == "reg") {
			$retval = $this->get_reg();

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


	protected function get_fake() {
		$retval = new reg_fake();
		return($retval);
	}


	protected function get_form() {
		$retval = new reg_form();
		return($retval);
	}

	protected function get_reg() {
		$retval = new reg();
		return($retval);
	}


} // End of reg_factory class

