#!/usr/bin/env bash

cd /var/www/html/$1.$2

# Sanitize the database.
echo "Sanitizing the database..."
echo "- Truncating table server_store..."
/usr/bin/env php vendor/bin/doctrine dbal:run-sql "truncate table server_store" >/dev/null

exit_code=$?
if [[ $exit_code -ne 0 ]]
  then echo "ERROR: Failed to sanitize the database."
  exit 1
else
  echo "Success!"
  exit 0
fi
