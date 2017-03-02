#End to End Testing Pipelines

## Pre-requisites

You will need to install aqtest and selenium-standalone for writing and running the e2e tests.
aqtest library is part of pipelines-ui. So, upon building pipelines-ui package, aqtest package
will also be installed automatically.

Selenium-Standalone is bundled with aqtest. So, it will be installed as follows

    aqtest --install-selenium

you can find more about aqtest over here
> https://github.com/acquia/aqtest

From pipelines-ui location run the following command in a terminal

Example: 

    npm install

Alternatively to install aqtest, selenium-standalone separately run the following commands from the terminal

    npm install acquia/aqtest
    aqtest --install-selenium
    
## Writing the tests
The process of creating e2e tests has two stages

### Stage 1
 Define the tests using cucumber feature file

  1. Explore the UI of a feature by logging into Pipelines-UI
 
  2. Design the e2e scenarios for each feature, and create them in the feature file as steps(in cucumber)
 
 > Note: Don't forget to tag the Feature and each Scenario. So that, We have the flexibility of
 running only the scenario(s) which are tagged with @FeatureName and @FeatureName_ScenarioName
 
 Example:
 
 ```cucumber
@JobList
Feature: Pipelines Jobs List
  As an Acquia Pipelines user
  I want to have a list of jobs that updates realtime so that I can monitor my application builds

  @JobList_CheckAlertLastJob
  Scenario: Check the last job is displayed as an alert
    Given jobs yml file "jobs.yml"
    When on the jobs-list page
    Then I can see the last job status displays as an alert with a status and message
 ```

### Stage 2
 **Key-Note**
 
 **Framework-For-Writing-E2E-Tests**
 
 We have created a framework by extending few of the existing webdriverIO API methods to handle synchronization/timing, 
 error handling and screenshot capture for better debugging. So, it's highly recommended to use these framework functions while
 writing the tests. This framework mainly consists of 4 components

 1. There are common step definitions to use and are preferred over writing your own step definitions. Please refer 
    [common-steps.js](../test/e2e/features/step-definitions/common-steps.js) file for more details
 
 2. All the extended webdriverIO functions can be found in [core.js](../test/e2e/features/support/core.js) file. Please refer this file while 
 creating the tests and make use of them in your script.
 
  Example#1: [For 1 & 2 above]
 
  ```js
   let page = require('./page');
   const boostrap = require('../../support/core').bootstrap;

   let JobsListPage = Object.create(page, {
    clickJobLinkById: {
    value: function (jobId) {
      let jobLinkXpath = '//a[text()="' + jobId + '"]';
      return this.browser._click(jobLinkXpath);
    },
   },
  ```
 
 3. Similarly, we've created a top level page-object named 'page.js' which contains some util functions which can be used across all 
 the pages. It's like a common page-object. So, While writing the tests you have to keep in mind this [page.js](../test/e2e/features/step-definitions/page-objects/page.js) file too along with [core.js](../test/e2e/features/support/core.js) file
 and make use of the methods defined in these files while scripting your tests.
 
 4. <module-name>.properties.js which contains all the feature level, scenario level properties; Properties means UI elements selectors,
 test data etc. For example refer this file [job-details.properties.js](../test/e2e/features/job-details.properties.js)
 
 Example#2: [For 3 & 4 above]
 
  ```js
  let jobsListPage = require('./page-objects/jobs-list.page');
  let jobDetailPage = require('./page-objects/job-detail.page');
  let page = require('./page-objects/page');

  module.exports = function () {
    this.Then(/^I should see \|(.*?)\| screen with status message shown in the alert$/, function (jobDetailIdentifier) {
      return jobDetailPage.assertJobDetailPage(page.getDynamicValue(jobDetailIdentifier))
        .then(() => jobDetailPage.getAlertMessage())
        .then(alertMessage => expect(alertMessage.value).to.be.length.above(7));
    });
  ```
 
 5. hooks.js this is the extension file for cucumber events listener. Cucumber basically generates events during each phase of it's
 feature file(s) execution. Like Before Features, Before Feature, Before Scenario etc.. where we have overriden these event methods
 and written our own implementation to load the properties file described in the above step and bind these properties to the feature
 and the scenario scope as defined in the properties file which can later be used in tests to replace the placeholders defined in .feature file.
 Please refer to [hooks.js](../test/e2e/features/support/hooks.js) file for more details.
 
  
 Write the step definitions for each scenario described in the feature file
 
 1. Each step of each scenario should actually map to one step definition. Cucumber has the tendency to generate this mapping automatically.
 All you need to do is, run the feature file without writing any step definitions, cucumber will provide us step definition functions 
 with dummy implementations which we can use and modify to provide actual implementation of the test step
 
	``` shell
     aqtest feature-file-name
	```
	
 Example:
 
 ```cucumber
  @Acceptance @BakerySSO @BakerySSO_VerifySSO
  Scenario: Log in with bakery
  ? Given I open the |*pipelines-url| url

	Warnings:

	1) Scenario: Log in with bakery - features\sample.feature:7
	   Step: Given I open the |*pipelines-url| url - features\sample.feature:10
	   Message:
		 Undefined. Implement with the following snippet:

		   this.Given(/^I open the \|\*pipelines\-url\| url$/, function (callback) {
			 // Write code here that turns the phrase above into concrete actions
			 callback(null, 'pending');
		   });

	1 scenario (1 undefined)
	1 step (1 undefined)
```

