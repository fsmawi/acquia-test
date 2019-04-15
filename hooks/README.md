## Cloud Hooks

Cloud Hooks is a feature of Acquia Cloud, the Drupal cloud hosting platform. For more information, see https://www.acquia.com/products-services/acquia-dev-cloud.

### How Cloud Hooks are Used in wip-service

Currently, wip-service only uses four common hooks for all environments.

- hooks/common/post-code-deploy
  - The [`link-ssh-wrapper` script](common/post-code-deploy/link-ssh-wrapper.sh) ensures that there is the appropriate symlink to the ssh_wrapper script.
  - The [`set-ecs-docker-image` script](common/post-code-deploy/set-ecs-docker-image.sh) updates the config override file for [config.ecs.yml](config/config.ecs.yml) to match the docker image tag with the deployed tag.
  - The [`update-schema` script](common/post-code-deploy/update-schema.sh) runs a schema update to ensure that the database works correctly after a deployment.

- hooks/common/post-code-update
  - The [`link-ssh-wrapper` script](common/post-code-update/link-ssh-wrapper.sh) ensures that there is the appropriate symlink to the ssh_wrapper script.

- hooks/common/post-db-copy
  - The [`sanitize-database` script](common/post-db-copy/sanitize-database.sh) sanitizes the database after a copy. Currently it truncates the `server_store` table.

- hooks/common/pre-web-activate
  - The [`update-servers` script](common/pre-web-activate/update-servers.sh) updates the server_store database table to match any changes to the webnode configuration exposed by Acquia hosting.