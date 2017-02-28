/**
 * Created by stephen.raghunath on 2/24/17.
 */

const boostrap = require('../support/core').bootstrap;
let page = require('./page-objects/page');
const path = require('path');

module.exports = function () {
  this.Given(/^I visit( the)? \|(.*?)\|/, function (theOperator, pageIdentifier) {
    boostrap(this.browser);
    page.setBrowser(this.browser);
    return this.browser._url(page.getDynamicValue(pageIdentifier));
  });

  this.Given(/^I have navigated to \|(.*?)\| page$/, function (pageIdentifier) {
    boostrap(this.browser);
    if (process.env.PIPELINES_URL) {
      let URL = path.join(process.env.PIPELINES_URL, page.getDynamicValue(pageIdentifier));
      this.browser._url(URL).pause(5000);
    }
    else {
      return this.browser.getUrl()
        .then((URL) => {
          URL = path.join(URL.replace('/auth/tokens', ''),
            page.getDynamicValue(pageIdentifier));
          this.browser._url(URL);
        });
    }
  });

  this.When(/^I enter \|(.*?)\| in the \|(.*?)\|/, function (value, selector) {
    boostrap(this.browser);
    return this.browser._click(page.getDynamicValue(selector), { timeout: 30000 }).keys(page.getDynamicValue(value));
  });

  this.When(/^I click on the \|(.*?)\| button$/, function (selector) {
    boostrap(this.browser);
    return this.browser._click(page.getDynamicValue(selector));
  });

  this.When(/^I click on the \|(.*?)\| link$/, function (selector) {
    boostrap(this.browser);
    return this.browser._click(page.getDynamicValue(selector));
  });

  this.Then(/^I should be navigated to \|(.*?)\|$/, function (expectedUrl) {
    boostrap(this.browser);

    if (process.env.PIPELINES_URL)
      PipelinesUnAuthUrl = process.env.PIPELINES_URL.replace(/:(.*)@/, '://') + page.getDynamicValue(expectedUrl);
    else
      PipelinesUnAuthUrl = page.getDynamicValue(expectedUrl);

    return this.browser._wait(5, 'seconds', 'waitForPipelinesUnauthenticatedPageToLoad')
      .then(() => this.browser._checkUrl(PipelinesUnAuthUrl));
  });

  this.Then(/^I wait (.*?) (.*?) (.*)/, function (value, format, message) {
    boostrap(this.browser);
    return this.browser._wait(page.getDynamicValue(value), page.getDynamicValue(format), message);
  });

  this.Then(/^I should see the \|(.*?)\|/, function (selector) {
    boostrap(this.browser);
    return this.browser._exists(page.getDynamicValue(selector));
  });
};
