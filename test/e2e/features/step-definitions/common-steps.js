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
    } else {
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

    return this.browser._click(page.getDynamicValue(selector), {timeout: 60000})
      .then(() => this.browser.setValue(page.getDynamicValue(selector), page.getDynamicValue(value)));
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

  this.Then(/^I should be navigated to \|(.*?)\| page$/, function (pageTitle) {
    boostrap(this.browser);
    return this.browser._waitUntil(page.getDynamicValue(pageTitle), {timeout: 10000});
  });

  this.Then(/^I wait (.*?) (.*?) (.*)/, function (value, format, message) {
    boostrap(this.browser);
    return this.browser._wait(page.getDynamicValue(value), page.getDynamicValue(format), message);
  });

  this.Then(/^I should see the \|(.*?)\|/, function (selector) {
    boostrap(this.browser);
    return this.browser._exists(page.getDynamicValue(selector));
  });

  this.Then(/^I delete the browser cookies$/, function() {
    return this.browser._deleteCookies();
  });

  this.When(/^I should see a \|(.*?)\| with \|(.*?)\|$/, function (textElementIdentifier, expectedText) {
    return this.browser._waitUntil(page.getDynamicValue(textElementIdentifier), {timeout: 5000})
      .then(() => {
        return this.browser._getText(page.getDynamicValue(textElementIdentifier));
      })
      .then((actualText) => {
        expect(page.getDynamicValue(expectedText)).to.be.equal(actualText);
      });
  });
};
