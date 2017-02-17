let jobsListPage = require('./page-objects/jobs-list.page');
let jobDetailPage = require('./page-objects/job-detail.page');

module.exports = function () {
  this.Then(/^I should see job\-details screen with status message shown in the alert$/, function () {
    return this.browser.pause(7000)
      .then(() => jobDetailPage.assertJobDetailPage())
      .then(() => jobDetailPage.getAlertMessage())
      .then(alertMessage => expect(alertMessage.value).to.be.length.above(7));
  });

  this.Then(/^I can see an alert showing the status of the job and message$/, function () {
    return this.browser.pause(7000)
      .then(() => jobDetailPage.getAlertMessage())
      .then(alertMessage => expect(alertMessage.value).to.have.length.above(7));
  });

  this.Then(/^I should see the "([^"]*)" button$/, function (buttonText) {
    return jobDetailPage.assertBackToJobListButton();
  });


  this.When(/^I click on the button$/, function () {
    return jobDetailPage.clickOnBackToJobList();
  });

  this.Then(/^I should be navigated to jobs\-list page$/, function () {
    return jobsListPage.assertJobsListPage();
  });

  this.Then(/^I should see the details of the job$/, function (callback) {
    // Write code here that turns the phrase above into concrete actions
    callback(null, 'pending');
  });

  this.Then(/^I should see the logs for the job$/, function () {
    return jobDetailPage.assertJobLogsExist();
  });

  this.Then(/^I should see the progress bar below the job details$/, function () {
    return jobDetailPage.assertProgressBar();
  });

  this.Then(/^I should be shown appropriate message about the empty logs$/, function () {
    return jobDetailPage.assertEmptyLogs();
  });
};
