let jobsListPage = require('./page-objects/jobs-list.page');
let jobDetailPage = require('./page-objects/job-detail.page');

module.exports = function () {
  this.Then(/^I should see the jobs\-list page with an alert$/, function () {
    return jobsListPage.assertJobsListPage();
  });

  this.When(/^I have given job with jobid "([^"]*)"$/, function (jobId) {
    return jobsListPage.doesJobExist(jobId);
  });

  this.Then(/^I will see the status message for jobid "([^"]*)" as "([^"]*)"$/, function (jobId, expectedStatus) {
    return jobsListPage.getJobStatusMessage(jobId).then((actualStatus) => expect(actualStatus).to.be.equal(expectedStatus));
  });

  this.When(/^on the jobs\-list page$/, function () {
    return jobsListPage.assertJobsListPage();
  });

  this.When(/^on the jobs\-list page with no jobs$/, function () {
    return jobsListPage.assertJobsListPage();
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

  this.When(/^I click on the job id in the "([^"]*)" column for a job which has the status as state__success\-\-circle$/, function (buildHeader) {
    return jobsListPage.clickJobLinkByStatus('state__success--circle');
  });

  this.When(/^I click on the job id in the "([^"]*)" column for a job which has the status as timer$/, function (buildHeader) {
    return jobsListPage.clickJobLinkByStatus('timer');
  });

  this.When(/^I click on any job id in the "([^"]*)" column from the list of jobs displayed$/, function (buildHeader) {
    return jobsListPage.clickRandomJobId();
  });


  this.When(/^I click on the job link in the alert$/, function () {
    return jobsListPage.clickJobLinkFromAlert();
  });

  this.Then(/^I should navigate to the job\-detail page$/, function () {
    return jobDetailPage.assertJobDetailPage();
  });

  this.Then(/^I should see an activity card with title "([^"]*)"$/, function (expectedActivityCardTitle) {
    return jobsListPage.getActivityCardTitle()
      .then((actualActivityCardTitle) => expect(expectedActivityCardTitle).to.be.equal(actualActivityCardTitle));
  });

  this.Then(/^I should see the appropriate headers for the activity card$/, function () {
    expectedActivityCardHeaders = ['Status', 'Build', 'Branch', 'Commit', 'Duration', 'Completed', 'Actions'];
    let wait = this.browser.waitUntil(() => {
      return this.browser.pause(15000).isVisible('section[class="el-card__body"]');
    }, 60000, 'waited for 1min for the jobs-list table to load but its not loaded');

    return wait.then(() => jobsListPage.getActivityCardHeaders())
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


  this.Then(/^I should see jobs\-list table inside "([^"]*)" card$/, function (activityCardTitle) {
    return jobsListPage.assertJobListInsideActivityCard(activityCardTitle);
  });


  this.When(/^I click on the jobs link in the "([^"]*)" column$/, function (buildHeader) {
    return jobsListPage.clickJobLinkInBuildHeader(1);
  });

  this.Then(/^I should see the "([^"]*)" column icon as state__danger for a job with id "([^"]*)"$/, function (statusColumn, jobId) {
    return jobsListPage.assertStatusIconOfJob(jobId, 'state__danger');
  });

  this.Then(/^I should see the "([^"]*)" column icon as spin\-reverse for a job with id "([^"]*)"$/, function (statusColumn, jobId) {
    return jobsListPage.assertStatusIconOfJob(jobId, 'spin-reverse');
  });

  this.Then(/^I should see the "([^"]*)" column icon as timer for a job with id "([^"]*)"$/, function (statusColumn, jobId) {
    return jobsListPage.assertStatusIconOfJob(jobId, 'timer');
  });

  this.Then(/^I should see the "([^"]*)" column icon as state__success\-\-circle for a job with id "([^"]*)"$/, function (statusColumn, jobId) {
    return jobsListPage.assertStatusIconOfJob(jobId, 'state__success--circle');
  });

  this.Then(/^I should see the message as "([^"]*)" inside the "([^"]*)" card$/, function (statusMessage, activityCardTitle) {
    return jobsListPage.assertNoJobsStatus(statusMessage);
  });

  this.When(/^I click on the job id in the "([^"]*)" column which is not yet finished$/, function (buildHeader) {
    return jobsListPage.clickJobLinkByStatus('spin-reverse');
  });

  this.Then(/^I should see last run job details as a summary table$/, function () {
    return this.browser.pause(10000).then(() => jobsListPage.assertSummaryTableData());
  });

  this.When(/^I click on the job "([^"]*)" that does not have logs$/, function (jobId) {
    return jobsListPage.clickJobLinkById(jobId);
  });

  this.When(/^I click on the job with jobid "([^"]*)"$/, function (jobId) {
    return jobsListPage.clickJobLinkById(jobId);
  });
};
