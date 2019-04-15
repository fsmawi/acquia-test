# ECS and Wip

Build container launching and teardown is done using the [ContainerInterface], which has an implementation named [EcsContainer]. This interface has a **run()** method that actually starts the container. Generally the container
 will be configured first with overrides using the **addContainerOverride()** method before the container is launched.

Having your Wip task subclass the [ContainerWip] class makes it easy to add container behavior to your task. This class takes care of container launching and cleanup and contains a method called **checkContainerResultStatus()** that wraps the more generic **[BasicWip]::checkResultStatus()**, adding new transition values:

*terminated* - returned if the container was terminated. This transition value is handled on virtually every SSH call into the container because the container could die or be terminated at any point.

*no_information* - returned if it was not possible to get the container information.

The [ContainerWip] class exposes a **getContainerStartTable()** method that provides logic responsible for starting a container as well as a **getContainerStopTable()** method that provides similar logic for shutting the container down. The intention of these two methods is that this logic would not be duplicated in each subclass, but rather the logic would be stitched into the child Wip task's state table. This is done by passing in the name of the state that should be used if starting the container is successful and the name of the state used if starting the container failed. The [BuildSteps] Wip task presents a solid use case and example of stitching the container logic into the state table.

## ECS configuration
The configuration of ECS can be found in a few different places.

1. The [config/config.ecs.yml] file contains the default container values.
2. The **/mnt/files/site.env/nobackup/config/config.ecs.yml** can be used to override those values. Typically this override is used to specify a particular image version and the CPU shares that indirectly limit the number of containers available on each ECS server.
3. The **ecs_cluster' table** in the wip-service database stores the region, cluster, and access credentials required to launch a container.

This configuration data together form a container task definition, which is stored in the **task_definition** table in the wip-service database. To create the task definition, the [EcsClusterStore] class loads the relevant data from the database; the non-database configuration is constructed using a file in our workspace with a deployment-specific configuration yaml file from gluster. The author isn't sure where the code that does this yaml combination is located; if found, please link it here. File overrides are found in [app.php].

## Container interactions with Wip
Once launched, the **entrypoint.sh** script is executed, and the container continues to run until this script exits. This script is responsible for exiting the container if any of our resource limits have been exceeded. We don't need to worry about CPU usage because that is controlled within ECS based on the task definition.

The **entrypoint.sh** periodically checks disk usage and the container uptime. The determination of what resource limits exist and whether the usage has exceeded our resource limits is facilitated by the **/etc/buildcontainer/config.ini** file. This file identifies the maximum disk space the customer's workload can take along with the initial disk size consumed by the container itself. This allows us to accurately calculate the space used by the customer.

After the ssh server has been started in the container, the **entrypoint.sh** script sends a signal to the associated Wip task that indicates the container is ready. Upon receiving this signal, the Wip task will verify the container is ready by attempting an ssh connection. If that connection fails, it will be retried after a bit of time elapses. Sometimes this is necessary because there is a delay between starting the server and the server being able to accept the first connection. Once this connection is established, the task moves into the actual build process.

Within the container, the container can be shut down by touching the **/tmp/shutdownContainer** file. The **entrypoint.sh** script will detect this and shut the container down, generally within 10 seconds.

Upon exit, the **entrypoint.sh** script sends a signal to the associated Wip task to indicate the container has gone away. This signal will include resource usage data.

If the container was in the middle of processing a build when the container was terminated (for example, if the maximum disk space allotment was exceeded), this container termination signal will be preceded by a signal that sends the associated Wip task the stdout and stderr of the process that was being executed at the time the container was terminated. The [BuildSteps] Wip task will use this information to log what was happening when the container was terminated.

## Container overrides
Pipelines uses container overrides to inject job-specific details and settings into the container. In the **BuildSteps** task you can see this in the **addContainerOverrides** method, in which the **Segment** user and application ID are set.

Also in **[AbstractPipelineWip]::addContainerOverrides** you can see how various Pipelines environment variables are being injected into the container.

[ContainerInterface]: https://github.com/acquia/wip-service/blob/master/src/Acquia/Wip/Container/ContainerInterface.php
[EcsContainer]: https://github.com/acquia/wip-service/blob/master/src/Acquia/WipIntegrations/Container/EcsContainer.php
[ContainerWip]: https://github.com/acquia/wip-service/blob/master/src/Acquia/Wip/Implementation/ContainerWip.php
[BuildSteps]: https://github.com/acquia/wip-service/blob/master/src/Acquia/Wip/Modules/NativeModule/BuildSteps.php
[config/config.ecs.yml]: https://github.com/acquia/wip-service/blob/master/config/config.ecs.yml
[AbstractPipelineWip]: https://github.com/acquia/wip-service/blob/master/src/Acquia/Wip/Objects/BuildSteps/AbstractPipelineWip.php
[BasicWip]: https://github.com/acquia/wip-service/blob/master/src/Acquia/Wip/Implementation/BasicWip.php
[app.php]: https://github.com/acquia/wip-service/blob/master/app/app.php