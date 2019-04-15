#!/bin/sh

eval $(docker-machine env dev)
export COMPOSER_GITHUB_TOKEN=REDACTED
export WIP_SERVICE_BASE_URL="http://$(docker-machine ip $(docker-machine active)):8081"
export DEV_DOCKER_CONTAINER_SERVICE="local"
export DEV_DOCKER_IMAGE="acquia/wip-service:latest"
export DEV_DOCKER_VM_NAME="$(docker-machine active)"
export DEV_DOCKER_HOST="$(docker-machine ip $DEV_DOCKER_VM_NAME)"
export DEV_DOCKER_USER="docker"
export DEV_DOCKER_WORKSPACE="THE-PATH-TO-WIP-SERVICE-SOURCE"
export DEV_DOCKER_SYSTEM="docker-machine"
export BUGSNAG_API_KEY="REDACTED"
export BUGSNAG_STAGE="dev"
export ACQUIA_CLOUD_SITEGROUP="sfwiptravis"
export ACQUIA_CLOUD_ENVIRONMENT="prod"
export ACQUIA_CLOUD_ENDPOINT="https://cloudapi.acquia.com/v1"
export ACQUIA_CLOUD_USER="REDACTED"
export ACQUIA_CLOUD_PASSWORD="REDACTED"
export ACQUIA_CLOUD_REALM="enterprise-g1"
