#!/usr/bin/env bash

# This script is responsible for updating the server store.
#
# When a webnode is launched, this script is called by Acquia Hosting with
# the sitegroup name and environment name as arguments.
#
# This script will cause the server_store database table to match the webnode
# configuration exposed by Acquia hosting. If any webnodes are missing from
# the table, they will be added. Any servers in the server_store table that
# are unknown to Acquia hosting will be disabled.
#
# Note that if this scripts exits with a non-zero code it will prevent the
# webnode from being activated. The wipctl 'server' command will exit with
# a value '1' if a change was made, and a '0' if no changes were necessary.
#
# Since the point of this script is to make new webnodes available for Wip
# processing, a failure is not catastrophic to the system, and the additional
# webnode will still be made available for processing web requests, thus
# offloading other webnodes. There is less damage caused by ignoring any
# such failure than by disallowing the webnode to fully launch, so this
# script always exits with code 0.
/var/www/html/$1.$2/bin/wipctl server --activate-servers `hostname` update
exit 0
