let jobsListPage = require('./page-objects/jobs-list.page');
let jobDetailPage = require('./page-objects/job-detail.page');
let page = require('./page-objects/page');

module.exports = function () {
  this.Then(/^I should see \|(.*?)\| screen with status message shown in the alert$/, function (jobDetailIdentifier) {
    return jobDetailPage.assertJobDetailPage(page.getDynamicValue(jobDetailIdentifier))
      .then(() => jobDetailPage.getAlertMessage())
      .then(alertMessage => expect(alertMessage.value).to.be.length.above(7));
  });

  this.Then(/^I can see an alert showing the status of the job and message$/, function () {
    return jobDetailPage.getAlertMessage()
      .then(alertMessage => expect(alertMessage.value).to.have.length.above(7));
  });

  this.Then(/^I should see the details of the job$/, function (callback) {
    // Write code here that turns the phrase above into concrete actions
    callback(null, 'pending');
  });

  this.Then(/^I should be shown appropriate \|(.*?)\| message$/, function (emptyLogsIdentifier) {
    return jobDetailPage.assertEmptyLogs(page.getDynamicValue(emptyLogsIdentifier));
  });
};
