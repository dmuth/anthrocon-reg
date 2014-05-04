#!/bin/sh
#
# This script exports registration data and gzips it into a unique filename.
#

#
# Make errors fatal
#
set -e

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
# Name the file in YYYYMMDDHHMMSS format.
#
FILE=reg-dump-`date +%Y%m%d%H%M%S`.gz
FILE="${HOME}/${FILE}"

#set -x # Debug
#echo $MYSQL # Debug
#echo $MYSQLDUMP # Debug
#echo $TABLES # Debug
#$MYSQLDUMP $TABLES # Debug
#exit 1 # Debug
#${MYSQLDUMP} ${TABLES} | gzip > ${FILE}
${MYSQLDUMP} ${TABLES} | gzip > ${FILE}

echo "File '${FILE}' written."


