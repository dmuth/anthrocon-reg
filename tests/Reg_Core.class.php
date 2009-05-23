<?php
/**
* This is a core class which will provide some functions for the other 
*	test classes.
*/
class Test_Core {


	/**
	* Our constructor.
	*
	* @param object $test A testing object from the Simepletest module, 
	*	which extends DrupalTestCase.
	*/
	function __construct(&$test, &$reg) {

		$this->test = $test;
		$this->reg = $reg;

	} // End of __construct()


	/** 
	* This function is only meant to be called by tests.  It checks to see
	* if certain settings are set properly for testing.
	*/  
	function checkSettings() {
     
		$output = variable_get($this->reg->get_constant(
			"form_admin_no_ssl_redirect"), "");
		$this->test->assertEqual(1, $output, t("SSL redirection is NOT disabled.  "
			. "Check the settings page."));

		$output = variable_get($this->reg->get_constant(
			"form_admin_no_captcha"), "");
		$this->test->assertEqual(1, $output, t("Captchas are NOT disabled.  "
			. "Check the settings page."));

    } // End of checkSettings()
        

} // End of Test_Core class


