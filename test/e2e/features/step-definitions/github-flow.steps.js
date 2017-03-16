let page = require('./page-objects/page');
let githubFlowPage = require('./page-objects/github-flow.page');
const boostrap = require('../support/core').bootstrap;

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

  this.Then(/^I should see a modal with non empty \|(.*?)\| list/, function(repoList){
    return githubFlowPage.assertRepoListIsNotEmpty(page.getDynamicValue(repoList));
  });

  this.Then(/^I should not see the repository modal$/, function(){
      return githubFlowPage.assertRepositoryModalWasClosed();
  });

  this.Then(/^I should see empty \|(.*?)\| list$/, function(repoList){
     return githubFlowPage.assertRepoListIsEmpty(page.getDynamicValue(repoList));
  });

  this.Then(/^I should see in the \|(.*?)\| list only repositories that contain "([^"]*)" keyword$/,
  function(repoList, filterText){
    return githubFlowPage.assertFilteredRepositoriesList(page.getDynamicValue(repoList),
    filterText);
  });
}
