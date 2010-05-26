<?php
/**
* This script will go through 2009 and 2010 registrations and try to 
* determine how many people pre-registered for AC 2009 also pre-registered
* again for AC 2010.
* 
*/


//
// Keep web browsers out!
//
if (php_sapi_name() != "cli") {
	print "This script should be run from the command line.";
	exit();
}


/**
* A little debugging function.
*
* It prints the message along with a time offset.
*/
function debug($message) {

	static $start;
	if (empty($start)) {
		$start = microtime(true);
		$now = $start;
	} else {
		$now = microtime(true);
	}

	$diff = sprintf("%.3f", ($now - $start));

	$output = "Debug ($diff sec): $message\n";

	print $output;

} // End of debug()


/**
* Get registration details.
*
* @param string $year The convention year
*
* @param string $end_date If specified, the latest date for registrations. 
*	Useful in determining pre-reg numbers.
*
* @return array An associative array where the key is a combination 
*	the last name and DOB, which should be unique for each attendee 
*	(excluding twins, ha!)
*/
function get_reg_by_year($year, $end_date = "") {

	$retval = array();

	$query = "SELECT last, birthdate "
		. "FROM reg "
		. "WHERE "
		. "year='%s' "
		//
		// Only complete registrations
		//
		. "AND reg_status_id=1 "
		;

	$query_args = array($year);
	if (!empty($end_date)) {
		$query .= "AND created <= UNIX_TIMESTAMP('%s') ";
		$query_args[] = $end_date;
	}

	debug($query);
	$cursor = db_query($query, $query_args);

	while ($row = db_fetch_array($cursor)) {
		$last = $row["last"];
		$birthdate = $row["birthdate"];
		$key = $last . "-" . $birthdate;
		$retval[$key] = true;
	}

	//
	// Makes things easier for me when I'm viewing/debugging.
	//
	ksort($retval);

	return($retval);

} // End of get_reg_by_year()


$ac_2009_end_date = "2009-06-30";
$ac_2010_end_date = "2010-06-21";
$prereg_2009 = get_reg_by_year(2009, $ac_2009_end_date, true);
$prereg_2010 = get_reg_by_year(2010, $ac_2010_end_date, true);
$full_2009 = get_reg_by_year(2009);
$full_2010 = get_reg_by_year(2010);

print "Note: All numbers exclude TWINS\n";
printf("Number of 2009 pre-regged attendees (registered before %s): %s\n", $ac_2009_end_date, count($prereg_2009));
printf("Number of 2009 total attendees: %s\n", count($full_2009));
printf("Number of 2010 pre-regged attendees (registered before %s): %s\n", $ac_2010_end_date, count($prereg_2010));
printf("Number of 2010 total attendees: %s\n",  count($full_2010));

$num_prereg_new = 0;
$num_prereg_repeat = 0;
$num_new = 0;
foreach ($prereg_2010 as $key => $value) {

	//
	// Is this a "new" pre-registered attendee?
	//
	if (empty($prereg_2009[$key])) {
		$num_prereg_new++;

	} else {
		$num_prereg_repeat++;
	}

	//
	// This is a "new" attendee who did not attend 2009.
	//
	if (empty($full_2009[$key])) {
		$num_new++;
	}

}

print "Number of 2010 pre-regged attendees who did NOT pre-reg at 2009 (\"prereg_new\"): $num_prereg_new\n";
print "Number of 2010 pre-regged attendees who DID pre-reg at 2009 (\"prereg_repeat\"):  $num_prereg_repeat\n";
print "Number of 2010 pre-regged attendees who did NOT attend 2009 at ALL (\"new\"):     $num_new\n";

$num = 0;
foreach ($prereg_2009 as $key => $value) {
	//
	// This person pre-regged at 2009, but did not do so at 2010.
	//
	if (empty($prereg_2010[$key])) {
		$num++;
	}
}

print "Number of 2009 pre-regged attendees who did NOT yet pre-register at 2010 (???): $num\n";

