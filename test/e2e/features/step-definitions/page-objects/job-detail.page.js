let page = require('./page');

let JobDetailPage = Object.create(page, {

  /**
   * Gets the label from an e-data
   */
  jobDetailsDataFieldValue: {
    get: function (dataFieldLabel) {
      return this.browser.element('//div[text()="' + dataFieldLabel + '"]/../following-sibling::e-data-value');
    },
  },

  /**
   * @param {String} jobDetailPage header identifier
   * @return {AssertionError} on failure
   * assert that we are on jobDetailPage by checking its header text 'Pipelines Log' is visible
   */
  assertJobDetailPage: {
    value: function (jobDetailPageIdentifier) {
      return this.browser._exists(jobDetailPageIdentifier, { timeout: 15000 });
    },
  },

  /**
   * @return {AssertionError} on failure
   * @param {String} progressBarSelector
   * assert the existense of progress bar in job-detail page when the job is not yet finished
   */
  assertProgressBar: {
    value: function (progressBarSelector) {
      return this.browser._waitUntil(progressBarSelector, { timeout: 10000 });
    },
  },

  /**
   * @param {String} jobsLinkSelector
   * clicks on Jobs link in job-detail page
   */
  clickOnBackToJobList: {
    value: function (jobsLinkSelector) {
      this.browser._click(jobsLinkSelector, { timeout: 10000 });
    },
  },

  /**
   * @return {string} last job status message
   * Get the complete alert text of last job run displayed on JobsListPage
   */
  getAlertMessage: {
    value: function () {
      return this.browser.execute(function () {
        return document.getElementsByTagName('e-card')[0].innerText;
      });
    },
  },

  /**
   * @param {String} label text of data field
   * @return {String} Data Field value of job-details fields
   * get the value of data field like duration, created by, job id etc...
   */
  getJobDetailsDataFieldValue: {
    value: function (dataFieldLabel) {
      this.jobDetailsDataFieldValue(dataFieldLabel).waitForVisible(10000)
        .then(() => this.jobDetailsDataFieldValue(dataFieldLabel).getText());
    },
  },

  /**
   * @return {AssertionError} on failure
   * assert that jobLogs view exist
   */
  assertJobLogsExist: {
    value: function (jobLogs) {
      return this.browser._waitUntil(jobLogs, { timeout: 10000 });
    },
  },

  /**
   * @return {AssertionError} on failure
   * @param {String} emptyLogsSelector
   * This method checks the logs message indicating empty logs
   */
  assertEmptyLogs: {
    value: function (emptyLogsSelector) {
      return this.browser._waitUntil(emptyLogsSelector, { timeout: 10000 });
    },
  },
});
module.exports = JobDetailPage;
