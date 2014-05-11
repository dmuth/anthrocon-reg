#!/bin/bash
#
# This script makes a backup of the registration database every n number of minutes
#

set -e # Errors are fatal

pushd `dirname $0` > /dev/null

MINUTES=10

while true
do
	ARG=$1
	if test "$ARG" == "-h"
	then
		echo "Syntax: $0 [ backup_interval_in_minutes ]"
		exit 1

	elif test "$ARG" == ""
	then
		break

	else
		MINUTES=$ARG

	fi

	shift

done


NUM_SEC=$(($MINUTES * 60))
echo "Backing up registration database tables every ${MINUTES} minutes (${NUM_SEC} seconds)"

while true
do

	echo "Starting backup..."
	./mysql-reg-export-gzip.sh
	echo "Done!"

	echo "Now sleeping for ${NUM_SEC} seconds" 
	sleep $NUM_SEC

done


