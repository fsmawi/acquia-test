# Pipelines UI Operational Readiness Documentation

## Goals

Utilizing tried and true DevOps methodology to drive value to the business in a sustainable process.

### Business Objectives

- We have committed to deliver a front-end that allows Customers to build, test, and integrate their applications into the production services that Acquia provides at a fee.
- Measure production business objectives and end user feedback as a measure of quality, instead of only internal automation/validations.
- Able to perform user-experiments and drive product innovation around application contintuous deployment and organizational learnings.
- Acquia will know about problems before the customers, providing a better upfront experience.

### DevOps Objectives

1. Be a high performing team with professional development going from a software engineer to a business service provider. Continuously improving the following Metrics:
	1. Throughput (deploy frequency): code commit to code deploy as measured through the JIRA control charts and cumulative flow charts.
	2. Stability: Mean Time to Recover - Rate of agility to respond and resolve to failures
	3. Error Change Rate
2. Striving for engineer personal motivation and positive commitment. The transition from traditional engineering practices within Acquia to DevOps Engineers who will have to own incidents and resolutions must consider the personal impact to the engineers happiness and ability to deliver focused, predictable quality work.
3. A vertically managable end-to-end process from engineer to product ownership.
4. The engineering team is comfortable running and deploying the Application, within a defined 2 incidents per on-call schedule.

## Definitions

- A service level instruments (SLI) is something that is measured, such as service uptime or the duration of a particular operation.
- A service level objective (SLO) is the value for a service level indicator deemed sufficient to achieve business goals, such as 99.95% (-21.6 Minutes per month) uptime or 100ms/500ms/1000ms at 50%/99%/99.9%.
- A service level agreement (SLA) is a contractual commitment to customers regarding the ratio of time during which a service will be operating within all of its objectives and the consequences if it is not.
- Alert/Notification is an automated message to the team during a defined event.

## Acceptance Criteria

The following criteria define the Pipelines UI as operationally ready. Each item will be accompanied by the story in which it was implemented which must include the implementation architecture, usage, and verification steps.

### Service Level Instruments (SLIs)

