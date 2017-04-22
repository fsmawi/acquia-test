const boostrap = require('../support/core').bootstrap;
let helpContentDrawerPage = require('./page-objects/help-center-drawer.page');
let page = require('./page-objects/page');

module.exports = function () {
  this.Then(/^I should see in the \|(.*?)\| only the items that contain "([^"]*)" keyword$/,
    function (contentList, filterText) {
      return helpContentDrawerPage.assertFilteredContentList(page.getDynamicValue(contentList), filterText);
  });
};
