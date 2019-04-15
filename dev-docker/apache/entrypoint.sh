#!/usr/bin/env bash

set -e

echo "Setting up composer..."
if [[ -z "$COMPOSER_GITHUB_TOKEN" ]]; then
  echo -e "\033[0;31mYou must have the COMPOSER_GITHUB_TOKEN env var set.\033[0m"
  exit 1
fi
/tmp/composer_installer.sh

echo "Setting up github access..."
sudo -E -H -u dev composer config -g github-oauth.github.com $COMPOSER_GITHUB_TOKEN
ssh-keyscan -H github.com >> /etc/ssh/ssh_known_hosts


echo "Installing wip-service dependencies..."
cd /var/www/html
sudo -E -H -u dev composer self-update -n
sudo -E -H -u dev composer install -n --no-progress

# Make sure we have the latest ssh_wrapper script.
mkdir -p /home/dev/bin
cp /var/www/html/scripts/ssh_wrapper /home/dev/bin/
echo "Copying the ssh_wrapper script."
if [[ ! -e /home/dev/bin/ssh_wrapper ]]; then
  echo -e "\033[0;31mFailed to copy ssh_wrapper.\033[0m"
  exit 1
fi

# Create the "silex_test" database for the unit tests.
mysql -hdb -uadmin -ppassword -e \
  "CREATE DATABASE IF NOT EXISTS silex_test"
WIP_SERVICE_DATABASE="silex_test" php vendor/bin/doctrine orm:schema-tool:update --dump-sql --force

# The main runtime "silex" database is defined in docker-compose.yml.
php vendor/bin/doctrine orm:schema-tool:update --dump-sql --force
mysql -hdb -uadmin -ppassword --database=silex -e \
  "INSERT IGNORE INTO server_store (hostname, total_threads, status) VALUES ('localhost', 3, 1)"

mysql -hdb -uadmin -ppassword --database=silex -e \
  "INSERT INTO wip_group_max_concurrency VALUES ('BuildSteps', 10)"

echo "Updating host-specific configuration..."
if [[ -z "$WIP_SERVICE_BASE_URL" ]]; then
  echo -e "\033[0;31mYou must have the WIP_SERVICE_BASE_URL env var set (this should be your ngrok URL without the trailing slash).\033[0m"
  exit 1
fi
echo "config.global.base_url: $WIP_SERVICE_BASE_URL"
sed -i "s~base_url: http://localhost~base_url: $WIP_SERVICE_BASE_URL~" /wip/config/config.global.dev.yml
echo "wipflushinglogstore.endpoint: $WIP_SERVICE_BASE_URL/logs"
sed -i "s~wipflushinglogstore.endpoint => https://wip-service.local/logs~wipflushinglogstore.endpoint => $WIP_SERVICE_BASE_URL/logs~" /wip/config/config.factory.cfg

if [[ -n "$BUGSNAG_API_KEY" ]]; then
  echo "services.bugsnag.api_key: $BUGSNAG_API_KEY"
  sed -i "s~api_key: insert_key_here~api_key: $BUGSNAG_API_KEY~" /wip/config/config.global.dev.yml
fi

if [[ -z "$DEV_DOCKER_IMAGE" ]]; then
  echo -e "\033[0;31mYou must have the DEV_DOCKER_IMAGE env var set (e.g. acquia/wip-service:MS-123).\033[0m"
  exit 1
fi
echo "config.ecs.image: $DEV_DOCKER_IMAGE"
echo "config.docker.image: $DEV_DOCKER_IMAGE"
sed -i "s~acquia/wip-service:latest~$DEV_DOCKER_IMAGE~" /wip/config/config.global.dev.yml

if [[ -z "$DEV_DOCKER_SYSTEM" ]]; then
  DEV_DOCKER_SYSTEM="docker-machine"
