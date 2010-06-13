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

if test "$1" == "-h"
then
	echo "Syntax: $0 [git]"
	exit 1
fi

GIT=""
if test "$1" == "git"
then
	GIT=1
fi

#
# Grab our highest version, so we can get a uniquely named file.
#
echo "Checking version...  Make sure you have svn set up!"
#VERSION=`svn stat -v |cut -c20-26 |sort -r |head -n1 |sed -e s/" "//g`
VERSION=`git svn info |grep "Revision" |cut -d: -f2 |sed -e s/[^0-9]//`

PWD=`pwd`
DIR=`basename $PWD`

#
# We don't want any revision control files included in the atrball.
#
OPTIONS="--exclude RCS --exclude .svn "

if test ! "$GIT"
then
	TARBALL=${DIR}/anthrocon-reg-build_${VERSION}.tgz
	OPTIONS="$OPTIONS --exclude .git"
	FILES="${DIR}/*"
else 
	TARBALL=${DIR}/anthrocon-reg-build_${VERSION}-git.tgz
	FILES="${DIR}/* ${DIR}/.git"
fi

cd ..

tar cfzv ${TARBALL} ${FILES} ${OPTIONS}

echo "Distfile created in '${TARBALL}'"

