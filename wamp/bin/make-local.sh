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
MODULES="${MODULES} forum"
MODULES="${MODULES} ping"
MODULES="${MODULES} update"
MODULES="${MODULES} wunderbar"
MODULES="${MODULES} poll search statistics throttle tracker trigger"
MODULES="${MODULES} currency currency_api"
MODULES="${MODULES} countdown"
MODULES="${MODULES} flag_actions flag"
MODULES="${MODULES} bbcode"
MODULES="${MODULES} privatemsg_filter pm_email_notify privatemsg"
MODULES="${MODULES} blocks404"
MODULES="${MODULES} advanced_forum advanced_help author_pane"
MODULES="${MODULES} fasttoggle"
MODULES="${MODULES} gamertags"
MODULES="${MODULES} service_links"
MODULES="${MODULES} token token_actions"
MODULES="${MODULES} user_badges"
MODULES="${MODULES} user_stats"
MODULES="${MODULES} webform"
MODULES="${MODULES} itweak_upload"
MODULES="${MODULES} googleanalytics" 
MODULES="${MODULES} subscriptions_content subscriptions subscriptions_mail"
MODULES="${MODULES} subscriptions_ui subscriptions_taxonomy"
MODULES="${MODULES} tagadelic"
MODULES="${MODULES} user_relationships_api user_relationship_blocks"
MODULES="${MODULES} user_relationship_defaults user_relationship_elaborations"
MODULES="${MODULES} user_relationship_mailer user_relationships_ui"
MODULES="${MODULES} user_relationship_views"
MODULES="${MODULES} views views_ui views_export"
MODULES="${MODULES} fivestar fivestar_comment fivestarextra"
MODULES="${MODULES} votingapi"

for MODULE in $MODULES
do
	drush -y pm-disable $MODULE
done