fi
echo "config.docker.system: $DEV_DOCKER_SYSTEM"
sed -i "s~system: docker-machine~system: $DEV_DOCKER_SYSTEM~" /wip/config/config.global.dev.yml
if [[ -z "$DEV_DOCKER_HOST" ]]; then
  echo -e "\033[0;31mYou must have the DEV_DOCKER_HOST env var set (this should be the IP address or hostname of your local machine on the local network).\033[0m"
  exit 1
fi
echo "config.docker.host: $DEV_DOCKER_HOST"
sed -i "s~host: localhost~host: $DEV_DOCKER_HOST~" /wip/config/config.global.dev.yml
if [[ -z "$DEV_DOCKER_USER" ]]; then
  echo -e "\033[0;31mYou must have the DEV_DOCKER_USER env var set (this should be the username on the Docker host with passwordless SSH access set up).\033[0m"
  exit 1
fi
echo "config.docker.username: $DEV_DOCKER_USER"
sed -i "s~username: docker-user~username: $DEV_DOCKER_USER~" /wip/config/config.global.dev.yml
if [[ -z "$DEV_DOCKER_VM_NAME" ]]; then
  echo -e "\033[0;31mYou must have the DEV_DOCKER_VM_NAME env var set (this should be the name of the virtual machine in docker-machine).\033[0m"
  exit 1
fi
if [[ -n "$DEV_DOCKER_WORKSPACE" ]]; then
  sed -i "s~workspace: /path/to/host/workspace~workspace: $DEV_DOCKER_WORKSPACE~" /wip/config/config.global.dev.yml
fi
echo "config.docker.mount: $DEV_DOCKER_MOUNT"
if [[ $DEV_DOCKER_MOUNT == "yes" ]]; then
   sed -i "s~mount: false~mount: true~" /wip/config/config.global.dev.yml
fi

if [[ "$DEV_DOCKER_DB_PERSIST" == "no" ]]; then
  echo "Truncating tables in the database..."
  mysql -hdb -uadmin -ppassword --database=silex -e \
    'truncate wip_log; truncate wip_pool; truncate wip_store; truncate wip_group_concurrency; truncate thread_store; truncate signal_callbacks; truncate signal_store;'
fi

if [[ "$DEV_DOCKER_XDEBUG" == "yes" ]]; then
echo "Configuring xdebug..."
echo "xdebug.remote_enable=on" \
  | tee -a /etc/php5/fpm/conf.d/xdebug.ini /etc/php5/cli/conf.d/20-xdebug.ini
echo "xdebug.remote_host=$DEV_DOCKER_HOST" \
  | tee -a /etc/php5/fpm/conf.d/xdebug.ini /etc/php5/cli/conf.d/20-xdebug.ini
echo "xdebug.remote_port=9000" \
  | tee -a /etc/php5/fpm/conf.d/xdebug.ini /etc/php5/cli/conf.d/20-xdebug.ini
echo "xdebug.remote_handler=dbgp" \
  | tee -a /etc/php5/fpm/conf.d/xdebug.ini /etc/php5/cli/conf.d/20-xdebug.ini
fi

if [[ "$DEV_DOCKER_PROFILE_PHP" == "yes" ]]; then
  echo "xdebug.profiler_enable=1" \
    | tee -a /etc/php5/fpm/conf.d/xdebug.ini /etc/php5/cli/conf.d/20-xdebug.ini
  echo "xdebug.profiler_output_dir=/tmp/profiler_output" \
    | tee -a /etc/php5/fpm/conf.d/xdebug.ini /etc/php5/cli/conf.d/20-xdebug.ini
fi

echo "Starting the WIP daemon..."
sudo -E -u dev bin/wipctl monitor-daemon stop
sudo -E -u dev bin/wipctl monitor-daemon start

echo "Starting fpm..."
service php5.6-fpm restart

echo "Starting apache..."
service apache2 restart

echo "Starting cron..."
cron

echo "Starting the SSH server in the foreground..."
/usr/sbin/sshd -D