Guidelines to Name the file(s):
 There are certain guidelines you need to follow while giving names to the feature files, step definition files, page files etc..
 
 **Feature File**:
 
 The Feature file should match the name of the angular module it is for. For example, the feature file for login module should be like 'login.feature'
 and the feature file for jobs list should be like 'jobs-list.feature' [Note each word should be separated by '-'].
 
 **Step-Definiton File**:
 
 Step definition file should match the name of the angular module it is for. For example, step definiton file for login should be like
 'login.steps.js'
 
 **Page-Object File**:
 
 Page-Object file which contains the actual implementation should match the name of the angular module it is for. For example, Page-Object file for 
 login should be like 'login.page.js'
      
 2. As we are following Page-Object pattern, you have to write the implementation inside a Page-Object File. There should be a separate file
 for each Page/Screen of your application. It should contain Page speciifc elements and actions in the form of functions.
 
 Please refer the following link for more details on Page-Object Pattern and how to create Page-Object file(s)
 > http://webdriver.io/guide/testrunner/pageobjects.html
 
 Example:
 
  ```js 
      let page = require('./page');

      let LoginPage = Object.create(page, {

        // page elements
        /**
         * App ID input element
         */
        appId: {
          get: function () {
            return this.browser.element('input[name="AppId"]');
          },
        },

        /**
         * Sign in input element
         */
        signIn: {
          get: function () {
            return this.browser.element('.md-primary');
          },
        },

        // method definitions
        /**
         * @param {String} appIdValue
         * sets the value for appId text field
         */
        setAppId: {
          value: function (appIdValue) {
            return page.setValue(this.appId, appIdValue);
          },
        },

        /**
         * clicks on signIn button
         */
        doSignIn: {
          value: function () {
            return this.signIn.submitForm();
          },
        },
      });
     module.exports = LoginPage;
  ```
  
 We are using ***webdriverIO*** API to drive the UI in Test Automation. WebdriverIO is a JavaScript implementation of Selenium WebDriver.
 To know more about it and its API methods. Please refer the following document.
 > http://webdriver.io/api.html
  
For writing Assertions in tests we are using chai.expect
Link to chai to assert the response content
> http://chaijs.com/

Example:

```js
let jobsListPage = require('./page-objects/jobs-list.page');
let jobDetailPage = require('./page-objects/job-detail.page');
let page = require('./page-objects/page');

module.exports = function () {
  this.Then(/^I should see \|(.*?)\| screen with status message shown in the alert$/, function (jobDetailIdentifier) {
    let expectedAlertMessage='Job alert 12345';
    return jobDetailPage.assertJobDetailPage(page.getDynamicValue(jobDetailIdentifier))
      .then(() => jobDetailPage.getAlertMessage())
      .then(alertMessage => expect(alertMessage.value).to.be.equal(expectedAlertMessage));
  });
```

## Running the tests
For running the e2e tests you need to follow the following steps

####Step#1: Open a terminal and set the following environment properties

You will need to set PIPELINES_URL and AQTEST_DEBUG=1(This is only required if we want to capture test-logs).
PIPELINES_URL will be set with fully qualified URL of your pipelines-ui application under test.
environment variables should be set with respective values before running the test(s). User should set these variables as follows

**Note:**
    'admin' and 'test123' used in the following examples in the URL are the BASIC_AUTH_USER and BASIC_AUTH_PASSWORD respectively. 
     Please replace them with your PIPELINES-UI environment specific values.

Example:
usage [from windows DOS prompt]:

    set PIPELINES_URL=https://admin:test123@pipelinesuidev1.network.acquia-sites.com 
    set AQTEST_DEBUG=1
     
usage [from bash shell]:

    export PIPELINES_URL=https://admin:test123@pipelinesuidev1.network.acquia-sites.com 
    export AQTEST_DEBUG=1
       
####Step#2: 

Alternately, you can pass these variables inline to a node command as follows
usage:

       PIPELINES_URL=https://admin:test123@pipelinesuidev1.network.acquia-sites.com AQTEST_DEBUG=1
       node ./node_modules/.bin/aqtest test/e2e/features 
       
