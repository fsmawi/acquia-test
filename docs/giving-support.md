# Pipelines UX Support

## Context
As a service, we want a central platform to gather, review, and respond to client inquiries. This will help us monitor and deliver consistent product to user feedback in a timely manner (SLA-Support Response).

## Approach
All engineers are assigned the _Light Agent_ role in [Zendesk](agent.acquia.com). Clients will request support from the traditional channels of support. (Cloud, Acquia.com, etc). The Acquia support team will respond to the initial query, with a  "Thank you for the inquiry" and either answer, or pass on to one of the UX Engineers for support (Still within zendesk).

The UX person will have 24 Hours to "Acknowledge" the request in zendesk, and triage to management about it if needed.

If the request is an issue, the engineer should reproduce, and create a [JIRA](backlog.acquia.com) issue and link the issue with the zendesk ticket number, with a priority according to its impact.  The zendesk ticket should stay open until the issue is resolved, which will allow the customer who reported the issue to see it's progress.

Below are some notes from Kent Gale with regards to UX support and triaging on [MS-2373](https://backlog.acquia.com/browse/MS-2373) from 3/3/2017:

> Customer requests are subject to their contracted response time SLA's depending on the type of subscription and the designated urgency of the request submitted. Since customers can have multiple subscriptions, that depends on which subscription they select to associate with a given request. Response time SLA calculations depend on their designated global support region. Whether a particular contact is allowed to submit a request or not depends on their Teams & Permissions settings. Providing visibility to customer-facing teams on customer requests and activity requires knowing the TAM and AM associated with the account. That's a lot of metadata associated with a request and it's all used to trigger various automations that let us meet contracted response obligations 24x7. It also feeds into the reporting by which we manage the business.

> Because our business requirements are complex, we cannot use the Zendesk's front end. Customers submit requests via a Drupal UI where the customer makes choices after which CCI then applies all the business logic necessary to generate the metadata needed to submit a properly tagged Zendesk ticket via Zendesk's APIs. The ticket is then subject to the service handling rules and automations configured in Zendesk. For proactive tickets, there is an internal-facing UI in addition to various tools that have been built to create singular or bulk tickets that are properly "formed".

> It is important for consistency and efficiency that we centrally manage customer requests and interactions. 

