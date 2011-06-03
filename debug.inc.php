<?php
/**
* Our functions that relate to debugging.
*
* @author Douglas Muth <http://www.dmuth.org/>
*/


/**
* Our main debug function.
*
* @param string $message
*/
function reg_debug($message) {

	drupal_set_message("Reg Debug: " . $message);

} // End of reg_debug()

