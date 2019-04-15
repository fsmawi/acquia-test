Docker Development Environment
==============================

This directory contains [Docker Compose][compose] instructions for putting
together a local development environment as a replacement for the hosted
Wip Service controller. It spins up 2 containers: apache, and db.

Your workspace is mounted directly into the Wip Service and Wip task containers
so any changes to the source files are reflected in the container immediately.

Wip task containers may be run via the local Docker installation or on ECS.
There are different requirements for each option.


Requirements
------------

- [Docker Toolbox][toolbox] on Mac or Docker Compose on Linux.
- [Wip Client][wip-client] or [buildsteps.phar][phar] for executing builds.
- An Acquia hosting site with a git repository containing build instructions
  and where build artifacts can be pushed. See
  [Invoking Build Steps][invoking-buildsteps] for more information.
- [An Acquia hosting account][acquia_account] with a free site.


Optional dependencies
---------------------

- The Acquia Cloud credentials necessary for running the wipng integration test
  suite.
- A BuildSteps container image pushed to Docker Hub. Only necessary for running
  containers on ECS.
- Amazon AWS access and an ECS cluster for running containers. Only necessary
  for running containers on ECS.
- [ngrok][ngrok] for creating a secure HTTPS tunnel for sending signals and log
  messages back to the controller from containers running on ECS.


Setup
-----

OS X does not have native support for running containers, so Mac users should
follow the [Get started with Docker Machine and a local VM][machine] guide to
create a virtual machine to run docker containers.

Linux users will need to [install Docker Compose][compose-ubuntu] but otherwise
may already have the necessary prerequisites.


Configuration
-------------

Configuration for the development environment is mostly sourced from environment
variables exported into the shell where `docker-compose` commands will be
executed.

Not all environment variables are necessary all of the time, depending on
whether wip task containers are being run in Docker locally verses on ECS, and
whether or not you need to run the wipng integration test suite.

*Tip*: Create a file with the necessary environment variables to make it easy to
edit and source them into your shell as needed.

#### Strictly required:
```bash
export WIP_SERVICE_BASE_URL="http://$(docker-machine ip $(docker-machine active)):8080"
export DEV_DOCKER_CONTAINER_SERVICE="local"
export DEV_DOCKER_IMAGE="acquia/wip-service:MS-123-psynaptic"
```

