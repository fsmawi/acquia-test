let page = require('./page-objects/page');

module.exports = function () {
  this.Then(/^I should see in the \|(.*?)\| only the items that contain "([^"]*)" keyword$/,
    function (contentList, filterText) {
      return this.browser._exists(page.getDynamicValue(contentList))
        .then(() => this.browser._getText(page.getDynamicValue(contentList)))
        .then((res) => {
          if (res.length > 0) {
            // increment by 2 as help item = title + description
            for (let i = 0; i < res.length; i = i+2) {
              // concat title + description
              const helpItem = res[i] + ' ' + res[i+1];
              expect(helpItem.toLowerCase()).to.contain(filterText.toLowerCase());
            }
          }
        });
  });
};
