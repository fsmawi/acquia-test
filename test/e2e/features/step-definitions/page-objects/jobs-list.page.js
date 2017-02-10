let page = require('./page');

let JobsListPage = Object.create(page, {

  // page elements
  getFailedJobsXpath: {
    get: function () {
      return '//td[contains(text(),"Failed")] | //td[contains(text(),"failed")] | //td[contains(text(),"terminating")]';
    },
  },

  ealert: {
    get: function () {
      return this.browser.element('e-alert');
    },
  },
  lastJob: {
    get: function () {
      return this.browser.element('//app-job-list/table/tbody/tr[1]/td[2]/a');
    },
  },

  activityCardTitle: {
    get: function () {
      return this.browser.element('.el-card__title');
    },
  },

  activityCardHeaders: {
    get: function () {
      return this.browser.element('//section[@class="el-card__content"]//table/thead/tr/th');
    },
  },

  failedJobElement: {
    get: function () {
      return this.browser.element(this.getFailedJobsXpath);
    },
  },

  // method definition
  /**
   * @param {string} jobId
   * @return {boolean}
   * checks whether given jobId is available in jobsListPage within 20 sec. returns true if it's available false otherwise
   */
  doesJobExist: {
    value: function (jobId) {
      return this.browser.isExistingWithTimeout('//a[text()="' + jobId + '"]', 20000);
    },
  },
  /**
   * @param {string} jobId
   * @return {string} job status message
   * It returns the message displayed on jobs-List Page for the given jobId
   */
  getJobStatusMessage: {
    value: function (jobId) {
      return this.browser.getText('//a[text()="' + jobId + '"]/../following-sibling::td[@class="hidden-sm-down"]');
    },
  },
  /**
   * @return last jobId
   * Get the jobId of last run job which is a first row in job-list table
   */
  getLastJobId: {
    value: function () {
      return this.lastJob.getText();
    },
  },
  /**
   * @return {string} last job status message
   * Get the complete alert text of last job run displayed on JobsListPage
   */
  getAlertMessage: {
    value: function () {
      this.ealert.waitForVisible(20000);
      result = this.browser.execute(function () {
        return document.getElementsByTagName('e-alert')[0].innerText;
      });
      return result;
    },
  },
  /**
   * @return {object}
   * Find the joblink from the alert text on jobsListPage and click on it
   */
  clickJobLinkFromAlert: {
    value: function () {
      return this.ealert.element('./div/a').click();
    },
  },
  /**
   * @return {object}
   * assert that jobsListPage is displayed with the alert
   */
  assertJobsListPage: {
    value: function () {
      return this.ealert.waitForVisible(10000).then(function (isAlertExists) {
        expect(isAlertExists).to.be.true;
      });
    },
  },
  /**
   * @return {String} activity card title
   * This method returns the title of an activity card
   */
  getActivityCardTitle: {
    value: function () {
      this.activityCardTitle.waitForVisible(10000);
      return this.activityCardTitle.getText();
    },
  },
  /**
   * @return {object} list of header values
   * Get the header values of job-list table as an array
   */
  getActivityCardHeaders: {
    value: function () {
      let elems = this.browser.execute(function () { return document.querySelectorAll('.el-card__content table thead tr th'); });
      return elems.then((res) => {
        return Promise.all(res.value.map((r) => this.browser.elementIdText(r.ELEMENT)));
      }).then((elems) => {
        return elems.map((e) => e.value);
      });
    },
  },

  /**
   * @return {array} contains jobId and jobBranch
   * click the restart button and returns the jobId and Branch of a job
   */
  restartAndGetJobDetails: {
    value: function () {
      let jobDetailsArray = [];
      jobDetailsArray.push(failedJobElement.element('./preceding-sibling::td[2]/a').getText()); // jobId
      jobDetailsArray.push(failedJobElement.element('./preceding-sibling::td[1]').getText()); // jobBranch
      // click on restart button
      failedJobElement.element('./following-sibling::td[4]/button').click();
      return jobDetailsArray;
    },
  },
  /**
   * @return {array} contains jobId and jobBranch
   * click the stop button and returns the jobId and Branch of a job
   */
  stopAndGetJobDetails: {
    value: function () {
      let jobDetailsArray = [];
      jobDetailsArray.push(failedJobElement.element('./preceding-sibling::td[2]/a').getText()); // jobId
      jobDetailsArray.push(failedJobElement.element('./preceding-sibling::td[1]').getText()); // jobBranch
      // click on stop button
      failedJobElement.element('./following-sibling::td[4]/button').click();
      return jobDetailsArray;
    },
  },
  /**
   * @param exxpected activity card title
   * @return {AssertionError} on failure
   * check that jobs-list table is inside the Activity e-card
   */
  assertJobListInsideActivityCard: {
    value: function (activityCardTitle) {
      return this.browser
        .isExistingWithTimeout('//e-card[//h4/span[text()="' + activityCardTitle + '"]]//e-card-content//app-job-list', 10000)
        .then(function (isJobListInsideActivityCard) {
          expect(isJobListInsideActivityCard).to.be.true;
        });
    },
  },
  /**
   * @return {browser} object
   * clicks first available job link from jobs-list table
   */
  clickJobLinkInBuildHeader: {
    value: function () {
      // click on first available job
      this.browser.waitForVisible('//table/tbody/tr[1]/td[2]/a');
      return this.browser.click('//table/tbody/tr[1]/td[2]/a');
    },
  },
  /**
   * @param {String} statusIcon identifier
   * @param {String} messageHasText job status message partial text
   * @return {AssertionError} on failure
   * assert that each job status message is associated with appropriate status icon
   */
  assertStatusIconOfJob: {
    value: function (statusIcon, messageHasText) {
      messageElement = '//td[contains(text(),"' + messageHasText + '")]';
      failedJobStatusIconXpath = messageElement + '/../td[1]/app-job-status//e-svg-icon[@type="' + statusIcon + '"]';
      succeededStatusIconXpath = messageElement + '/../td[1]/app-job-status//e-svg-icon[@type="' + statusIcon + '"]';
      queuedStatusIconXpath = messageElement + '/../td[1]/app-job-status//md-icon[.="' + statusIcon + '" and @role="img"]';
      runningJobStatusIconXpath = messageElement + '/../td[1]/app-job-status//md-spinner[@class="status-spinner" and @color="primary"]';
      terminatingStatusIconXpath = messageElement + '/../td[1]/app-job-status//md-spinner[@class="status-spinner" and @color="danger"]';

      this.browser.waitForVisible(messageElement);
      if (messageHasText == 'Failed')
        return this.isJobStatusIconMatched(failedJobStatusIconXpath);
      if (messageHasText == 'Job is running')
        return this.isJobStatusIconMatched(runningJobStatusIconXpath);
      if (messageHasText == 'Job is terminating')
        return this.isJobStatusIconMatched(terminatingStatusIconXpath);
      if (messageHasText == 'Job is queued')
        return this.isJobStatusIconMatched(queuedStatusIconXpath);
      if (messageHasText == 'Job has succeeded')
        return this.isJobStatusIconMatched(succeededStatusIconXpath);
    },
  },
  /**
   * @param xpath identifier of jobstatus icon in correlation with status message
   * @return {AssertionError} on failure
   * check the status icon with status message
   */
  isJobStatusIconMatched: {
    value: function (jobStatusIconXpath) {
      return this.browser.waitForVisible(jobStatusIconXpath).then(function (isFound) {
        expect(isFound).to.be.true;
      });
    },
  },

});

module.exports = JobsListPage;
