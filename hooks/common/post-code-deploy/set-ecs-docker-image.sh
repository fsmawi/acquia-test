#!/usr/bin/env bash

ECS_CONFIG_FILE="/mnt/files/$1.$2/nobackup/config/config.ecs.yml"

# If the config file exists and we are deploying a tag, we should assume that
# the docker image tag also exists and corresponds with the code tag.
if [[ -f $ECS_CONFIG_FILE ]] && [[ $4 == tags/* ]]; then
  DOCKER_TAG=$(echo $4 | sed 's@^tags/@@')
  /usr/bin/env sed -r -i "s@(acquia/wip-service:).*\"@\1${DOCKER_TAG}\"@" $ECS_CONFIG_FILE
fi
