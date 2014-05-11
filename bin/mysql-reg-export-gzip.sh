#!/bin/bash
#
# This script exports registration data and gzips it into a unique filename.
#

#
# Make errors fatal
#
set -e

#
# Our target directory for writing backups
#
TARGET_DIR=$HOME/reg-backups

#
# Name the file in YYYYMMDDHHMMSS format.
#
FILE=${TARGET_DIR}/reg-dump-`date +%Y%m%d%H%M%S`.gz

#
# Change to the directory of this script
#
pushd `dirname $0` >/dev/null

#
# Get our SQL command line
#
MYSQL=`drush sql-connect`
MYSQLDUMP=`drush sql-connect |sed -e s/^mysql/mysqldump/ -e "s/--database=[^ ]\+//" `
MYSQLDUMP="${MYSQLDUMP} reg "

SQL="SHOW TABLES LIKE 'reg%'"
#SQL="SHOW TABLES LIKE 'reg_level%'" # Debugging
TABLES=""
for ROW in `echo $SQL | $MYSQL -s`
do
	#
	# Chop off the carriage returns and newlines, and glue the table
	# name onto the list.
	#
	ROW=`echo $ROW | tr -d "\r\n"`
	TABLES="$TABLES $ROW"
done


#
# If the target directory does not exist, create it
#
if test ! -d ${TARGET_DIR}
then
	if test -f ${TARGET_DIR}
	then
		echo "ERROR: File ${TARGET_DIR} exists, but not as a directory, stopping."
		exit 1
	else
		echo "Making target directory ${TARGET_DIR}..."
		mkdir ${TARGET_DIR}
		echo "Done!"

	fi

fi


#set -x # Debug
#echo $MYSQL # Debug
#echo $MYSQLDUMP # Debug
#echo $TABLES # Debug
#$MYSQLDUMP $TABLES # Debug
#exit 1 # Debug
#${MYSQLDUMP} ${TABLES} | gzip > ${FILE}
${MYSQLDUMP} ${TABLES} | gzip > ${FILE}

echo "File '${FILE}' written."


