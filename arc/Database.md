There are quite a few tables involved in proper operation - I'll talk about each of the important ones starting with the problem of the system not picking up new work.

# `server_store`
This table contains information about the webnodes available for Wip to do its work. Here is the contents of that table on my stage environment:

```
mysql> select * from server_store;
+----+--------------------------------+---------------+--------+
| id | hostname                       | total_threads | status |
+----+--------------------------------+---------------+--------+
|  1 | ded-15.sprint147.srvs.ahdev.co |            20 |      1 |
|  6 | ded-16.sprint147.srvs.ahdev.co |            20 |      1 |
+----+--------------------------------+---------------+--------+
```

Ensure that there are threads on each server and that the status is '1' for each. The status indicates the server is available to perform work. In some cases we may wish to dedicate a particular server for only accepting web requests, and would set its status to 0. The total number of threads here represents the total number of Wip tasks that can be processing at any given time. The system will take a task that is ready for processing and pair that with an available thread and dispatch it (using `wipctl exec --id[wip ID] --thread-id[thread ID]`). The `wipctl exec` is responsible for moving the task forward, not for executing the entire task. So with 40 threads shown here we may be able to have 80 tasks in process at once, but only execute up to 40 at a time. Due to the asynchronous nature of the work typically done by tasks, sharing threads this way works nicely.

# `thread_store`
This table is responsible for holding thread metadata for each `wipctl exec` command. 

Here is the schema:

```
mysql> describe thread_store;
+------------+------------------+------+-----+---------+----------------+
| Field      | Type             | Null | Key | Default | Extra          |
+------------+------------------+------+-----+---------+----------------+
| id         | int(10) unsigned | NO   | PRI | NULL    | auto_increment |
| server_id  | int(10) unsigned | NO   | MUL | NULL    |                |
| wid        | int(10) unsigned | NO   | MUL | NULL    |                |
| pid        | int(10) unsigned | NO   |     | NULL    |                |
| created    | int(10) unsigned | NO   |     | NULL    |                |
| completed  | int(10) unsigned | NO   |     | NULL    |                |
| status     | int(10) unsigned | NO   | MUL | NULL    |                |
| ssh_output | longtext         | NO   |     | NULL    |                |
| process    | longtext         | NO   |     | NULL    |                |
+------------+------------------+------+-----+---------+----------------+
```

If the system is running you will see entries in this table that have a 'status' field value 2. '2' indicates the thread is currently running. There are other values which you shouldn't typically see:

| Status        | Meaning |
| :------------- | :------------- |
| 1 - RESERVED. | This value is used temporarily as the thread is being established, and quickly moves to status 2. |
| 2 - RUNNING. | This is typically the value you will see in the thread_store table, and indicates a task is being executed. As you can see from the schema, you can easily determine which task is being processed with this thread and which server the process is running on as well as the process pid. |
| 3 - FINISHED. | This state is used when the process has completed. Generally the thread is removed instead since there are many such threads that are used to complete a typical Wip task and the thread metadata has no enduring value beyond the process execution. If the process ended abnormally we might instead move its status to 3, so if you see a bunch of thread_store entries with status = 3 the system is probably unhealthy. |

To find all of the threads associated with running Wip tasks and their related Pipelines job IDs you can run the following query:
```sql
select ts.wid, wp.client_job_id from thread_store ts left join wip_pool wp on ts.wid = wp.wid where ts.status = 2;
```
Running this query on prod just now I got the following:

```
mysql> select ts.wid, wp.client_job_id from thread_store ts left join wip_pool wp on ts.wid = wp.wid where ts.status = 2;
+---------+--------------------------------------+
| wid     | client_job_id                        |
+---------+--------------------------------------+
| 1060091 | 65c83150-2bc6-4048-a59a-7d1ca660b455 |
+---------+--------------------------------------+
```

# `wip_group_max_concurrency`
This table is used to set the maximum number of tasks for any particular group. Pipelines currently uses only the `BuildSteps` group. The `BuildSteps` tasks all use the same container definition and ecs machines. That means the number of BuildSteps tasks cannot exceed the maximum number of containers available. You will see on production this number has been set to the maximum number of containers we can run simultaneously:

```
+------------+-----------+
| group_name | max_count |
+------------+-----------+
| BuildSteps |        36 |
+------------+-----------+
1 row in set (0.00 sec)
```