Name | Description
-----|-----------------------------------------------------------------------
`WIP_SERVICE_BASE_URL` | This is either the HTTPS scheme version of the ngrok tunnel URL when running wip task containers on ECS, or the IP address of the Docker host and the port bound to Apache in the controller e.g. `http://192.168.1.54:8080` when running wip task containers locally. The Docker host is the IP address of the VM on Mac, and the LAN IP address on Linux.
`DEV_DOCKER_CONTAINER_SERVICE` | The type of container service being used to run wip task containers. This is either "ecs" for running containers on ECS, or "local" for using the local docker installation instead. Most of the time, this should be "local".
`DEV_DOCKER_IMAGE` | The name of the wip task container image. See [Building the container](../docker/readme.md#building-the-container) for more information.

#### Optional:
```bash
export DEV_DOCKER_WORKSPACE="/path/to/workspace"
export DEV_DOCKER_DB_PERSIST="yes"
export BUGSNAG_API_KEY="key"
export BUGSNAG_STAGE="dev"
```

Name | Description
-----|-----------------------------------------------------------------------
`DEV_DOCKER_WORKSPACE` | The absolute file system path to the wip-service workspace (repository) on your local machine. This is used to mount the workspace into the container. Any changes to the source files in the workspace on your local machine will be immediately reflected in the containers.
`DEV_DOCKER_DB_PERSIST` | By default, data in the database persists beyond restart. Set this to `no` to truncate the database in entrypoint.
`BUGSNAG_API_KEY` | The [Bugsnag][bugsnag] API key should be unique per developer.
`BUGSNAG_STAGE` | The stage for which to send Bugsnag notifications. Usually "dev".

#### To run wip task containers on ECS:
```bash
export WIP_SERVICE_NAMESPACE="$USER"
export AWS_ACCESS_KEY_ID="id"
export AWS_SECRET_ACCESS_KEY="key"
export AWS_ECS_CLUSTER="cluster"
export AWS_EC2_REGION="us-east-1"
```

Name | Description
-----|-----------------------------------------------------------------------
`WIP_SERVICE_NAMESPACE` | A value, unique to yourself, that is used to avoid namespace clashes in registered task definitions on AWS.
`AWS_ACCESS_KEY_ID` `AWS_SECRET_ACCESS_KEY` | The AWS credentials for the gardensdev account that has access to the runTask operation for ECS.
`AWS_ECS_CLUSTER` | The name of the ECS cluster that will be used for running containers, for example `fooba-ECSCl-VYA2BJVILAC8`. If you don't already have an ECS cluster assigned to you, ask someone to launch one or use someone else's.
`AWS_EC2_REGION` | The AWS region in which the ECS cluster is located. Usually `us-east-1`.

#### To run wip task containers locally:
```bash
export DEV_DOCKER_VM_NAME="$(docker-machine active)"
export DEV_DOCKER_HOST="$(docker-machine ip $DEV_DOCKER_VM_NAME)"
export DEV_DOCKER_USER="docker"
```

Name | Description
-----|-----------------------------------------------------------------------
`DEV_DOCKER_HOST` | The hostname or IP address of the Docker host (to SSH to your local machine where Docker commands may be executed).
`DEV_DOCKER_USER` | The username of the account on the Docker host (with SSH access and the ability to execute Docker commands).
`DEV_DOCKER_VM_NAME` | The name of the virtual machine created using docker-machine. If using Linux, leave as `default`.

#### To run the wipng integration test suite:
```bash
export ACQUIA_CLOUD_SITEGROUP="sitegroup"
export ACQUIA_CLOUD_ENVIRONMENT="env"
export ACQUIA_CLOUD_ENDPOINT="https://cloudapi.acquia.com/v1"
export ACQUIA_CLOUD_USER="username"
export ACQUIA_CLOUD_PASSWORD="password"
export ACQUIA_CLOUD_REALM="realm"
```

Name | Description
-----|-----------------------------------------------------------------------
`ACQUIA_CLOUD_*` | The credentials necessary for connecting the wipng integration test suite to Acquia Cloud API.


#### Sample environment config file:

```bash
eval $(docker-machine env dev)
export AWS_ACCESS_KEY_ID="AKIAJWFGDEXEWSGUI123"
export AWS_SECRET_ACCESS_KEY="sLhIPsNPrtrybKR5YIRPGiXZWJ1XmUyP29O7t123"
export AWS_ECS_CLUSTER="psyna-ECSCl-VYA2BJVIL123"
export AWS_EC2_REGION="us-east-1"
export WIP_SERVICE_BASE_URL="http://$(docker-machine ip $(docker-machine active)):8080"
#export WIP_SERVICE_BASE_URL="https://25b2e123.ngrok.io"
export WIP_SERVICE_NAMESPACE="$USER"
export DEV_DOCKER_IMAGE="acquia/wip-service:MS-359-$USER"
# For local development with latest image.
#export DEV_DOCKER_IMAGE="wip-service:latest"
export DEV_DOCKER_VM_NAME="$(docker-machine active)"
export DEV_DOCKER_HOST="$(docker-machine ip $DEV_DOCKER_VM_NAME)"
export DEV_DOCKER_USER="docker"
export DEV_DOCKER_WORKSPACE="$HOME/src/acquia/wip-service"
export DEV_DOCKER_CONTAINER_SERVICE="local"
export DEV_DOCKER_SYSTEM="docker-machine"
export BUGSNAG_API_KEY="29c592b359cb27c8b334dd6182724123"
export BUGSNAG_STAGE="dev"
```

To switch to running containers on ECS instead of via local Docker, simply
uncomment the ngrok version of `WIP_SERVICE_BASE_URL` and set
`DEV_DOCKER_CONTAINER_SERVICE` to `ecs`. Reverse the procedure to switch back.

After you've made changes to the environment config file, don't forget to source
it into your shell before executing the `docker-compose build` and
`docker-compose up` commands.


Running task containers locally
-------------------------------

1. [Mac only] Source the `docker-machine` environment variables:
     ```
     eval "$(docker-machine env $DEV_DOCKER_VM_NAME)"
     ```

   *Tip*: You can find the value of `vm-name` using `docker-machine ls`.

2. Build the wip task container. Note if you want the latest container you do not need to pass in a tag:
     ```
     ./docker/build.py acquia/wip-service:MS-123-psynaptic
     ```
     or
     ```
     ./docker/build.py
     ```

3. Review the [Configuration](#configuration) section and set the appropriate
   environment variables.

4. Build and start the controller:
     ```
     docker-compose -f dev-docker/docker-compose.yml build
     docker-compose -f dev-docker/docker-compose.yml up
     ```

5. Add the dev user's public key to the authorized_keys file on your local
   machine:
     ```
     # On Mac
     docker-machine ssh $DEV_DOCKER_VM_NAME "docker exec devdocker_web_1 cat /home/dev/.ssh/id_rsa.pub >> ~/.ssh/authorized_keys"
     # On Linux
     (docker exec -it devdocker_web_1 cat /home/dev/.ssh/id_rsa.pub) >> ~/.ssh/authorized_keys
     ```

   This is necessary to allow the controller to SSH into the host to execute
   Docker commands (this may need to be repeated whenever you run
   `docker-compose build` as a new keypair could potentially be generated as
   part of the build).

   *Tip*: On Linux your local machine needs to be able to accept incoming SSH connections
   to be able to run containers locally, make sure sshd is running.

6. If you have not done so already checkout out the site you have created on [Acquia][acquia_account]. And cd into the
   directory.

7. Create an .acquia.yml or .acquia.yaml file in the root of the directory. The minimal contents for
   this file is:
   ```
   build-steps: {}
   ```

   To validate the file run
   ```
   buildsteps lint
   ```

   If the yaml is confirmed as valid, commit the file and push to your repo if necessary.

   *Tip*: Additional commands such as drush make can be added but the above will create a successful
   build. See [Gardens distro][gardens-distro] and [DG-15529][gardens-distro-update] for a make example.

8. Log into the Wip Client and execute a build.:
     ```
     buildsteps login
     buildsteps build
     ```

   *Tip*: Use the URL from step 1 as the "Buildsteps endpoint". See the [Wip
   Client readme][wip-client-readme] for more information on using the Wip Client
   commands. The cloud api credentials are your email address and private key. The
   buildsteps credentials can be found in [config.security.yml][wip-service-config], use
   the ADMIN_ROLE settings.

9. Log into the controller container and check the logs:
     ```
     docker exec -it devdocker_web_1 /bin/bash
     wip log
     ```

   *Tip*: If a build fails you will be able to see this in the log. If you are logged in as an admin user you may not be
   able to see the job status using buildsteps status because there are too many log entries.

10. Check the status of the build using the buildsteps client:
     ```
     buildsteps status
     ```


Running task containers on ECS
------------------------------

1. [Mac only] Source the `docker-machine` environment variables:
     ```
     eval "$(docker-machine env $DEV_DOCKER_VM_NAME)"
     ```

   *Tip*: You can find the value of `vm-name` using `docker-machine ls`.

2. Create an HTTP tunnel using the IP address of the docker host on port 8080:
     ```
     ngrok http $(docker-machine ip $DEV_DOCKER_VM_NAME):8080
     ```

   *Note*: At this point, accessing the ngrok tunnel will fail as the container
   has not yet been started and nothing should be listening on port 8080. The URL
   needs to be set as an environment variable, which is read when executing the
   docker-compose commands so this needs to be set up before that happens.

   *Tip*: You can inspect traffic going through the tunnel at:
   [http://localhost:4040/](http://localhost:4040/)

3. Build the wip task container and push to Docker Hub:
     ```
     ./docker/build.py acquia/wip-service:MS-[ticket_no]-[user] --push
     ```

4. Review the [Configuration](#configuration) section and set the appropriate
   environment variables.

5. Build and start the controller:
     ```
     docker-compose -f dev-docker/docker-compose.yml build
     docker-compose -f dev-docker/docker-compose.yml up
     ```

6. If you have not done so already checkout out the site you have created on [Acquia][acquia_account]. And cd into the
   directory.

7. Create an .acquia.yml or .acquia.yaml file in the root of the directory. The minimal contents for
   this file is:
   ```
   build-steps: {}
   ```

   To validate the file run
   ```
   buildsteps lint
   ```

   If the yaml is confirmed as valid, commit the file and push to your repo if necessary.

   *Tip*: Additional commands such as drush make can be added but the above will create a successful
   build. See [Gardens distro][gardens-distro] and [DG-15529][gardens-distro-update] for a make example.

8. Log into the Wip Client and execute a build.:
     ```
     buildsteps login
     buildsteps build
     ```

   *Tip*: Use the IP address and port version of the URL from step 1 as the
   "Buildsteps endpoint". See the [Wip Client readme][wip-client-readme] for
   more information on using the Wip Client commands. The cloud api credentials are your
   email address and private key. The buildsteps credentials can be found
   in [config.security.yml][wip-service-config], use the ADMIN_ROLE settings.

9. Log in to the local controller and check the logs:
     ```
     docker exec -it devdocker_web_1 /bin/bash
     wip log
     ```

   *Tip*: If a build fails you will be able to see this in the log. If you are logged in as an admin user you may not be
   able to see the job status using buildsteps status because there are too many log entries.

10. Check the status of the build using the buildsteps client:
     ```
     buildsteps status [site-name]
     ```

Updating the database schema
----------------------------

To ensure test runs are isolated from the running application, there are two
databases for the Wip Service controller application:

- silex: The main database for the runtime application.
- silex_test: The database for running the unit tests.

To update the scheme of the main "silex" database:

```
php vendor/bin/doctrine orm:schema-tool:update --dump-sql --force
```

By default, all commands issued in terminal will connect to the main "silex"
database. To use the "silex_test" database instead, prepend the
`WIP_SERVICE_DATABASE` environment variable to the command.

To update the schema of the "silex_test" database:

```
WIP_SERVICE_DATABASE=silex_test php vendor/bin/doctrine orm:schema-tool:update --dump-sql --force
```

Likewise, to log into the mysql console via the `wip sqlc` command:

```
WIP_SERVICE_DATABASE=silex_test wip sqlc
```

The schema for the "silex_test" database is automatically updated by the test
bootstrap script when [running the tests](#running-the-tests).


Running the tests
-----------------

1. Review the [Configuration](#configuration) section and set the appropriate
   environment variables before building and starting the controller container.

2. Open a terminal session in the controller container:
     ```
     docker exec -it devdocker_web_1 /bin/bash
     ```

3. Run the wip-service tests:
     ```
     vendor/bin/phpunit --exclude-group=excluded
     ```

   *Tip*: It can be beneficial to execute the unit tests in the background as
   you continue to execute tasks. Because the tests use a different database,
   they are isolated from the main application.

4. Run the wipng tests:
     ```
     cd vendor/acquia/wipng
     vendor/bin/phpunit
     ```

   *Tip*: Don't forget to `composer install` the wipng dependencies on your
   local machine.

The database must be empty when running the tests and it may at times be
required to manually clear the test database:

```
mysql -uadmin -ppassword -hdb silex_test < scratch/CLEAR_DB.sql
```

Using xdebug
------------

The Apache container comes with the [xdebug][xdebug] extension installed and
configured for remote debugging.

#### Enable debugging in PHPStorm

1. Add a new *Run/Debug Configuration* for *PHP Remote Debug* with the following
   parameters:
     ```
     Name: Dev Docker
     Server: dev-docker
     Ide key: phpstorm
     ```

2. Add a remote server with the following parameters:
     ```
     Name: dev-docker
     Host: 192.168.99.100 # The value of `docker-machine ip $DEV_DOCKER_VM_NAME`.
     Port: 8080
     Path mapping: /path/to/wip-service /var/www/html
     ```

#### Start the debugger

1. Select the *Run/Debug Configuration* profile in PHPStorm.

2. Add a breakpoint where you want execution to pause by clicking the gutter.

3. Click the bug icon (Debug Dev Docker).

4. Execute PHP with the `XDEBUG_CONFIG` environment variable set, for example:
     ```
     export XDEBUG_CONFIG="idekey=phpstorm"; php scratch/encryption.php test1234
     ```

#### Enable Profiling of API requests

1. In the root of wip-service create profiler_output folder and chmod 777 it so
that docker containers can write to it.

2. Set env variable DEV_DOCKER_PROFILE_PHP=yes either by 
```export DEV_DOCKER_PROFILE_PHP=yes``` or setting it in your env config.

3. Restart docker-compose stack so that PHP config gets written.

4. cachegrind files will be written into profiler_output from where you can use
[kcachegrind], [qcachegrind] or [phpstorm] to analyze the results.

[compose]: https://docs.docker.com/compose/
[toolbox]: https://www.docker.com/toolbox
[ngrok]: https://ngrok.com/
[wip-client]: https://github.com/acquia/wip-client
[machine]: https://docs.docker.com/machine/get-started/
[wip-client-readme]: https://github.com/acquia/wip-client/blob/master/README.md#getting-started
[invoking-buildsteps]: https://confluence.acquia.com/display/MS/Invoke+Build+Steps
[phar]: https://confluence.acquia.com/display/MS/Invoke+Build+Steps#InvokeBuildSteps-Getthebuildstepsclient(buildsteps.pharmethod)
[bugsnag]: https://bugsnag.com/
[compose-ubuntu]: https://docs.docker.com/installation/ubuntulinux/
[xdebug]: http://xdebug.org/docs/remote
[qcachegrind]: http://brewformulas.org/Qcachegrind
[kcachegrind]: https://kcachegrind.github.io/html/Home.html
[phpstorm]: https://www.jetbrains.com/phpstorm/help/analyzing-xdebug-profiling-data.html
[acquia_account]: https://accounts.acquia.com
[wip-service-config]: https://github.com/acquia/wip-service/blob/master/config/config.security.yml
[gardens-distro]: https://github.com/acquia/gardens_distro/tree/buildsteps
[gardens-distro-update]: https://backlog.acquia.com/browse/DG-15529
