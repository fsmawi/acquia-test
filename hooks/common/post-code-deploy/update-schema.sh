#!/usr/bin/env bash

cd /var/www/html/$1.$2

# Sleep for two seconds in order to avoid race conditions for code updates.
sleep 2

# Update the schema. The script's output contains 'Updating database schema', 
# so there is no need for an echo here.
/usr/bin/env php vendor/bin/doctrine orm:schema-tool:update --dump-sql --force