If a particular group has no entry in this table, it uses the default which is 3, but can be overridden in the configuration table setting the `wip_group_max_concurrency` property's value.

# `wip_group_concurrency`
This table is used to keep track of the number of tasks in progress of any particular type at a given time. No entries in this table would indicate no tasks are currently being processed. Note that this would be a superset of the items in the `thread_store` table because not all tasks currently being worked on will have an active thread. Much of the time asynchronous operations are being performed. The value for a particular group cannot exceed the value in the `wip_group_max_concurrency` table for the same group. The contents of this table change constantly depending on whether the system is idle (in which case there will be on entries) or the system is currently executing tasks (in which case there should be an entry here for each task). Note that this table can be full and yet the system isn't processing tasks if the system is paused. Here I show the contents of this table on prod when the system is under minimal load:

```
+---------+------------+
| wid     | group_name |
+---------+------------+
| 1060151 | BuildSteps |
| 1060156 | BuildSteps |
| 1060161 | BuildSteps |
| 1060166 | BuildSteps |
+---------+------------+
4 rows in set (0.01 sec)
```

# `wip_pool`
This table stores the task metadata for every task. This is where the task status is maintained. The task status is expressed as 2 independent yet related fields: `run_status` and `exit_status`. 

## `run_status`
The `run_status` field indicates whether the task is currently running, and has the following possible values:

| Status        | Meaning |
| :------------- | :------------- |
| 99 - NOT_READY. | It is rare to see a task with a status of 99 because this value is used only when creating a task to prevent the task from being picked up before its initialization is complete. |
| 0 - NOT_STARTED. | This is the status when the task has been added to the system but not started. It may not be started because the system is paused or busy. If the `wip_group_max_concurrency` has been met, no new work for that particular group will be started. Also if there are insufficient threads to process work, these items will stay in the `NOT_STARTED` state. Finally, if there is already a task with the same `work_id` value in progress, other tasks with matching `work_id` fields will run in serial. |
| 1 - WAITING. | This status is used when the task has been started but is not currently being executed. That generally means there is an asynchronous call for which the task is waiting. |
| 2 - PROCESSING. | This status is used when the task is currently being processed on a particular webnode. For every task currently in the `PROCESSING` state, there should be a thread in the `thread_store` table with a status of `RUNNING` (2). If there is no such thread, that means there is trouble. |
| 3 - COMPLETE. | This status indicates the associated task has completed its processing. It does not reveal how the task completed - that is the job of the `exit_status` field. |
| 4 - RESTARTED. | This status indicates the task is not currently running, and has been flagged to be restarted. We aren't currently restarting tasks, so you shouldn't see any tasks with this run status. |

## `exit_status`
This field indicates the exit status of the task. This is how you can figure out if the task finished successfully, with a warning, a system error or a user error. It uses the following values:

| Status        | Meaning |
| :------------- | :------------- |
| 0 - NOT_FINISHED. | This is the exit status for all tasks that do not have a `run_status` value of 3 (`COMPLETE`). |
| 1 - WARNING. | This is the exit status for a task that completed successfully but has a non-fatal warning that needs to be communicated to the user. We aren't using this with our existing Wip tasks, so you shouldn't see this. |
| 2 - ERROR_SYSTEM. | This is the exit status for a task that failed due to a system error. That essentially means that our system failed to complete the task for whatever reason. |
| 3 - TERMINATED. | This is the exit status for a task that finished through task termination. |
| 4 - COMPLETED. | This is the exit status of a task that completed successfully. This is the value we are looking for. |
| 5 - ERROR_USER. | This is the exit status of a task that failed due to some problem the user has control over. For example if the user asks us to build a branch that they forgot to push to the source repository the task will fail due to the user. |

## Other fields
The wip_pool table has the following fields:

