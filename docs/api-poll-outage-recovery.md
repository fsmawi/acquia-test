# API Poll Outage Recovery

As part of any application that uses an API polling method, we need to implement a handling strategy when system goes down. This includes server errors, timeouts or network failure.


## Strategy

UI should notify the user that the API is not responding and that the retry is scheduled for the next n seconds/minutes (where n is an agreed or calculated amount of time). The user can still click on a link to retry immediately. The system should also have a maximum number of retries which, when reached, will trigger another UI action such as redirecting to an error page that informs user that the system is momentarily down.

## Pipelines UX handling way

Pipelines UX is only handling three HTTP status codes 400, 403 and 404. Which mean that if there is any 5xx error such as a bug in the server or a load balancer that fails to process the request, User will not have any UI notification.
The API Polling is being used in the Jobs flow, which include two scenarios:

### Jobs List
In this screen, API requests are polling to get updates about jobs. 
When an error occurs, user will be redirected to an error page with a message corresponding to the status code of the error, and a link to the (auth/tokens) screen.

### Job Details
In this screen API requests are polling to get log updates when the job is running.
When an error occurs, user will be redirected to an error page with a message corresponding to the status code of the error, and a link to the Jobs List screen.
