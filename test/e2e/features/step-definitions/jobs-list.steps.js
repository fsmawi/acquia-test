const boostrap = require('../support/core').bootstrap;
let jobsListPage = require('./page-objects/jobs-list.page');
let jobDetailPage = require('./page-objects/job-detail.page');
let page = require('./page-objects/page');

module.exports = function () {
  this.When(/^on the \|(.*?)\| page$/, function (jobsListPageIdentifier) {
    boostrap(this.browser);
    return jobsListPage.assertJobsListPage(page.getDynamicValue(jobsListPageIdentifier));
  });

  this.Then(/^I can see the last job status displays as an alert with a status and message$/, function () {
    return jobsListPage.getLastJobId().then((id) => {
      jobsListPage.getAlertMessage().then((message) => {
        expect(message).to.contain(id);
      });
    });
  });

  this.When(/^I click on the job id in the "([^"]*)" column for a job which has the status as state__danger$/, function (buildHeader) {
    return jobsListPage.clickJobLinkByStatus('state__danger');
  });

  this.When(/^I click on the job id in the "([^"]*)" column for a job which has the status as spin\-reverse$/, function (buildHeader) {
    return jobsListPage.clickJobLinkByStatus('spin-reverse');
  });

  this.When(/^I click on the job id in the "([^"]*)" column for a job which has the status as state__success\-\-circle$/,
    function (buildHeader) {
    return jobsListPage.clickJobLinkByStatus('state__success--circle');
  });

  this.When(/^I click on the job id in the "([^"]*)" column for a job which has the status as timer$/, function (buildHeader) {
    return jobsListPage.clickJobLinkByStatus('timer');
  });

  this.When(/^I click on the first job id in the "([^"]*)" column from the list of jobs displayed$/, function (buildHeader) {
    return jobsListPage.clickFirstJobId();
  });

  this.When(/^I click on the \|(.*?)\| in the alert$/, function (jobLinkSelector) {
    boostrap(this.browser);
    return jobsListPage.clickJobLinkFromAlert(page.getDynamicValue(jobLinkSelector));
  });

  this.Then(/^I should navigate to the \|(.*?)\| page$/, function (jobLinkSelector) {
    boostrap(this.browser);
    return jobDetailPage.assertJobDetailPage(page.getDynamicValue(jobLinkSelector));
  });

  this.Then(/^I should see an activity card with title \|(.*?)\|$/, function (expectedActivityCardTitle) {
    boostrap(this.browser);
    return jobsListPage.getActivityCardTitle()
      .then((actualActivityCardTitle) => expect(actualActivityCardTitle).to.contain(expectedActivityCardTitle));
  });

  this.Then(/^I should see the appropriate headers for the activity card$/, function () {
    expectedActivityCardHeaders = ['Status', 'Job', 'Branch', 'Commit', 'Duration', 'Completed', 'Actions'];
    let selector = 'section[class="el-card__body"]';

    boostrap(this.browser);
    return this.browser._waitUntil(selector, {timeout: 30000})
      .then(() => jobsListPage.getActivityCardHeaders())
      .then((headers) => expect(headers).to.deep.equal(expectedActivityCardHeaders));
  });

  this.Then(/^I should see a new job after (\d+) seconds with the same branch$/, function (arg1, callback) {
    // Write code here that turns the phrase above into concrete actions
    callback(null, 'pending');
  });


  this.When(/^I click on the "([^"]*)" button in the "([^"]*)" column$/, function (buttonText, actionsColumn) {
    if (buttonText == 'Stop') {
      return jobsListPage.clickOnFirstAvailableStopButton();
    } else {
      console.log('clicking on any action other than Stop not yet implemented. Hence failing the Test!!!');
      return expect(false).to.be.true;
    }
  });

  this.Then(/^I should see the job status as "([^"]*)"$/, function (jobStatus) {
    return jobsListPage.assertJobStatus(jobStatus);
  });

  this.Then(/^I should see \|(.*?)\| inside \|(.*?)\| card$/, function (jobsListTable, activityCardTitle) {
    boostrap(this.browser);
    jobsListTable = page.getDynamicValue(jobsListTable);
    return jobsListPage.assertJobListInsideActivityCard(jobsListTable, activityCardTitle);
  });

  this.When(/^I click on the jobs link in the "([^"]*)" column$/, function (buildHeader) {
    return jobsListPage.clickJobLinkInBuildHeader(1);
  });

  this.Then(/^I should see the "([^"]*)" column icon as state__danger for a job with id "([^"]*)"$/,
    function (statusColumn, jobId) {
    return jobsListPage.assertStatusIconOfJob(jobId, 'state__danger');
  });

  this.Then(/^I should see the "([^"]*)" column icon as spin\-reverse for a job with id "([^"]*)"$/,
    function (statusColumn, jobId) {
    return jobsListPage.assertStatusIconOfJob(jobId, 'spin-reverse');
  });

  this.Then(/^I should see the "([^"]*)" column icon as timer for a job with id "([^"]*)"$/,
    function (statusColumn, jobId) {
    return jobsListPage.assertStatusIconOfJob(jobId, 'timer');
  });

  this.Then(/^I should see the "([^"]*)" column icon as state__success\-\-circle for a job with id "([^"]*)"$/,
    function (statusColumn, jobId) {
    return jobsListPage.assertStatusIconOfJob(jobId, 'state__success--circle');
  });

  this.Then(/^I should see the message as "([^"]*)" inside the "([^"]*)" card$/,
    function (statusMessage, activityCardTitle) {
    return jobsListPage.assertNoJobsStatus(statusMessage);
  });

  this.When(/^I click on the job id in the "([^"]*)" column which is not yet finished$/, function (buildHeader) {
    return jobsListPage.clickJobLinkByStatus('spin-reverse');
  });

  this.Then(/^I should see last run job details as a summary table$/, function () {
    return jobsListPage.assertSummaryTableData();
  });

  this.When(/^I click on the job "([^"]*)" that does not have logs$/, function (jobId) {
    return jobsListPage.clickJobLinkById(jobId);
  });

  this.When(/^I click on the job with jobid "([^"]*)"$/, function (jobId) {
    return jobsListPage.clickJobLinkById(jobId);
  });

  this.Then(/^I should see non empty \|(.*?)\| list/, function (branchList) {
    return jobsListPage.assertListIsNotEmpty(page.getDynamicValue(branchList));
  });

  this.Then(/^I should see in the \|(.*?)\| list only items that contain "([^"]*)" keyword$/,
    function (branchList, filterText) {
      return jobsListPage.assertFilteredList(page.getDynamicValue(branchList), filterText);
  });
};
