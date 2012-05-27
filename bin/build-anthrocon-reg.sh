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
# Set the version based on the date
#
VERSION=`date +%Y%m%dT%H%M%S`

PWD=`pwd`
DIR=`basename $PWD`

#
# We don't want any old revision control files included in the atrball.
#
OPTIONS="--exclude RCS --exclude .svn "

#
# We don't want any previously created tarballs, either.
#
OPTIONS="${OPTIONS} --exclude *.tgz"

TARBALL=${DIR}/anthrocon-reg-build_${VERSION}.tgz

#
# The reason we're going up a directory and using all of the 
# filenames is that in my test environment, the reg directory 
# is a symlink, and tar doesn't follow symlinks by default.
#
FILES="${DIR}/* ${DIR}/.git"
cd ..

tar cfzv ${TARBALL} ${FILES} ${OPTIONS}

echo "Distfile created in '${TARBALL}'"

