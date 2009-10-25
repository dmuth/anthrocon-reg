<?php
/**
* This class is used for finding duplicate registrations.
*/
class Reg_Util_Duplicate {

	function __construct(&$reg) {
		$this->reg = $reg;
	}


	/**
	* Get duplicate last names.
	*
	* @return array Every membership with duplicate last names.
	*/
	function getLastNames($year) {

		$retval = array();

		$field = "last";
		$query = $this->getQuery($field);
		$retval = $this->getRows($query, $field, $year);

		return($retval);

	} // End of getLastNames()


	/**
	* Get duplicate phone numbers.
	*
	* @return array Every membership with duplicate phone numbers.
	*/
	function getPhoneNumbers($year) {

		$retval = array();

		$field = "phone";
		$query = $this->getQuery($field);
		$retval = $this->getRows($query, $field, $year);

		return($retval);

	} // End of getPhoneNumbers()


	/**
	* Get duplicate email addresses.
	*
	* @return array Every membership with duplicate email addresses.
	*/
	function getEmailAddresses($year) {

		$retval = array();

		$field = "email";
		$query = $this->getQuery($field);
		$retval = $this->getRows($query, $field, $year);

		return($retval);

	} // End of getEmailAddresses()


	/**
	* Get duplicate postal addresses.
	*
	* @return array Every membership with duplicate postal addresses.
	*/
	function getAddresses($year) {

		$retval = array();

		$field = "address1";
		$query = $this->getQuery($field);
		$retval = $this->getRows($query, $field, $year);

		return($retval);

	} // End of getAddresses()


	/**
	* Get duplicate badge names.
	*
	* @return array Every membership with duplicate badge names.
	*/
	function getBadgeNames($year) {

		$retval = array();

		$field = "badge_name";
		$query = $this->getQuery($field);
		$retval = $this->getRows($query, $field, $year);

		return($retval);

	} // End of getBadgeNames()



	/**
	* Generate a query that checks a specific field for dupes.
	*
	* @param string $field The field in the reg table to check for dupes.
	*
	* @return string The query
	*/
	protected function getQuery($field) {

		$retval = "SELECT "
			. "reg.id, first, last, badge_name, year, badge_num, ${field}, "
			. "reg_type.member_type, reg_status.status "
			. "FROM {reg} "
			. "JOIN {reg_type} ON reg.reg_type_id = reg_type.id "
			. "JOIN {reg_status} ON reg.reg_status_id = reg_status.id "
			. "WHERE "
			. "year='%s' "
			. "AND ${field} IN "
			. "(SELECT ${field} FROM "
			. "(SELECT ${field}, count(id) AS num "
				. "FROM {reg} "
				. "WHERE year='%s' "
				. "GROUP BY ${field} "
				. "HAVING num > 1) "
			. "AS tbl1) "
			. "AND ${field} != '' "
			. "AND ${field} IS NOT NULL "
			. "ORDER BY {$field} "
			;

		return($retval);

	} // End of getQuery()


	/**
	* Query the database and get our matching memberships.
	*
	* @param string $query The query to run.
	*
	* @param string $field The name of the field to check for duplicates.
	*
	* @param integer $year The year to pass into the query
	*
	* @return array Array of possible matching memberships
	*/
	function getRows($query, $field, $year) {

		$retval = array();

		$query_args = array($year, $year);
		$cursor = db_query($query, $query_args);

		while ($row = db_fetch_array($cursor)) {
			$row["match"] = $row[$field];
			$retval[] = $row;
		}

		return($retval);

	} // End of getRows()


} // End of Reg_Util_Duplicate class


