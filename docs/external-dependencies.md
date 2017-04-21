# External system Dependencies
 
The pipelines UI uses several external services:

1. Acquia Cloud: Production grade hosting for dev/stage/prod/test environments
2. Pipelines API: Main api for interacting with the pipelines service
3. Segment: Captures user events from the UI, and sends them to amplitude
4. Amplitude: Organizes and reports user events into metrics for improvement
5. Lift: Captures user events from the UI, and uses them to personalize content for users
6. Bugsnag: Application error reporting that triggers notifications to the UI engineers.
7. Saucelabs: Cloud-based platform for automated testing of web and mobile applications.
8. N3 API: A RESTful web interface that allows developers to extend, enhance, and customize Acquia Cloud.
 
## Acquia Cloud

Acquia Cloud is a Drupal-tuned application lifecycle management suite, with a complete infrastructure to support PHP application deployment workflow processes, from development and staging through to production. 
Acquia Cloud is being used to host the Pipelines UI application:
https://cloud.acquia.com/app/develop/applications/fbcd8f1f-4620-4bd6-9b60-f8d9d0f74fd0

As Pipelines UX use an Acquia Cloud Enterprise we have three environments, corresponding to each stage in the deployment workflow: **Development**, **Staging**, and **Production**. 
we can access to each environment via its public url:

Development: http://dev.pipelines-internal.acquia.com

Staging: http://staging.pipelines-internal.acquia.com

Production: http://pipelines.acquia.com

Acquia Cloud Enterprise provides more environments, called On Demand Environments (ODE), helping developpers to work on the server instead of locally. In the Pipelines UX context, every new pull request submitted will create a new ODE hosting source code from related branch.

