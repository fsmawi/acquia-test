const boostrap = require('../support/core').bootstrap;
let cloudIntegrationPage = require('./page-objects/cloud-integration.page');
let page = require('./page-objects/page');

module.exports = function () {

  this.Then(/^I should be shown pipelines app in an \|(.*?)\|$/, function (iframeIdentifier) {
    boostrap(this.browser);
    return cloudIntegrationPage.assertPipelinesIframe(page.getDynamicValue(iframeIdentifier));
  });

  this.Then(/^I should have \|(.*?)\| list within the \|(.*?)\|$/, function (jobsListIdentifier, iframeIdentifier) {
    boostrap(this.browser);
    return cloudIntegrationPage.assertJobsList(
      page.getDynamicValue(iframeIdentifier), page.getDynamicValue(jobsListIdentifier));
  });

};
