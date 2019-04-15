Wip Service
===========

[![Build Status](https://magnum.travis-ci.com/acquia/wip-service.svg?token=HEW4pemTKysUMywAZEr4&branch=master)](https://magnum.travis-ci.com/acquia/wip-service)
[![Coverage Status](https://coveralls.io/repos/acquia/wip-service/badge.svg?branch=master&service=github&t=0kiFEb)](https://coveralls.io/github/acquia/wip-service?branch=master)

A standalone service comprised of a [Silex][silex] application with a REST API
frontend and command line user interface.

Wip is a prioritized, failure resilient, asynchronous task management system.

Requirements
------------

The list of requirements for wip-service and [wipng][wipng] are numerous:

- php55+ with the curl, gmp, mysql and sqlite extensions
- composer
- apache
- mysql or equivalent
- an ssh server and client
- gnu stat and md5sum
- graphviz
- permission to write to /tmp, /mnt/tmp, and $HOME/.ssh (and for them to already
  exist)

For this reason, it is highly recommended to use the [Docker Development
Environment](dev-docker) instead of trying to install the application on your
local machine.

Configuration
-------------

Configuration of the service is non-trivial.

- `ACQUIA_CLOUD_*` environment variables containing credentials for Cloud API
  for running the wipng unit test suite.
- `AH_SITE_GROUP` and `AH_SITE_ENVIRONMENT` environment variables.
- An ecs_cluster record in the database for running tasks on ECS.
- A server_store record in the database for threads.
- An open HTTP port with apache listening for incoming connections so that
  signals and logs can be received from containers running on ECS.
- A crontab entry for automatically restarting the Wip daemon processes.
- An encryption SSH keypair (preferably RSA 4096) needs to be added to /mnt/files/{site}.{env}/nobackup/buildsteps.key.pub and /mnt/files/{site}.{env}/nobackup/buildsteps.key.pub

Updating the database schema
----------------------------

```
php vendor/bin/doctrine orm:schema-tool:update --dump-sql --force
```

Running the tests
-----------------

The test suites have numerous external dependencies so running them via the
[Docker Development Environment](dev-docker) is highly recommended.

#### Wip Service
```
composer install
vendor/bin/phpunit --exclude-group=excluded
```

#### Wip NG
```
cd vendor/acquia/wipng
composer install
vendor/bin/phpunit
```

[silex]: http://silex.sensiolabs.org/
[wipng]: https://github.com/acquia/wipng


Detecting changes in Wip objects
--------------------------------

The bin/wipversion tool can be used to detect changes in Wip objects. It will help warn the developer when a Wip update
function may be needed due to code changes. There are four commands in the tool, which are explained below.

When working on Wip objects, you should run the diff command to ensure that you have handled any necessary update
function and version changes. Jenkins will run the check-all command on all RC jobs to ensure that nothing is amiss.
Finally, we will merge the updated fingerprint and details files into master on release.

#### version
  The version command calculates version information for a Wip class based on its state table and class variables. It
requires the fully-qualified class name of a Wip object as an argument, and outputs the class signature details. It can
save the details inside the wipversions/details directory with the "--save" option. Note that you should NOT use the
save option unless you have ensured that all necessary update methods and version changes have been made in the Wip
object.

#### hash
  The hash command generates a hash value ("fingerprint") based on the signature details of a given class. Like the
"version" command, the hash command has a "--save" option that can be used to save the value in the
wipversions/fingerprints directory. The "--save" option should also NOT be used unless you have ensured that all
necessary update methods and version changes have been made in the Wip object.

#### diff
  The diff command checks if the fingerprint for a Wip class has changed. The original fingerprints and details, to
which the current ones will be compared, are found in the wipversions directory. If the fingerprint values differ, a
diff will be perfomred on the details. If a Wip class was added and does not have details and fingerprints in the
wipversions directory, the command will treat it as a mismatch.

#### check-all
  The check-all command will check the details and fingerprints of all the Wip classes given in a file. The input file's
location defaults to wipversions/all_wips.txt. The output will be saved in a file, which defaults to
wipversions/all_wips.output. If at the end of the run, none of the diff commands reported a change, the command will
exit successfully. If any changes were detected, the relevant information will both be printed on the console and saved
in the output file and the command will exit with a failure. If no classes were provided in the input file, the command
will exit with a failure.

#### update procedure
  After updating a wip state table, you will need to update the detail and fingerprint files.
```bash
$ # Look for changes:
$ bin/wipversion check-all
$ # Review the changes to the fingerprint:
$ bin/wipversion hash "Acquia\Wip\Modules\NativeModule\ContainerCanary"
$ # Save the changes to the fingerprint:
$ bin/wipversion hash "Acquia\Wip\Modules\NativeModule\ContainerCanary" --save
$ # Review the changes to the detail file:
$ bin/wipversion detail "Acquia\Wip\Modules\NativeModule\ContainerCanary"
$ # Save the changes to the detail file:
$ bin/wipversion detail "Acquia\Wip\Modules\NativeModule\ContainerCanary" --save
$ # Double check the changes:
$ bin/wipversion check-all
$ # Review all changes:
$ git diff
```

  If your changes require a change in the state table that could affect wip objects in-flight you should implement an
update hook.

1. Increment the wip objects CLASS_VERSION property.
2. Implement a method in the wip object to handle the update. Note that the CLASS_VERSION is used as a naming convention:

  ```public function updateBuildStepsNg4(WipUpdateCoordinatorInterface $coordinator) ```

Setting up Wip on stage
-----------------------

#### Running the Jenkins job
Every time there is a major change to the Acquia Hosting infrastructure, we launch our own stage for testing. This is
shared between the Site Factory team as well as the BuildSteps team, so some of the language is geared toward Site Factory.
This is where you can run wip-service and host a sandbox site to test BuildSteps.
Run the [jenkins_job][jenkins job] that creates your stage sites. This creates three AcquiaHosting sites: "gardener",
"gardens" and "wip". The best way to do this is to find a job that has already been run for the current sprint and rebuild
that but change the customer name to your own.

It will take around 20 minutes to run. When the task is complete if you scroll to the end of the console log you will see the wip information for
the given build. The elements of interest are

```
{
  "gardens": {
    "site": "mygardens",
    "password": "mypassword"
  },
  "wip": {
    "site": "mywip",
    "password": "mypassword"
  },
  "git": "mysvn",
  "clown": "https://myclown/v1"
}
```

The "wip" site is what will run your fully built wip-service code.

[jenkins_job]: https://ci.acquia.com/view/Site%20Factory/job/sf-add-garden/

##### Adding SSH keys
You need to add your public ssh keys to each site on the sprint stage via the stage's cloud api (this is not accessible
via the production cloudapi). The endpoint is the "clown" entry in the JSON e.g https://myclown/v1.

- Add to the wip service
```
$ curl -sk -u mywip:mypassword -X POST --data-binary '{"ssh_pub_key":"my-ssh-key"}' https://myclown/v1/sites/mywip/sshkeys.json?nickname=mynickname
```

- Add to gardens (you can use this as your build site via buildsteps).
```
$ curl -sk -u mywip:mypassword -X POST --data-binary '{"ssh_pub_key":"my-ssh-key"}' https://myclown/v1/sites/mygardens/sshkeys.json?nickname=mynickname
```

#### Adding git remotes
You need to add remotes to your local repos and ensure you have push access to these remotes (again copy and paste "with
your brain on").
- Add "mywip@mysvn:mywip.git" to your wip-service release repo.
- Add wipservice@svn-182.network.hosting.acquia.com:wipservice.git to your wip-service release repo, this is where
builds can be found.
- Add "mygardens@mysvn:mygardens.git" to your buildsteps repo.

By default code should be pushed to the master branch of the mywip@mysvn:mywip.git repo. However you can change the
deployment branch by running:
```
curl -sku mywip:mypassword -X POST https://myclown/v1/sites/mywip/envs/prod/code-deploy.json?path=branch-to-deploy
```

The `scripts/build_deployment.sh` script helps to create the deployable version of the repo by adding the vendor dir and making some necessary code hacks. It's based on some steps of https://confluence.acquia.com/pages/viewpage.action?spaceKey=MS&title=Set+up+a+development+environment

#### Check SSH access
SSH to the sprint stage servers to ensure shell access. Typically ded-15 and ded-16 are the sprint stage servers. If
these hosts are unavailable, use the Cloud API to find the correct ones. The following commands can be run to check
access:
```
$ ssh -i ~/.ssh/your_pub_key -F /dev/null -p22 mygardens.prod@ded-15.[sprint-no].srvs.ahdev.co
$ ssh -i ~/.ssh/your_pub_key -F /dev/null -p22 mywip.prod@ded-15.[sprint-no].srvs.ahdev.co
```

#### Checking database tables
Once you have sshed to the stage run:
```
$ wip sqlc
```

Once the mysql shell appears run:
```
SELECT * FROM server_store;
SELECT * FROM ecs_cluster;
```

If either table is empty it needs to be populated with data to ensure wip has the necessary meta date to run.

To populate the server_store table run:
```
INSERT IGNORE INTO server_store (hostname, total_threads, status) VALUES ('ded-15.[sprint-no].srvs.ahdev.co', 3, 1);
INSERT IGNORE INTO server_store (hostname, total_threads, status) VALUES ('ded-16.[sprint-no].srvs.ahdev.co', 3, 1);
```

To populate the ecs_cluster table run:
```
INSERT IGNORE INTO ecs_cluster(name, aws_access_key_id, aws_secret_access_key, region, cluster) VALUES ("default","AKIAJWFGDEXEWSGUIMDA","sLhIPsNPrtrybKR5YIRPGiXZWJ1XmUyP29O7tYu8", "us-east-1", "buildsteps-dev-3-ECSCluster-1G2JU10W33BSJ");
INSERT IGNORE INTO ecs_cluster(name, aws_access_key_id, aws_secret_access_key, region, cluster) VALUES ("default","AKIAJWFGDEXEWSGUIMDA","sLhIPsNPrtrybKR5YIRPGiXZWJ1XmUyP29O7tYu8", "us-east-1", "buildsteps-dev-3-ECSCluster-1G2JU10W33BSJ")
```

#### Checking the wip daemon
Make sure you are sshed onto a wip box on stage and then run:
```
$ wipctl monitor status
```

If this command doesn't indicate that a wip daemon is present and currently running, run:
```
$ wipctl monitor start
```

This should inform you that the wip daemon is running and provide the pid that is running it.

#### Setting up the pipeline api
You need to provide information to pipeline api about which wip to use. This can be achieved in one of 2 ways, either via
credentials or updating the configuration in your own pipeline.

The credentials file lives at ~/.acquia/pipelines/credentials and the buildsteps_endpoint entry needs to be set to
http://[mywip]prod.[sprint-no].sites.ahdev.co/. In addition buildsteps_user and buildsteps_pass need to be set appropriately.

If you are using your own pipeline then update the entry for BuildStepsEndpoint to
http://[mywip]prod.[sprint-no].sites.ahdev.co/ and update your pipeline.

### Swapping ecs cluster configuration
If you need to swap the configuration for an ecs cluster you can do so via the cli.
SSH to the server you want to use. Then if new configuration is required run:
```
wipctl save-ecs-cluster <name> <cluster> <key> <secret> --region=clusterRegion
```

Region is optional and defaults to us-east-1. If the cluster you want to swap to already
exists there is no need to do this step. This can also be used to update any existing
configuration.

Once the configuration you need has been set you can swap to the configuration you want to
use by running:
```
wipctl set-active-cluster --name=myConfig
```

If you want to swap back to the default configuration simply run the command above without
the --name option.

If you need to delete a configuration for ever, run:
```
wipctl delete-ecs-cluster <name>
```

Note neither the configuration called default or the active configuration can be deleted.

Monitoring ThreadPool Health
----------------------------
ThreadPool status can provide insight into the overall health of wip-service. For example, if processing threads spend
too little time sleeping, the system is likely to be overloaded with work.

To be able to easily track this data, we have two classes, ThreadPoolProcessDetail and ThreadPoolIterationDetail, that
log data every time ThreadPool's process() and useThreads() functions are called. Each ThreadPoolProcessDetail object
tracks an execution of process(), corresponding to a thread being spun up to process data. They may contain one or
more ThreadPoolIterationDetail objects, each corresponding to an iteration of useThreads() where the system attempts
to pair up free threads and tasks that need to be executed.

By default, only percent of time in execution, total time in execution, and average threads per iteration are sent to
[the Grafana dashboard] [grafana] and nothing is logged in the wip log. To get more detailed data, you can turn on
logging, and optionally verbose mode, in the factory override file appropriate for your environment. You can then get
more detailed data in the wip_log table of the database.

[grafana]: https://grafana.ops.acquia.com/dashboard/db/buildsteps
