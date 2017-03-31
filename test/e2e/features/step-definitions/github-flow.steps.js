let page = require('./page-objects/page');
let githubFlowPage = require('./page-objects/github-flow.page');

module.exports = function () {
  this.When(/^I get success parameter with value "([^"]*)"$/, function (isSuccess) {
    return this.browser.getUrl()
      .then((URL) => {
        indexOfParam = URL.indexOf('?');
        expect(URL.substring(indexOfParam + 1)).to.contain('success=' + isSuccess);
      });
  });

  this.When(/^I get reason parameter with value \|(.*?)\|$/, function (failReason) {
    return this.browser.getUrl()
      .then((URL) => {
        URL = decodeURIComponent(URL);
        indexOfParam = URL.indexOf('?');
        expect(URL.substring(indexOfParam + 1)).to.contain('reason=' + page.getDynamicValue(failReason));
      });
  });

  this.Then(/^I should see a modal with non empty \|(.*?)\| list/, function (repoList) {
    return githubFlowPage.assertRepoListIsNotEmpty(page.getDynamicValue(repoList));
  });

  this.Then(/^I should not see the repository modal$/, function () {
    return githubFlowPage.assertRepositoryModalWasClosed();
  });

  this.Then(/^I should see empty \|(.*?)\| list$/, function (repoList) {
    return githubFlowPage.assertRepoListIsEmpty(page.getDynamicValue(repoList));
  });

  this.When(/^I click on the \|(.*?)\| button within the \|(.*?)\|$/, function (buttonSelector, iframeSelector) {
    return githubFlowPage
      .clickButtonInTheIframe(page.getDynamicValue(iframeSelector), page.getDynamicValue(buttonSelector));
  });

  this.Then(/^I should see in the \|(.*?)\| list only repositories that contain "([^"]*)" keyword$/,
    function (repoList, filterText) {
      return githubFlowPage.assertFilteredRepositoriesList(page.getDynamicValue(repoList), filterText);
    });

  this.Then(/^I should see the repo name \|(.*?)\| in the \|(.*?)\|$/,
    function (repoName, repoInfoSelector) {
      return githubFlowPage
        .assertGitHubRepoName(page.getDynamicValue(repoInfoSelector), page.getDynamicValue(repoName));
    });
};
