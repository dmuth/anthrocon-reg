<?php
/**
* This file holds our autoloader.
* It is included by the reg module and the authorize.net module.
* The reason for the authorize.net module including it is because 
* the reg module isn't yet loaded when trying to install the
* authorize.net module and it needs a way to find key classes, 
* such as reg_log.
*/


/**
* Define our autoloader.
* @param string $class The class that PHP is trying to load
*/
function reg_autoload($class) {

	//
	// Only load classes we're responsible for.
	//
	if (
		!preg_match("/^reg/", $class)
		|| preg_match("/^reg_authorize_net/", $class)
		) {
		return(null);
	}

	//print "CLASS: $class<br>\n"; // Debugging
	$file = str_replace("_", "/", $class);
	//print __FUNCTION__ . "(): $class<br>\n"; // Debugging
	$file = dirname(__FILE__) . "/../" . $file . ".class.php";
	//print "FILE: $file<br>\n"; // Debugging

	include_once($file);

} // End of reg_autoload()