Indicator|Link|Rationale
---------|----|---------
Uptime|[MS-2370](https://backlog.acquia.com/browse/MS-2370) [Dashboard](https://my.pingdom.com/reports/uptime#daterange=7days&tab=uptime_tab&check=2569062)|The UI should have an uptime metric
Response Time|[MS-2371](https://backlog.acquia.com/browse/MS-2371) [Dashboard](https://my.pingdom.com/reports/rbc/2569095)|The UI should have a response time metric
User Event Tracking|[MS-2369](https://backlog.acquia.com/browse/MS-2369)|User events should be tracked for analysis aligned business goals

### Service Level Objectives (SLOs)

Objective|Link|Rationale
---------|----|---------
Uptime|Todo|Fundamental Service availability, 99.95%
Response Time|Todo|Goal of consistent response times across load
Central Monitoring|Todo|Centralized monitoring and alerting mechanism to uphold/measure SLOs
User Flow Graduation|Todo|Desired user flows are measured and metricized

### Service Level Agreements (SLAs)

Agreement|Link|Rationale
---------|----|---------
Feedback Response|[MS-2373](https://backlog.acquia.com/browse/MS-2373)|Triage of feedback from clients
On Call Assignments|[MS-2374](https://backlog.acquia.com/browse/MS-2374) [PPO-1558](https://backlog.acquia.com/browse/PPO-1558)|Deployment/24 hour debugging engineer for incident response

### Scalability Requirements

Requirement|Link|Rationale
---------|----|---------
Load Metrics|[MS-2375](https://backlog.acquia.com/browse/MS-2375)|Measure UI response times under load
View Metrics|[MS-2376](https://backlog.acquia.com/browse/MS-2376)|Measure view responses under heavy data load
CDN Implementation|[MS-2377](https://backlog.acquia.com/browse/MS-2377)|Utilize CDN/Varnish for increased performance and auto cache refreshes

### Feedback/Incident Response Strategy and Process

Item|Link|Rationale
---------|----|---------
Feedback System|[MS-2453](https://backlog.acquia.com/browse/MS-2453)|Intuitive gateway for client feedback
Inicident Report System|[MS-2454](https://backlog.acquia.com/browse/MS-2454)|Intuitive gateway for client incident reports
Incident Notifications|[MS-2454](https://backlog.acquia.com/browse/MS-2454)|Automated incident notifications
Subscribable Notifications|[MS-2455](https://backlog.acquia.com/browse/MS-2455)|Public subscribable feed for event internal/external monitoring
API Outage Notifications|[MS-2456](https://backlog.acquia.com/browse/MS-2456)|API outages should be automatically reported to engineering through Hipchat

### Continuous Integration

Activity|Link|Rationale
---------|----|---------
Continuous Unit Testing|[MS-2457](https://backlog.acquia.com/browse/MS-2457)|Unit tests run on every check in
Continuous Integration Testing|[MS-2458](https://backlog.acquia.com/browse/MS-2458)|Integration tests run on every check in
Continuous End to End Testing|[MS-2464](https://backlog.acquia.com/browse/MS-2464)|End to End tests run on every check in
Continuous coverage check|[MS-2457](https://backlog.acquia.com/browse/MS-2457)|Coverage is checked on every commit for compliance
Continuous code style check|[MS-2457](https://backlog.acquia.com/browse/MS-2457)|Code style and usages are checked on every commit for compliance
Continuous acceptance testing|Todo|Acceptance testing is run on deployed customer facing stages
Dedicated External API|[MS-2464](https://backlog.acquia.com/browse/MS-2464)|Staging environment dedicated to UI Development. UX team doesn't want to be limited in their testing by API disruptions to staging


### Continuous Deployment

Activity|Link|Rationale
---------|----|---------
Continuous Pull Request Stage Deployments|Todo|Each PR should deploy a testable stage for verification
Continuous Release Stage Deployments|Todo|Each stage launch should deploy to it's own environment for verification
Continuous Release Events|Todo|Each deployment event should notify engineering and the subscription feed

### Production Testing

Activity|Link|Rationale
---------|----|---------
Availability Tests|[MS-2465](https://backlog.acquia.com/browse/MS-2465)|Quick ping/resource based testing to ensure assets are available (SignalFX)
Acceptance End to End Tests|[MS-2464](https://backlog.acquia.com/browse/MS-2464)|End to end tests that run ensuring basic functionality continues to work after release
Communication Testing|Todo|Ensures all communication channels are still up for client feedback(subscription system, feedback system, IE status.acquia.com, needs investigation)

### Automated Recovery

Activity|Link|Rationale
---------|----|---------
API Poll Outage Recovery|[MS-2466](https://backlog.acquia.com/browse/MS-2466)|Polling API calls should be retried for API outages, but not for configuration or bad user data inputs
User UI Flow Retries|[MS-2467](https://backlog.acquia.com/browse/MS-2467)|User based UI flows should automatically direct users to retry the action in the event of an API/UI error

### Security Considerations and Monitoring

Activity|Link|Rationale
---------|----|---------
Code Obfuscation|-|All javascript assets should be obfuscated
Route Protection|-|Route gaurds should be in place for all protected screens
Protected Assets|[MS-2469](https://backlog.acquia.com/browse/MS-2469)|Server should not allow users without a session to access static assets

### External Dependencies

Item|Link|Rationale
---------|----|---------
Pipelines API|[MS-2468](https://backlog.acquia.com/browse/MS-2468)|The main API powering the UI
Acquia Cloud|[MS-2468](https://backlog.acquia.com/browse/MS-2468)|The primary host of the API

### UX measurements

Metric|Link
--------------------|-----
Number of "pipelines no job" viewers who press configure, and press configure github and succeed doing so, then start a job|[MS-2747](https://backlog.acquia.com/browse/MS-2747) [Chart](https://analytics.amplitude.com/org/2005/chart/4fijsym)
Number of "pipelines no job" viewers who press configure, and press configure acquia git, then start a job|[MS-2748](https://backlog.acquia.com/browse/MS-2748) [Chart](https://analytics.amplitude.com/org/2005/chart/tripfyo)
Number of "pipelines no job" viewers who press start job, and click start job with any branch|[MS-2746](https://backlog.acquia.com/browse/MS-2746) [Chart](https://analytics.amplitude.com/org/2005/chart/jaglmgz)
