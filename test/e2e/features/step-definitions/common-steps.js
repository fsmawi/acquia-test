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
      return this.browser._url(URL);
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

    return this.browser
      ._click(page.getDynamicValue(selector), {timeout: 60000})
      .then(() => this.browser.keys(page.getDynamicValue(value)))
      .then(() => this.browser.pause(1000));
  });

  this.When(/^I click on the \|(.*?)\| /, function (selector) {
    boostrap(this.browser);
    return this.browser._click(page.getDynamicValue(selector), {timeout: 10000});
  });

  this.Then(/^I should be navigated to \|(.*?)\|$/, function (expectedUrl) {
    boostrap(this.browser);

    if (process.env.PIPELINES_URL) {
      let sanitized = process.env.PIPELINES_URL
        .replace(/:(.*)@/, '://') 			 //replace any basic auth details found in url
        .replace(/^(http|https)\:\/\//, '') // remove the leading http:// or https:// (temporarily)
        .replace('/index.html#', '') 		// remove /index.html#
        .replace(/\/+/g, '/')       		// replace consecutive slashes with a single slash
        .replace(/(\/|\\)+$/, '');       		// remove trailing slashes

      PipelinesUnAuthUrl = 'https://' + sanitized + page.getDynamicValue(expectedUrl);
    }
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

  this.Then(/^I delete the browser cookies$/, function () {
    return this.browser._deleteCookies();
  });

  this.Then(/^I select \|(.*?)\| from \|(.*?)\|/, function (value, selector) {
    boostrap(this.browser);
    return this.browser._selectValue(page.getDynamicValue(selector), page.getDynamicValue(value));
  });

  this.Then(/^I select value \|(.*?)\| from \|(.*?)\|/, function (value, selector) {
    boostrap(this.browser);
    return this.browser._selectByVisibleText(page.getDynamicValue(selector), page.getDynamicValue(value));
  });

  this.Then(/^I should see \|(.*?)\| value in \|(.*?)\|/, function (expectedValue, selector) {
    boostrap(this.browser);
    return this.browser._waitUntil(page.getDynamicValue(selector), {timeout: 5000})
      .then(() => {
        return this.browser._getValue(page.getDynamicValue(selector));
      })
      .then((actualValue) => {
        expect(page.getDynamicValue(expectedValue)).to.be.equal(actualValue);
      });
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

  this.When(/^I should see a \|(.*?)\| contains \|(.*?)\|$/, function (textElementIdentifier, expectedText) {
    return this.browser._waitUntil(page.getDynamicValue(textElementIdentifier), {timeout: 10000})
      .then(() => {
        return this.browser._getText(page.getDynamicValue(textElementIdentifier));
      })
      .then((actualText) => {
        expect(actualText).to.contain(page.getDynamicValue(expectedText));
      });
  });

  this.When(/^I should see \|(.*?)\| window opened$/, function (pageURL) {
    let getWindowHandle = function (browser) {
      return browser.windowHandles()
        .then((handles) => {
          handles = handles.value;
          handle = (handles.length > 1) ? handles[1] : '';
          return handle;
        });
    };
    let switchToWindow = function (browser, handle) {
      return browser.window(handle);
    };
    let validateURL = function (browser) {
      return browser._checkUrl(page.getDynamicValue(pageURL));
    };

    return getWindowHandle(this.browser)
      .then((handle) => {
        return switchToWindow(this.browser, handle);
      })
      .then(validateURL(this.browser))
      .then(() => {
        return this.browser.close();
      });
  });

  this.Then(/^I should see \|(.*?)\| \|(.*?)\| containing \|(.*?)\|$/, function (labelIdentifier, attribute, attributeValue) {
    let getAttributeText = function (browser, labelIdentifier, attribute) {
      return browser._waitUntil(page.getDynamicValue(labelIdentifier), {timeout: 10000})
        .then(() => {
          return browser._getAttributeText(page.getDynamicValue(labelIdentifier), page.getDynamicValue(attribute));
        });
    };

    let getAttributeTextJS = function (browser, labelIdentifier, attribute) {
      console.log('unable to find attribute: "', page.getDynamicValue(attribute), '". Trying with javascript');
      return browser._getAttributeTextJS(page.getDynamicValue(labelIdentifier), page.getDynamicValue(attribute));
    };

    let validateTooltip = function (actualText) {
      actualText = actualText.replace(/\n(\s)+/g, '');
      expect(actualText).to.contain(page.getDynamicValue(attributeValue));
    };

    return getAttributeText(this.browser, labelIdentifier, attribute)
      .then((actualText) => {
        actualText = actualText != null ? actualText :
          getAttributeTextJS(this.browser, labelIdentifier, attribute);
        return actualText;
      })
      .then(validateTooltip);
  });

  this.Then(/^I hover on \|(.*?)\|/, function (hoverElement) {
    return this.browser.moveToObject(page.getDynamicValue(hoverElement));
  });

  this.Then(/^I should see a \|(.*?)\| scroller move \|(.*?)\| by at least \|(.*?)\|$/, function (scrollerType, moveDirection, points) {
    return this.browser.execute(function () {
      return [document.body.scrollTop, document.body.scrollLeft];
    }).then((scrollPos) => {
      if (page.getDynamicValue(scrollerType) == 'vertical' && page.getDynamicValue(moveDirection) === 'down')
        expect(scrollPos.value[0]).to.be.above(page.getDynamicValue(points));

      else if (page.getDynamicValue(scrollerType) == 'vertical' && page.getDynamicValue(scrollerType) == 'up')
        expect(scrollPos.value[0]).to.be.below(page.getDynamicValue(points));

      else if (page.getDynamicValue(scrollerType) == 'horizontal' && page.getDynamicValue(scrollerType) == 'right')
        expect(scrollPos.value[1]).to.be.above(page.getDynamicValue(points));

      else if (page.getDynamicValue(scrollerType) == 'horizontal' && page.getDynamicValue(scrollerType) == 'left')
        expect(scrollPos.value[1]).to.be.below(page.getDynamicValue(points));
    });
  });

  this.Then(/^I should see a \|(.*?)\| element contains \|(.*?)\|$/, function (element, textToFind) {
    return this.browser._getHTML(page.getDynamicValue(element))
      .then((actualHTML) => {
        expect(actualHTML).to.contain(page.getDynamicValue(textToFind));
      });
  });
  this.Then(/^I should not see the \|(.*?)\|/, function (selector) {
    boostrap(this.browser);
    selector = page.getDynamicValue(selector);
    return this.browser.isVisible(selector).then((isVisible) => {
      if (isVisible)
        throw new Error('the given selector', selector, 'should not be visible');
    });
  });
};
