let page = require('./page');

let CloudIntegrationPage = Object.create(page, {

  /**
   * @return {Promise}
   * @param {String} pipelinesIframeSelector
   * Check the pipelines app is rendered in an iframe on cloud
   */
  assertPipelinesIframe: {
    value: function (pipelinesIframeSelector) {
      return this.browser._exists(pipelinesIframeSelector, {timeout: 10000});
    },
  },

  /**
   * @return {Promise}
   * @param {String} iframeIdentifier
   * @param {String} jobsListIdentifier
   * Check the jobs list section is rendered in the pipelines iframe
   */
  assertJobsList: {
    value: function (iframeIdentifier, jobsListIdentifier) {
      return this.browser
        ._switchFrame(iframeIdentifier)
        .then(() => {
          return this.browser._exists(jobsListIdentifier, {timeout: 10000});
        });
    },
  },
});

module.exports = CloudIntegrationPage;
