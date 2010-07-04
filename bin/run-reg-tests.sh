#!/bin/sh
#
# Wrapper to run our reg module unit tests
#

#
# Errors are fatal.
#
set -e 

#
# Get the root directory of our Drupal installation
#
set +e
DIR=`drush dd`
if test $? -ne 0
then
	echo "$0: Please run this insite a Drupal installation"
	exit 1
fi
set -e

if test "$1" == "" -o "$1" == "-h" -o "$1" == "--help"
then
	echo "You need to specify a test group or class name. "
	echo "Examples: "
	echo ""
	echo "To test a specific test group: "
	echo "$0 reg-authorize-net"
	echo "$0 --verbose reg-authorize-net"
	echo ""
	echo "To list all tests:"
	echo "$0 --list "
	echo ""
	echo "To test a specific class:"
	echo "$0 --class Reg_Util_PrintBadge_Test"
	echo "$0 --verbose --class Reg_Util_PrintBadge_Test"
	echo ""
	echo "To test all of the registration system:"
	echo "$0 reg-authorize-net,reg-functional-tests,reg-unit-tests"
	exit 1
fi


ARGS="--url http://anthrocon.localdomain/ --color "

CMD="php ${DIR}/scripts/run-tests.sh $ARGS "

$CMD $@