```
mysql> describe wip_pool;
+----------------+------------------+------+-----+---------+----------------+
| Field          | Type             | Null | Key | Default | Extra          |
+----------------+------------------+------+-----+---------+----------------+
| wid            | int(10) unsigned | NO   | PRI | NULL    | auto_increment |
| work_id        | varchar(255)     | NO   | MUL | NULL    |                |
| parent         | int(10) unsigned | NO   | MUL | NULL    |                |
| name           | varchar(255)     | NO   |     | NULL    |                |
| group_name     | varchar(255)     | NO   |     | NULL    |                |
| priority       | int(10) unsigned | NO   |     | NULL    |                |
| run_status     | int(10) unsigned | NO   | MUL | NULL    |                |
| exit_status    | int(10) unsigned | NO   |     | NULL    |                |
| is_terminating | int(10) unsigned | NO   | MUL | NULL    |                |
| wake_time      | int(10) unsigned | NO   |     | NULL    |                |
| created        | int(10) unsigned | NO   | MUL | NULL    |                |
| start_time     | int(10) unsigned | NO   |     | NULL    |                |
| completed      | int(10) unsigned | NO   |     | NULL    |                |
| claim_time     | int(10) unsigned | NO   |     | NULL    |                |
| lease          | int(10) unsigned | NO   |     | NULL    |                |
| max_run_time   | int(10) unsigned | NO   |     | NULL    |                |
| paused         | int(10) unsigned | NO   |     | NULL    |                |
| exit_message   | longtext         | NO   |     | NULL    |                |
| resource_id    | varchar(255)     | NO   |     | NULL    |                |
| uuid           | varchar(255)     | NO   | MUL | NULL    |                |
| class          | varchar(255)     | NO   |     | NULL    |                |
| client_job_id  | varchar(255)     | NO   | MUL | NULL    |                |
+----------------+------------------+------+-----+---------+----------------+
```

Many of these fields are obvious, but the various timestamps can be helpful for diagnosing problems, so I will describe each of them.

### `created`
This timestamp indicates when the task was added to the wip_pool table.

### `start_time`
Indicates when the task started.

### `completed`
Indicates when the task completed.

### `wake_time`
This is used when the task has a `run_status` of 1 (`WAITING`), and indicates the earliest time the system will pick up the task for execution. This value is set based on the task's state table, but will be overridden by any signals that are received for that task.

### `claim_time`
This timestamp is supposed to be set when the task moves from `run_status` 1 (`WAITING`) to `run_status` 2 (`PROCESSING`) and effectively indicates how long it has been in the `PROCESSING` state. Even though this isn't being set, you can still find out how long a particular task has been in the `PROCESSING` state:

```
mysql>  select t.id, t.server_id, t.wid, (unix_timestamp() - t.created) as seconds_ago from thread_store t left join wip_pool wp on t.wid = wp.wid where wp.run_status = 2;
+---------+-----------+---------+-------------+
| id      | server_id | wid     | seconds_ago |
+---------+-----------+---------+-------------+
| 5072466 |         6 | 1061396 |          16 |
+---------+-----------+---------+-------------+
1 row in set (0.00 sec)
```

In this case task 1061396 has been executing on server 6 (check the `server_store` table for the hostname) for 16 seconds.

Note that the `wip_pool` contents are constantly being pruned to improve system performance. Currently we remove tasks that have completed and are more than 3 days old. All associated table entries (`thread_store`, `wip_pool`, etc.) are removed as well.

### `work_id`
This identifier attempts to uniquely describe the work the task is attempting to perform. For BuildSteps this `work_id` field is calculated based on the destination of the build. The destination repository URI and branch names are used. That means if you are building a bunch of different branches from different repositories, but they are all going to be pushed to the same destination branch in the same repository, they will all get the same `work_id` value. Importantly, of all of the tasks having the same `work_id`, only one of them will be executed at a time.

# `wip_log`
This table stores all of the log entries for a given Wip task. Here are the fields in this table:

```
mysql> describe wip_log;
+---------------+------------------+------+-----+---------+----------------+
| Field         | Type             | Null | Key | Default | Extra          |
+---------------+------------------+------+-----+---------+----------------+
| id            | int(10) unsigned | NO   | PRI | NULL    | auto_increment |
| timestamp     | int(10) unsigned | NO   | MUL | NULL    |                |
| level         | int(10) unsigned | NO   | MUL | NULL    |                |
| message       | longtext         | NO   |     | NULL    |                |
| object_id     | int(10) unsigned | NO   | MUL | NULL    |                |
| container_id  | longtext         | NO   |     | NULL    |                |
| user_readable | int(11)          | NO   |     | NULL    |                |
+---------------+------------------+------+-----+---------+----------------+
```

The `level` field refers to the log level, and has the following values:

```
1 - FATAL
2 - ERROR
3 - ALERT
4 - WARN
5 - INFO
6 - DEBUG
7 - TRACE
```

The `object_id` field holds the task ID. 
The `user_readable` field will be set to 1 if the message is intended to be viewed by the user.
