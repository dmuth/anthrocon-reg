#!/bin/sh
#
# This script modifies a Drupal installation so that it can better run
# locally, be disabling certain modules
#

#
#  Errors are fatal
#
set -e

MODULES=""
MODULES="${MODULES} aggregator"
MODULES="${MODULES} xmlsitemap xmlsitemap_engines xmlsitemap_node xmlsitemap_taxonomy xmlsitemap_user"
MODULES="${MODULES} ping"
MODULES="${MODULES} update"
MODULES="${MODULES} wunderbar"

for MODULE in $MODULES
do
	drush -y pm-disable $MODULE
done

