let page = require('./page');

let JobsListPage = Object.create(page, {

  /**
   * Get the last job link
   */
  lastJob: {
    get: function () {
      return '//app-job-list/table/tbody/tr[1]/td[2]/a';
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
   * @return last jobId
   * Get the jobId of last run job which is a first row in job-list table
   */
  getLastJobId: {
    value: function () {
      return this.browser._exists(this.lastJob)
        .getText(this.lastJob);
    },
  },

  /**
   * @return {string} last job status message
   * Get the complete alert text of last job run displayed on JobsListPage
   */
  getAlertMessage: {
    value: function () {
      return this.browser._exists('//app-job-summary')
        .then(() => this.browser.execute(function () {
          return document.getElementsByTagName('e-card')[0].innerText;
        }));
    },
  },

  /**
   * @return {Promise}
   * @param {String} selector
   * Find the joblink from the alert text on jobsListPage and click on it
   */
  clickJobLinkFromAlert: {
    value: function (selector) {
      return this.browser._click(selector);
    },
  },

  /**
   * @return {object}
   * assert that jobsListPage is displayed with the alert
   */
  assertJobsListPage: {
    value: function (jobsListPageIdentifier) {
      return this.browser._exists(jobsListPageIdentifier);
    },
  },

  /**
   * @return {String} activity card title
   * This method returns the title of an activity card
   */
  getActivityCardTitle: {
    value: function () {
      return this.browser._exists('.el-card__title', {timeout: 10000})
        .getText('.el-card__title');
    },
  },

  /**
   * @return {object} list of header values
   * Get the header values of job-list table as an array
   */
  getActivityCardHeaders: {
    value: function () {
      return this.browser.execute(function () {
        return document.querySelectorAll('app-job-list table thead tr th');
      }).then((res) => Promise.all(res.value.map((r) => this.browser.elementIdText(r.ELEMENT))))
        .then((elems) => elems.map((e) => e.value));
    },
  },

  /**
   * @param {String} jobsListTable selector
   * @param {String} activityCardTitle exxpected activity card title
   * @return {AssertionError} on failure
   * check that jobs-list table is inside the Activity e-card
   */
  assertJobListInsideActivityCard: {
    value: function (jobsListTable, activityCardTitle) {
      jobsListTable = jobsListTable.replace('{0}', activityCardTitle);
      return this.browser._exists(jobsListTable);
    },
  },

  /**
   * @param {number} rowNumber in the table
   * @return {browser} object
   * clicks jobLink available in provided rowNumber of jobs-list table
   */
  clickJobLinkInBuildHeader: {
    value: function (rowNumber) {
      let jobLinkXpath = '//table/tbody/tr[' + rowNumber + ']/td[2]/a';
      return this.browser._click(jobLinkXpath);
    },
  },

  /**
   * @param {String} jobId of the job to click
   * click jobLink by id
   */
  clickJobLinkById: {
    value: function (jobId) {
      let jobLinkXpath = '//a[text()="' + jobId + '"]';
      return this.browser._click(jobLinkXpath);
    },
  },

  /**
   * @param {String} job status
   * @return {browser} object
   * clicks job link from jobs-list table by its status message
   */
  clickJobLinkByStatus: {
    value: function (status) {
      let jobLinkElementXpath;
      if (status == 'state__danger')
        jobLinkElementXpath = '//td[./app-job-status//e-svg-icon[@type="state__danger"]]/following-sibling::td/a';
      else if (status == 'spin-reverse')
        jobLinkElementXpath = '//td[./app-job-status//e-svg-icon[@type="feedback__autocompleting"]]/following-sibling::td/a';
      else if (status == 'timer')
        jobLinkElementXpath = '//td[./app-job-status//md-icon[.="timer"]]/following-sibling::td/a';
      else if (status == 'state__success--circle')
        jobLinkElementXpath = '//td[./app-job-status//e-svg-icon[@type="state__success--circle"]]/following-sibling::td/a';

      return this.browser._click(jobLinkElementXpath, {timeout: 10000});
    },
  },

  /**
   * find the jobs-list table rowNum randomly from 1-10 and click on the jobId link
   */
  clickRandomJobId: {
    value: function () {
      return this.clickJobLinkInBuildHeader(Math.floor(Math.random() * 10));
    },
  },

  /**
   * click on the first jobId link
   */
  clickFirstJobId: {
    value: function () {
      return this.clickJobLinkInBuildHeader(1);
    },
  },

  /**
   * @param {String} statusIcon identifier
   * @param {String} messageHasText job status message partial text
   * @return {AssertionError} on failure
   * assert that each job status message is associated with appropriate status icon
   */
  assertStatusIconOfJob: {
    value: function (jobId, statusIcon) {
      let jobIdXpath = '//app-job-list/table//td[a[text()="' + jobId + '"]]';
      let failedOrSucceededJobStatusIconXpath = jobIdXpath +
        '/preceding-sibling::td/app-job-status//e-svg-icon[@type="' + statusIcon + '"]';
      let queuedStatusIconXpath = jobIdXpath + '/preceding-sibling::td/app-job-status//md-icon[.="' + statusIcon + '" and @role="img"]';
      let runningJobStatusIconXpath = jobIdXpath +
        '/preceding-sibling::td/app-job-status//e-progress//e-svg-icon[@animation="spin-reverse"]';

      this.browser._exists(jobIdXpath).then(() => {
        if (statusIcon == 'state__danger')
          return this.isJobStatusIconMatched(failedOrSucceededJobStatusIconXpath);
        if (statusIcon == 'spin-reverse')
          return this.isJobStatusIconMatched(runningJobStatusIconXpath);
        if (statusIcon == 'timer')
          return this.isJobStatusIconMatched(queuedStatusIconXpath);
        if (statusIcon == 'state__success--circle')
          return this.isJobStatusIconMatched(failedOrSucceededJobStatusIconXpath);
      });
    },
  },

  /**
   * @param xpath identifier of jobstatus icon in correlation with status message
   * @return {AssertionError} on failure
   * check the status icon with status message
   */
  isJobStatusIconMatched: {
    value: function (jobStatusIconXpath) {
      return this.browser._waitUntil(jobStatusIconXpath);
    },
  },

  /**
   * Find the first available Stop Button and click on it
   */
  clickOnFirstAvailableStopButton: {
    value: function () {
      return this.browser._click('//button[//md-icon[.="stop"]]');
    },
  },

  /**
   * @param {String} jobstatus
   * check that stopped job should display 'Job is paused' in its status Message
   */
  assertJobStatus: {
    value: function (jobStatus) {
      if (jobStatus == 'Job is paused') {
        return this.browser.element('//td[text()="' + jobStatus + '"]').waitForVisible(10000);
      } else {
        console.log('expected jobStatus' + jobStatus + ' belongs to an unimplemented action. Hence failing the test!!');
        return expect(false).to.be.true;
      }
    },
  },

  /**
   * @param {String} expected status message
   * @return {AssertionError} on job failure
   * assert that no jobs status message is displayed when there are no jobs
   */
  assertNoJobsStatus: {
    value: function (noJobsStatusMessage) {
      this.browser._waitUntil('//*[contains(text(),"' + noJobsStatusMessage + '")]', {timeout: 10000});
    },
  },

  /**
   * check the summary table data matches to job details of last run job
   */
  assertSummaryTableData: {
    value: function () {
      let details;
      return this.getSummaryTableJobId().then((summaryTableJobDetails) => {
        details = summaryTableJobDetails;
        return this.getLastRunJobDetails()
          .then((expectedJobDetails) => {
            expect(expectedJobDetails.slice(1, 2)).to.deep.equal(details);
          });
      });
    },
  },

  /**
   * return the array with last run job details for branch,commit,duration etc.. fields
   * Note: '-' in jobs-list table means 'Not Available' in summary-table
   */
  getLastRunJobDetails: {
    value: function () {
      return this.browser.execute(function () {
        return document.querySelectorAll('app-job-list table tbody tr:nth-child(1) td');
      }).then((res) => Promise.all(res.value.map((r) => this.browser.elementIdText(r.ELEMENT))))
        .then((elems) => elems.map((e) => e.value == '-' ? 'Not available' : e.value));
    },
  },

  /**
   * return the array with summary table job details for branch,commit,duration etc.. fields
   */
  getSummaryTableJobDetails: {
    value: function () {
      return this.browser.execute(function () {
        return document.querySelectorAll('.el-data__value');
      }).then((res) => Promise.all(res.value.map((r) => this.browser.elementIdText(r.ELEMENT))))
        .then((elems) => elems.map((e) => e.value));
    },
  },

  getSummaryTableJobId: {
    value: function () {
      return this.browser.execute(function () {
        return document.querySelectorAll('e-card section div.job__info a');
      }).then((res) => Promise.all(res.value.map((r) => this.browser.elementIdText(r.ELEMENT))))
        .then((elems) => elems.map((e) => e.value));
    },
  },
});

module.exports = JobsListPage;