Pipelines UX use also a second Acquia Cloud Enterprise subscription, the [Pipelines UI Testing](https://cloud.acquia.com/app/develop/applications/d6a43c82-cc6e-4426-b6eb-883cbe4a99ea), which is used as part of the end to end testing as the site under test.

## Pipelines as a Service
   
Acquia Pipelines is a tool for developing, testing, and deploying websites or other applications to Acquia Cloud. It executes instructions that we provide via a YAML file [acquia-pipelines.yaml](../acquia-pipelines.yaml), to transform application source code into a build artifact which can be tested and deployed. 
As Pipeline UI is a Javascript web application, we are using the Acquia Pipelines specifically in:
* Getting source code from Git repository
* Installing node modules
* Executing lint and tests
* Building the project using the [@angular/cli](https://github.com/angular/angular-cli) tool chain.
* Deploying to a specific environment

## Amplitude

[Amplitude](https://amplitude.com) is an analytics tool that helps web companies to understand user behavior. Product, marketing, and growth teams use Amplitude to discover and share insights about user engagement, retention, and revenue.


### Integration
1. Login into your Amplitude account. 
2. Go to `SETTINGS > Projects` and select the Pipeline-UI project then click on MANAGE link.
3. Under the Javascript Tab copy the script code and paste it in index.html inside the head tag.
4. Replace the following line of the script:
```js
amplitude.getInstance().init(".....");
```
with:
```js
var ampMock = false;
```
this will help us to displace the global variable **amplitude** during integration.
5. In the [environments\environment.prod.ts](../src/environments/environment.prod.ts) replace the value of **amplitudeAPIKey** with the API key got from your account.

### Using 
In order to send data to Amplitude, we use the service [amplitude.service](../src/app/core/services/amplitude.service.ts) to call the method logEvent() with the event name:

```ts
service.logEvent('EVENT_NAME')
```

## Segment

[Segment](https://segment.com) is a platform that collects, stores, and routes user data to other analytics tools. We use the Segment API to track user events like page views, clicks, and sign ups on websites. 

### Integration
1. Login into your Segment account.
2. Go to the [workspaces](https://segment.com/workspaces) page and click on the pipelines-ui workspace.
3. Click on Javascript card and copy the script code then paste it in index.html inside the head tag.
4. Replace the last three lines of the script: 
```js
  analytics.load("...");
  analytics.page();
```
with:
```js
var analyticsMock = false;
```
this will help us to displace the global variable **analytics** during integration.

### Using
In order to send data to Segment, we use one of two approaches depending on the situation:

#### Segment Directive:
By adding attributes *appSegmentOn*, *[segmentEventData]* and *segmentEventIdentifier* on a tag:

```html
<a  id="auth" 
    appSegmentOn="click" // Event type
    [segmentEventData]="{key1: value1, key2: value2, ...}" // Object 
    segmentEventIdentifier="ClickOnLinkAuthUser" // Unique segment identifier
    (click)="action()">Auth user</a>
```

#### Segement Service:
By using the service [segement.service](../src/app/core/services/segement.service.ts) we can call the method trackEvent() like following:

```ts
service.trackEvent('ClickOnLinkAuthUser', {key1: value1, key2: value2, ...});
```

## Aquia Lift

[Acquia Lift](https://www.acquia.com/products-services/acquia-lift) is an analytics tool that let us track users behavior throughout their journey in website — from anonymous visitor to loyal, repeat customer. 

### Integration 
We could simply integrate the embedded script into head tag, but as we want to track separately user's behavior by site, we need to change the `site_id` variable included in this script dynamically. Thus we are using the service `lift.service` to get environment information first, and then create the script dynamically as following:
```ts
...
  window.AcquiaLift = environment.lift; // Angular's environment object that contain lift variable values

  // Creates a <script> tag to download lift.js and appends it to <head>
  // the Lift Experience Builder script
  const node = document.createElement('script');
  node.type = 'text/javascript';
  node.src = 'https://lift3assets.lift.acquia.com/stable/lift.js';
  node.async = true;
  node.charset = 'utf-8';
  document.getElementsByTagName('head')[0].appendChild(node);
...
```
We need also to replace lift environment variables with our own information in Angular's environment files. And make sure that `site_id` is replaced by the corresponding stage name.

### Using Acquia Lift Directive
In order to send data to Acquia Lift, we add attributes *appLiftOn*, *[liftEventData]* and *liftEventName* on a tag:
```html
<a  id="auth" 
    appLiftOn="click" // Event type that the eventManager is listening for
    [liftEventData]="{key: appId}"  
    liftEventName="ClickOnLinkAuthUser"
    (click)="action()">Auth user</a>
```
#### liftEventName:
The **liftEventName** is used to describe how visitors are interacting with the application. There are four available values (Click-Through, Content View, Decision, Goal), but we can still create custom events by following these [steps](https://docs.acquia.com/lift/profile-mgr/event/category#creating).

#### liftEventData:
The **liftEventData** is used to send captured visitor actions from the application. Acquia Lift use some custom fields to store Information about visitors ([50 custom fields for Events](https://docs.acquia.com/lift/omni/event)).  
So if we want to send data to Acquia Lift service, we should create an alias (also known as a column name) for one of this 50 fields following these [steps](https://docs.acquia.com/lift/profile-mgr/admin/column-meta-data#add). After creating the column name, according to its ID we can set the key of the Object we send in **liftEventData**. For example, if we create a column name that will store the application ID using `custom_field_11` the key will be `event_udf11`
```html
<a  id="auth" 
    appLiftOn="click" // Event type that the eventManager is listening for
    [liftEventData]="{event_udf11: appId}"  
    liftEventName="ClickOnLinkAuthUser"
    (click)="action()">Auth user</a>
```
## Bugsnag

[Bugsnag](https://www.bugsnag.com) is a SaaS-based error monitoring platform. In our context, we are using a browser integration of Bugsnag, 
which gives us instant notification of errors and exceptions in Javascript.

### Integration
As we want Bugsnag to report only errors and exceptions that happen in production environment, a normal integration of the script in the head tag won't work. Instead, we are loading the script dynamically using the service [bugsnag.service](../src/app/core/services/bugsnag.service.ts) like this:

```ts
if (environment.production) {

      const node = document.createElement('script');
      node.type = 'text/javascript';
      node.src = '//d2wy8f7a9ursnm.cloudfront.net/bugsnag-3.min.js';
      node.async = true;
      document.getElementsByTagName('head')[0].appendChild(node);

      // set API KEY
      node.onload = function () {
        window.Bugsnag.apiKey = environment.bugsnagAPIKey;
      };
...
```
We need also to replace the **bugsnagAPIKey** property value in [environments\environment.prod.ts](../src/environments/environment.prod.ts) with the Bugsnag API Key.

## Saucelabs

[Saucelabs](https://saucelabs.com/) is a cloud based, web and mobile application automated testing platform. This is being used for running E2E test cases. 

### Integration 

To run E2E tests on sauce labs, 
   1. Get Saucelabs username and service API Key
   2. And run the following command
	
	``` shell
     aqtest features --host sauce --service-user admin --service-key E2345QW-HWER675-12WERT89
    ```
	
    Note: 
    * Please replace the 'service-user' and 'service-key' option values with Saucelabs account specific values
    * Saucelabs won't support phantomjs so please use other browsers while running in sauce labs

## N3 API

[N3 API](http://cloud.acquia.com/api-docs/) is a RESTful web interface that allows developers to extend, enhance, and customize Acquia Cloud. It includes developer workflow, site management, and provisioning capabilities.


### Integration 

Getting Started
To get started with the API, you need an API access token.

To generate an API access token, login to [cloud.acquia.com](cloud.acquia.com), then visit [cloud.acquia.com/#/profile/tokens](cloud.acquia.com/#/profile/tokens), and click **Create Token**.
   * Provide a label for the access token, so it can be easily identified. Click **Create Token**.
   * The token has been generated, copy the api key and api secret to a secure place. Make sure you record it now: you will not be able to retrieve this access token's secret again.

The Acquia HTTP HMAC spec v2 is used for authenticating requests. Use [Acquia HTTP HMAC library](https://github.com/acquia/http-hmac-javascript) to make requests.
