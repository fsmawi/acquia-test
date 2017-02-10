let page = require('./page');

let JobDetailPage = Object.create(page, {

  // page elements
  jobDetailPageTopHeader: {
    get: function () {
      return this.browser.element('//h1[text()=" Pipelines Log "]');
    },
  },

  // method definitions
  /**
   * @return {AssertionError} on failure
   * assert that we are on jobDetailPage by checking its header text 'Pipelines Log' is visible
   */
  assertJobDetailPage: {
    value: function () {
      return this.jobDetailPageTopHeader.waitForVisible(10000).then(function (isJobDetailPageHeaderExists) {
        expect(isJobDetailPageHeaderExists).to.be.true;
      });
    },
  },
});
module.exports = JobDetailPage;