#####Step#3: Switch to 'pipelines-ui/test/e2e' folder from the terminal and run the features
  Before Running the tests
  1. we need to start the selenium-server first using the following command in a separate terminal
     
     node selenium-standalone start
 
  2. We need to override the browser on which we are planning to run the tests in **'aqtestfile.js'** found in 'test/e2e' folder.
     content inside aqtestfile.js is this..
	 
     ```js
      // Example of custom settings
      module.exports = {
      // Custom browser specifications.
      browser: {
        name: 'chrome', //Supported browsers are 'phantomjs' (it only supports in local Run) , 'firefox', 'internetexplorer'
        width: '1024',
        height: '768',
      },

     };
     ```
     
  **Run Locally**
    
  1. To Run all the features at a time    
  
    ``` shell
	  aqtest features
    ```
 
   Alternatively, if you have aqtest installed globally, you can run the below command
    
	``` shell
	 node ./node_modules/.bin/aqtest features
	```
	
  2. To Run a single feature file [This will run all the scenarios inside the specified feature file]

	``` shell
	  aqtest features/jobs-list.feature
	```
   
  3. To Run the Features/Scenarios filter by Tags. This will run only the features or scenarios tagged by 'tag1'
   
    ``` shell
	  aqtest features --tags @tag1
	```
	
    negate for this is
	
    ``` shell
	  aqtest features --tags ~@tag1
	```
	
   For More information on tags please refer the following document
   > https://github.com/cucumber/cucumber/wiki/Tags
 
   **Run in SauceLabs**
    To run the e2e tests on sauce labs, 
    1. we need to get the saucelabs username and service API Key first
    2. Second, Run the following command
	
	``` shell
     aqtest features --host sauce --service-user admin --service-key E2345QW-HWER675-12WERT89
    ```
	
    Note: 
    * Please replace the above 'service-user' and 'service-key' option values with sauce labs account specific values
    * sauce labs won't support phantomjs so please use other browsers while running in sauce labs
    
   **aqtest command line options help**
    To get the help of aqtest different commandline options run the following command
	
	``` shell
	  aqtest --help
	```  
  Debug logging:
  
     If we want to debug/troubleshoot any failures framework has the capability to log the screenshot for each step of the execution.
     In order to leverage this, one has to set the AQTEST_DEBUG envrionment flag to 1 as described in the above steps. Then, After
     Running the feature/Scenario we can see the screenshot of each step of scenario execution inside
     'e2e/test-logs/<scenario-name>/yyy-mm-dd-ms' folder.
     
    For Example:
    
    **test\e2e\test-logs\Ajobintheactivitycardshouldlinktothedetailpageforthatjob\2017_02_28_1488284705**
    
    Launch aqtest runner in debug mode:
	To run aqtest runner in debug mode use the following command
	
	``` shell
	 aqtest-debug features\jobs-list.feature --tags @JobList_CheckSummaryTable
	```
 
    Debugger listening on port 9229.
    Warning: This is an experimental feature and could change at any time.
    To start debugging, open the following URL in Chrome:
    chrome-devtools://devtools/bundled/inspector.html?experiments=true&v8only=true&ws=127.0.0.1:9229/58464772-3db6-437e-aaef-cc4a8748d765
    
    Open the URL it's showing in the terminal in a chrome browser and where you have an option to open the file and put
    breakpoints. So that, script will stop there and you can start debugging.
    
    Alternately, you can create a debug listener in your IDE [webStorm or VisualStudioCode etc.. to listen on port 9229]
          
    Troubleshoot:
      * SomeTimes the terminal may get stuck making the progress update during tests, which can be resumed by pressing CTRL+C
      
      * Sometimes when we run the tests multiple times, selenium-standalone server may get stuck and fail to open a browser which
        can be restored by killing the already opened selenium terminal/process and start again freshly from the terminal
        
      * Sometimes you may experience odd behavior like few steps you define in a Scenario might run in random order rather than
        sequential execution. In which case please check the Given, When and Then sequence they should be in order. No 'When' step     
        should come prior to 'Given'. Similarly. No 'Then' step should come prior to 'When'. In Other way, All 'When' steps should start 
		only after all the 'Given' Steps are finished and all 'Then' steps should start only after all the 'When' steps are finished.
        
      * Sometimes your test may not wait for the condition you specified/pause for the time interval given. which is due to javascripts
        SingleThread nature. If you write wait/pause code followed by selenium action you wanna perform without chaining them then it
        could happen. In which case, we need to chain them together so that they will run in synchronously.
         
         Example:
		 
           ```js
           this.browser._wait(5, 'seconds', 'waitForPipelinesUnauthenticatedPageToLoad');
           return this.browser._checkUrl(PipelinesUnAuthUrl));
           
           In the above exmple, _checkUrl function gets called prior to the completion of _wait call. Thats not what we anticipated. So, To make these
           synchronous we must chain them together as follows....
		   
           return this.browser._wait(5, 'seconds', 'waitForPipelinesUnauthenticatedPageToLoad')
             .then(() => this.browser._checkUrl(PipelinesUnAuthUrl));
           ```  
		   
   
