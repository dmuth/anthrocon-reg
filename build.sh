#!/bin/sh
#
# This script is used to build a file for distribution.
#

#
# Make errors fatal
#
set -e

#
# Debugging
#
#set -x

#
# Grab our highest version, so we can get a uniquely named file.
#
echo "Checking version...  Make sure you have svn set up!"
VERSION=`svn stat -v |cut -c20-26 |sort -r |head -n1 |sed -e s/" "//g`

PWD=`pwd`
DIR=`basename $PWD`
TARBALL=${DIR}/anthrocon-reg-build_${VERSION}.tgz

#
# We don't want any revision control files included in the atrball.
#
OPTIONS='--exclude RCS  --exclude .svn'

cd ..

tar cfz ${TARBALL} ${DIR}/* ${OPTIONS}

echo "Distfile created in '${TARBALL}'"

