let page = require('./page');

let JobDetailPage = Object.create(page, {

  /**
   * Top header
   */
  jobDetailPageTopHeader: {
    get: function () {
      return this.browser.element('//a[text()="Jobs "]');
    },
  },

  /**
   * Back to jobs link
   */
  backToJobListButton: {
    get: function () {
      return this.browser.element('//a[text()="Jobs "]');
    },
  },

  /**
   * Gets the label from an e-data
   */
  jobDetailsDataFieldValue: {
    get: function (dataFieldLabel) {
      return this.browser.element('//div[text()="' + dataFieldLabel + '"]/../following-sibling::e-data-value');
    },
  },

  /**
   * Gets the logs element
   */
  jobLogs: {
    get: function () {
      return this.browser.element('e-card#logs');
    },
  },

  /**
   * Gets the Alert element
   */
  eAlert: {
    get: function () {
      return this.browser.element('e-alert');
    },
  },

  /**
   * progressbar after the job-list content
   */
  progressBarElement: {
    get: function () {
      return this.browser.element('//e-card//md-progress-bar[@role="progressbar"]');
    },
  },

  /**
   * Returns the no logs element
   */
  emptyLogsMessage: {
    get: function () {
      return this.browser.element('//i[contains(text(),"There are no logs for this job.")]');
    },
  },

  /**
   * @return {AssertionError} on failure
   * assert that we are on jobDetailPage by checking its header text 'Pipelines Log' is visible
   */
  assertJobDetailPage: {
    value: function () {
      return this.jobDetailPageTopHeader.waitForVisible(10000)
        .then((isJobDetailPageHeaderExists) => expect(isJobDetailPageHeaderExists).to.be.true);
    },
  },

  /**
   * @return {AssertionError} on failure
   * assert the existense of progress bar in job-detail page when the job is not yet finished
   */
  assertProgressBar: {
    value: function () {
      return this.progressBarElement.waitForVisible(10000)
        .then((isProgressBarDisplayed) => expect(isProgressBarDisplayed).to.be.true);
    },
  },

  /**
   * clicks on Back To JobList button
   */
  clickOnBackToJobList: {
    value: function () {
      this.backToJobListButton.waitForVisible(10000)
        .then(() => this.backToJobListButton.click());
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
    value: function () {
      return this.jobLogs.waitForVisible(10000).then((isJobLogsExists) => expect(isJobLogsExists).to.be.true);
    },
  },

  /**
   * @return {AssertionError} on failure
   * assert that BackToJobListButton exist
   */
  assertBackToJobListButton: {
    value: function () {
      return this.backToJobListButton.waitForVisible(10000)
        .then((isBackToJobListButtonExist) => expect(isBackToJobListButtonExist).to.be.true);
    },
  },

  /**
   * @return {AssertionError} on failure
   * This method checks the logs message indicating empty logs
   */
  assertEmptyLogs: {
    value: function () {
      return this.emptyLogsMessage.waitForVisible(10000)
        .then((isEmptyLogsMsgExists) => expect(isEmptyLogsMsgExists).to.be.true);
    },
  },
});
module.exports = JobDetailPage;
