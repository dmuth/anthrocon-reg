<?php
/**
* Our development code.
*
* @author Douglas Muth <http://www.dmuth.org/>
*/


function reg_log_devel() {

	//reg_log_devel_1();
	//reg_log_devel_trans();

} // End of reg_log_devel()

function reg_log_devel_1() {
	reg_log_devel_2();
}
function reg_log_devel_2() {
	$arg1 = new Stdclass();
	reg_log_devel_3($arg1);
}


function reg_log_devel_3($arg1) {

	$message = "test message " . time();
	reg_log($message, 122, "notice");
	reg_log($message, 122, "warning", true);
	reg_log($message, 122, "error");

}


function reg_log_devel_trans() {

	$data = array();
	$data["cc_exp"] = array();
	$data["cc_exp"]["year"] = 2015;
	$data["cc_exp"]["month"] = 11;
	$data["cc_num"] = "1234567890123456";
	$data["badge_cost"] = 123.45;
	$data["donation"] = 456.78;
	$data["reg_trans_type_id"] = 1;
	$data["reg_payment_type_id"] = 1;

	$data["reg_id"] = 123;

	reg_log_trans($data);

} // End of reg_log_devel_trans()


