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

PWD=`pwd`
DIR=`basename $PWD`
TARBALL=${DIR}/anthrocon-reg.tgz

#
# We don't want any revision control files included in the atrball.
#
OPTIONS='--exclude RCS  --exclude .svn'

cd ..

tar cfz ${TARBALL} ${DIR}/* ${OPTIONS}

echo "Distfile created in '${TARBALL}'"

