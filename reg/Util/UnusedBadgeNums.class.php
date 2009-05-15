<?php

class Reg_Util_UnusedBadgeNums {


	function __construct(&$reg) {
		$this->reg = $reg;
	}


	/**
	* Figure out what badge numbers are unused for the current year.
	* Badge numbers from 0 to the current highest badge number are checked.
	*
	* @return array An associative array where the key and values are 
	*	available badge numbers.
	*/
	function getBadgeNums() {

		$retval = array();

		$max_badge_num = $this->getMaxBadgeNum();
		
		//
		// Get all badge numbers for this year
		//
		$year = $this->reg->get_constant("year");
		$query = "SELECT badge_num FROM {reg} "
			. "WHERE "
			. "year='%s' "
			//
			// Don't check for an empty string, as it will match badge 
			// number 0, which we want.
			//
			. "AND badge_num IS NOT NULL "
			. "ORDER BY badge_num";
		$cursor = db_query($query, $year);
	
		//
		// Loop through our badge numbers.  If we skip any badge numbers,
		// call the function to process that range.
		//
		$expected_num = 0;
		while ($row = db_fetch_array($cursor)) {

			$badge_num = $row["badge_num"];

			if ($badge_num > $expected_num) {
				$tmp = $this->processMissing($expected_num, $badge_num);
				$retval = array_merge($retval, $tmp);
			}

			$expected_num = ($badge_num + 1);

		}

		//
		// If we're missing badge numbers at the end, process those too.
		//
		if ($max_badge_num > $badge_num) {
			$tmp = $this->processMissing(($badge_num + 1), ($max_badge_num + 1));
			$retval = array_merge($tmp, $retval);
		}

		//
		// Copy our array over to one where the keys equal the values.
		//
		$tmp = $retval;
		unset($retval);
		foreach($tmp as $key => $value) {
			$retval[$value] = $value;
		}

		ksort($retval);

		return($retval);

	} // End of getBadgeNums()


	/**
	* Process our missing badge numbers.
	*
	* @param integer $expected The badge number we were expecting to see.
	*
	* @param integer $actual The actual badge number we got.
	*
	* The expected badge number, and any badge numbers after that up to 
	*	(but NOT including) the actual badge number will be computed.
	*
	* @return array Associative array of missing badge numbers.
	*/
	function processMissing($expected, $actual) {

		$retval = array();

		for ($i = $expected; $i < $actual; $i++) {
			$retval[$i] = $i;
		}

		return($retval);

	} // End of processMissing()



	/**
	* Get our current maximm badge number.
	*
	* @return integer Our current maximum badge number.
	*/
	function getMaxBadgeNum() {

		$year = $this->reg->get_constant("year");
		$query = "SELECT badge_num FROM {reg_badge_num} WHERE year='%s'";
		$cursor = db_query($query, $year);
		$row = db_fetch_array($cursor);
		$retval = $row["badge_num"];

		return($retval);

	} // End of getMaxBadgeNum()


} // End of Reg_Util_UnusedBadgeNums class

