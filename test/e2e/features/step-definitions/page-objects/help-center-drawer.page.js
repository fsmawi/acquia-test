let page = require('./page');

let HelpContentDrawerPage = Object.create(page, {

  /**
   * @param {String} contentList identifier for elements in help content (both general + personalized)
   * @param {String} filterText help content filtered with text
   * assert the filtered help content contains only the help items matches with filterText
   */
  assertFilteredContentList: {
    value: function (contentList, filterText) {
      return this.browser._exists(contentList)
        .then(() => {
          return this.browser.getText(contentList);
        })
        .then((res) => {
          if (res.length > 0) {
            // increment by 2 as help item = title + description
            for(let i = 0; i < res.length; i = i+2) {
              // Contact title + description
              const helpItem = res[i] + ' ' + res[i+1];
              expect(helpItem.toLowerCase()).to.contain(filterText.toLowerCase());
            }
          }
        });
    },
  },
});

module.exports = HelpContentDrawerPage;
