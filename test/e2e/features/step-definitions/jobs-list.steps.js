let jobsListPage = require('./page-objects/jobs-list.page');
let jobDetailPage = require('./page-objects/job-detail.page');

module.exports = function () {
  this.Then(/^I should see the jobs\-list page with an alert$/, function () {
    this.browser.pause(7000);
    return jobsListPage.assertJobsListPage();
  });

  this.When(/^I have given job with jobid "([^"]*)"$/, function (jobId) {
    return jobsListPage.doesJobExist(jobId);
  });

  this.Then(/^I will see the status message for jobid "([^"]*)" as "([^"]*)"$/, function (jobId, expectedStatus) {
    return jobsListPage.getJobStatusMessage(jobId).then(function (actualStatus) {
      expect(actualStatus).to.be.equal(expectedStatus);
    });
  });

  this.When(/^on the jobs\-list page$/, function () {
    return jobsListPage.assertJobsListPage();
  });

  this.Then(/^I can see the last job status displays as an alert with a status and message$/, function () {
    return jobsListPage.getLastJobId().then((id) => {
      jobsListPage.getAlertMessage().then((message) => {
        expect(message).to.contain(id);
      });
    });
  });

  this.When(/^I click on the job link in the alert$/, function () {
    return jobsListPage.clickJobLinkFromAlert();
  });

  this.Then(/^I should navigate to the job\-detail page$/, function () {
    return jobDetailPage.assertJobDetailPage();
  });

  this.Then(/^I should see an activity card with title "([^"]*)"$/, function (expectedActivityCardTitle) {
    return jobsListPage.getActivityCardTitle().then(function (actualActivityCardTitle) {
      expect(expectedActivityCardTitle).to.be.equal(actualActivityCardTitle);
    });
  });

  this.Then(/^I should see the appropriate headers for the activity card$/, function () {
    expectedActivityCardHeaders = ['Status', 'Build', 'Branch', 'Message', 'Creator', 'Duration', 'Completed', 'Actions'];
    return jobsListPage.getActivityCardHeaders().then((headers) => {
      expect(headers).to.deep.equal(expectedActivityCardHeaders);
    });
  });

  this.Then(/^I should see a new job after (\d+) seconds with the same branch$/, function (arg1, callback) {
    // Write code here that turns the phrase above into concrete actions
    callback(null, 'pending');
  });


  this.When(/^I click on the "([^"]*)" button in the "([^"]*)" column$/, function (arg1, arg2, callback) {
    // Write code here that turns the phrase above into concrete actions
    callback(null, 'pending');
  });


  this.Then(/^I should see the job status as stopped$/, function (callback) {
    // Write code here that turns the phrase above into concrete actions
    callback(null, 'pending');
  });


  this.Then(/^I should see jobs\-list table inside "([^"]*)" card$/, function (activityCardTitle) {
    return jobsListPage.assertJobListInsideActivityCard(activityCardTitle);
  });


  this.When(/^I click on the jobs link in the "([^"]*)" column$/, function (buildHeader) {
    return jobsListPage.clickJobLinkInBuildHeader();
  });

  this.Then(/^I should see the "([^"]*)" column icon as state__danger for a job has the status Failed in the "([^"]*)" column$/, function (statusColumn, messageColumn) {
    return jobsListPage.assertStatusIconOfJob('state__danger', 'Failed');
  });


  this.Then(/^I should see the "([^"]*)" column icon as status\-spinner with color primary for a job has the status Job is running in the "([^"]*)" column$/, function (statusColumn, messageColumn) {
    return jobsListPage.assertStatusIconOfJob('status-spinner', 'Job is running');
  });


  this.Then(/^I should see the "([^"]*)" column icon as status\-spinner with color danger for a job has the status Job is terminating in the "([^"]*)" column$/, function (statusColumn, messageColumn) {
    return jobsListPage.assertStatusIconOfJob('status-spinner', 'Job is terminating');
  });


  this.Then(/^I should see the "([^"]*)" column icon as timer for a job has the status Job is queued in the "([^"]*)" column$/, function (statusColumn, messageColumn) {
    return jobsListPage.assertStatusIconOfJob('timer', 'Job is queued');
  });

  this.Then(/^I should see the "([^"]*)" column icon as state__success\-\-circle for a job has the status Job has succeeded in the "([^"]*)" column$/, function (statusColumn, messageColumn) {
    return jobsListPage.assertStatusIconOfJob('state__success--circle', 'Job has succeeded');
  });

  this.When(/^there are no jobs$/, function (callback) {
    // Write code here that turns the phrase above into concrete actions
    callback(null, 'pending');
  });

  this.Then(/^I should see the message as "([^"]*)" inside the "([^"]*)" card$/, function (arg1, arg2, callback) {
    // Write code here that turns the phrase above into concrete actions
    callback(null, 'pending');
  });
};
