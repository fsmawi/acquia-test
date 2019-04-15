Service updates that affect the Wip engine can cause failures as the code is switched from one version to the next. The safest way to perform an update of this type is to shut down all task processing and restart the daemon processes on every webnode.

# Update Sequence

## The following sequence ensure the daemon procesess are restarted during update.
1. Stop the monitor daemon on all webnodes by setting monitor daemon pause via the state REST API call.
..* The `wipctl monitor-daemon start` command is called once per minute via cron. When a new `wipctl run-daemon` process launches, it would look for this state entry and exit immediately before attempting to acquire a database lock.
..* When the run-daemon is matching threads to tasks for the dispatch, a check for this entry would be performed before the dispatch is executed.
..* New work can be added, but it will not be started.
1. Wait for all Wip task processing to complete before continuing. This is done using the TaskCollection REST endpoint that allows for counting tasks by run status.
1. Update the code.
1. Update the database.
1. Allow the monitor daemon processes to start again via cron by removing the monitor daemon pause via the state REST API endpoint.

## Design considerations
* All run-daemon processes must be stopped on all webnodes, but it's better to not have to know exactly which webnodes.
..* The Cloud API could be used to figure out which webnodes are part of the current configuration, but we are intentionally moving away from that API.
..* The servers could be retrieved from the server_store table, but that table is not updated when webnodes are shut down; only when they are added.
* The `wipctl monitor-daemon` process is started once per minute and is responsible for starting the `run-daemon` processes.
..* We do not have an easy way to programmatically disable and enable cron jobs.
* Any task that is currently in the RUNNING status should be allowed to continue because a code or database schema change could cause it to fail.

## Summary
In short, to safely update the wip-service code, we must shut off all of the processes and completely disable them during the update process. By using the database to simultaneously inform each process that new work should not be dispatched makes the coordination significantly easier, with less delay. Leveraging the existing cron configuration guarantees that task processing will resume within 1 minute of the completion of the update.
