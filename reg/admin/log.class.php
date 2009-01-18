<?php

/**
* This class is used to hold our log and transaction-related functions which
* are only used be an admin.  Stuff such as code to print up recent log
* entries, for example.
*/
class reg_admin_log extends reg {


	function __construct($message, $fake, $log) {
        parent::__construct($message, $fake, $log);
	}

} // End of reg_log class


