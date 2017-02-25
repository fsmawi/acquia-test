/**
 * Created by stephen.raghunath on 2/24/17.
 */

const boostrap = require('../support/core').bootstrap;
let page = require('./page-objects/page');

module.exports = function () {

  this.Given(/^I visit( the)? \|(.*?)\|/, function (theOperator, pageIdentifier) {
    boostrap(this.browser);
    return this.browser._url(page.getDynamicValue(pageIdentifier));
  });

  this.When(/^I enter \|(.*?)\| in the \|(.*?)\|/, function (value, selector) {
    boostrap(this.browser);
    return this.browser._click(page.getDynamicValue(selector)).keys(page.getDynamicValue(value));
  });

  this.When(/^I click on the \|(.*?)\|/, function (selector) {
    boostrap(this.browser);
    return this.browser._click(page.getDynamicValue(selector));
  });

  this.Then(/^I should be navigated to \|(.*?)\|$/, function (expectedUrl) {
    boostrap(this.browser);
    return this.browser._checkUrl(page.getDynamicValue(expectedUrl));
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
