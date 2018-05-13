/**
 * Created by stephen.raghunath on 2/24/17.
 */

const path = require('path');
/**
 * It contains customized webdriverio functions; taken care of synchronisation
 * with pagetoload/elements existence/visibility etc.. and meaningful exception messages on
 * failure and then screenshot capture for better debugging
 * @param  {any} browser
 * @return {any}
 */
exports.bootstrap = function (browser) {
  if (browser._frameworkAttached) {
    return browser;
  }

  // Helpers
  /**
   * @param {String} name of the action
   * @param {String} value for the action
   * create a time+name+value combined string to be used as a name to screenshot file
   * @return {String}
   */
  let createTimeName = (name, value) => `${+new Date()}__${name}__${value.replace(/(:|\/|\.|\[|\]|"|\=|@|#)/g, '')}`;

  /**
   * @return {Promise}
   * @param {String} name for the screenshot
   * take the screenshot of current screen and place it inside test-logs of current scenrio folder
   */
  let screenshot = function (name) {
    return new Promise((resolve) => {
      if (process.env.AQTEST_DEBUG) {
        browser.saveScreenshot(path.join(global.LOG_PATH || './', global.CURRENT_SCENARIO_FOLDER || '', `${name}.png`));
      }
      resolve();
    });
  };

  /**
   * @return {Promise}
   * @param {String} url
   * @param {Array} params
   * open the url on the browser and pause the browser for 5 secs for the URL to load
   */
  browser._url = function (url, params) {
    let options = {pauseAfter: 5000};
    Object.assign(options, params || {});
    return browser.url(url)
      .then(() => browser.pause(options.pauseAfter))
      .then(() => screenshot(createTimeName('url', url)));
  };

  /**
   * @return {Promise}
   * @param {String} selector
   * @param {Array} params
   *  wait for element to be visible before performing a click
   */
  browser._click = function (selector, params) {
    let options = {timeout: 5000};
    Object.assign(options, params || {});
    return browser.waitForVisible(selector, options.timeout)
      .then(() => {
        return browser.execute(function(selector) {
          "use strict";
          try {
            // dom selector
            document.querySelector(selector).scrollIntoView(false);
          } catch(e) {
            // xpath selector
            let element = document.evaluate(selector, document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null);
            if (element && element.singleNodeValue) {
              element.singleNodeValue.scrollIntoView(false);
            }
          }

        }, selector);
      })
      .then(() => screenshot(createTimeName('click', selector)))
      .click(selector)
      .pause(500)
      .catch((e) => {
        if (e.message.includes('Other element would receive the click')) {
          return browser._wait(10, 'seconds', 'waiting for the element to load completely')
            .then(() => { return browser.click(selector).pause(500) });
        }
        return screenshot(createTimeName('click-error', selector))
          .then(() => Promise.reject(e));
      });
  };

  /**
   * @return {Promise}
   * @param {String} value in seconds/milliseconds
   * @param {String} format could be either one of second/seconds/millisecond/milliseconds
   * @param {String} message to append to screenshot file name
   * pause the browser for given {value} milliseconds
   */
  browser._wait = function (value, format, message) {
    let ms;
    switch (format) {
      case 'seconds':
      case 'second':
        ms = +value * 1000;
        break;
      case 'milliseconds':
      case 'millisecond':
        ms = +value;
        break;
    }
    return browser.pause(ms).then(() => screenshot(createTimeName('wait', message)));
  };

  /**
   * @return {Promise}
   * @param {String} expectedUrl
   * assert that current browser url should be equal to given {expectedUrl}  On exception
   * take the screenshot
   */
  browser._checkUrl = function (expectedUrl) {
    return browser.waitUntil(function () {
      return browser.getUrl()
        .then((url) => {
          url = url.replace('http://', 'https://');
          expectedUrl = expectedUrl.replace('http://', 'https://');
          return expect(url).to.be.equal(expectedUrl);
        });
    }, 15000, 'expected url not found after 15 secs')
      .catch((e) => {
        return screenshot(createTimeName('get-url-error', expectedUrl))
          .then(() => Promise.reject(e));
      });
  };

  /**
   * @return {Promise}
   * @param {String} selector of the element to find
   * @param {Array} params
   * check that the element identified by given selector exists on the page
   */
  browser._exists = function (selector, params) {
    let options = Object.assign({timeout: 5000}, params);
    return browser.isExistingWithTimeout(selector, options.timeout)
      .then((exists) => {
        if (exists) {
          return Promise.resolve();
        } else {
          throw new Error(`${selector} did not exist`);
        }
      })
      .then(() => browser.pause(options.afterPause || 1000))
      .then(() => screenshot(createTimeName('exist', selector)))
      .catch((e) => {
        return screenshot(createTimeName('exist-error', selector))
          .then(() => Promise.reject(e));
      });
  };

  /**
   * @param {String} selector
   * @param {Array} params contains optional timeout parameter
   * @return {Promise}
   * wait until the given selector is visible
   */
  browser._waitUntil = function (selector, params) {
    let options = Object.assign({timeout: 5000}, params);
    return browser.waitForVisible(selector, options.timeout)
      .then(() => screenshot(createTimeName('waitUntil', selector)))
      .catch((e) => {
        return screenshot(createTimeName('waitUntil-error', selector))
          .then(() => Promise.reject(e));
      });
  };

  /**
   * Deletes the browser cookies
   */
  browser._deleteCookies = function() {
    return browser.deleteCookie()
      .catch((e) => {
        return screenshot(createTimeName('deleteCookies-error', ''))
          .then(() => Promise.reject(e));
      });
  };

  /**
   * Selects the value from the list box
   */
  browser._selectValue = function(selector, value) {
    return browser.selectByValue(selector, value)
      .catch((e) => {
        return screenshot(createTimeName('selectValue-error', ''))
          .then(() => Promise.reject(e));
      });
  };

  /**
   * Selects the visible value from the list box
   */
  browser._selectByVisibleText = function(selector, value) {
    return browser.selectByVisibleText(selector, value)
      .catch((e) => {
        return screenshot(createTimeName('selectValue-error', ''))
          .then(() => Promise.reject(e));
      });
  };

  /**
   * @param {String} selector
   * Find the iframe with given selector and switch the frame
   * @return {Promise}
   */
  browser._switchFrame = function (selector) {
    return browser.element(selector)
      .then((el) => {
        return browser.frame(el.value);
      })
      .catch((e) => {
        return screenshot(createTimeName('switchFrame-error', selector))
          .then(() => Promise.reject(e));
      });
  };

  /**
   * @return {String} text of the element
   * @param {String} selector identifier of an element
   * get the text of an element identified by given selectors
   */
  browser._getText = function (selector) {
    return browser.getText(selector)
      .catch((e) => {
        return screenshot(createTimeName('getText-error', selector))
          .then(() => Promise.reject(e));
      });
  };

  /**
   * @return {String} value of the element
   * @param {String} selector identifier of an element
   * get the value of an element identified by given selectors
   */
  browser._getValue = function (selector) {
    return browser.getValue(selector)
      .catch((e) => {
        return screenshot(createTimeName('getValue-error', selector))
          .then(() => Promise.reject(e));
    });
  };

  /**
  * @return {Promise}
  * @param {String} selector of the element to find
  * @param {Array} params
  * check that the element identified by given selector exists on the page
  */
  browser._notExists = function (selector, params) {
    let options = Object.assign({timeout: 5000}, params);
    return browser.isExistingWithTimeout(selector, options.timeout)
      .then((exists) => {
        if (exists) {
          throw new Error(`${selector} should not exist`);
        } else {
          Promise.resolve();
        }
      })
      .then(() => browser.pause(options.afterPause || 1000))
      .then(() => screenshot(createTimeName('notExists', selector)))
      .catch((e) => {
        return screenshot(createTimeName('notExists-error', selector))
          .then(() => Promise.reject(e));
      });
  };

  /**
  * @return {String} text of an {attribute}
  * @param {String} selector of the element to find
  * @param {String} attribute whose value has to be returned
  * get the value of {attribute} inside an element identified by {selector}
  */
  browser._getAttributeText = function (selector, attribute) {
    return browser.getAttribute(selector, attribute)
      .catch((e) => {
        return screenshot(createTimeName('getAttributeText-error', selector))
          .then(() => Promise.reject(e));
      });
  };

  /**
  * @return {String} text of an {attribute}
  * @param {String} selector of the element to find
  * @param {String} attribute whose value has to be returned
  * get the value of {attribute} inside an element identified by {selector} through javascript
  */
  browser._getAttributeTextJS = function (selector, attribute) {
    return browser.execute(function (selector, attribute) {
      text = document.querySelector(selector).getAttribute(attribute);
      return text;
    }, selector, attribute)
      .then((actualAttributeText) => {
        return actualAttributeText.value;
      })
      .catch((e) => {
        return screenshot(createTimeName('getAttributeTextJS-error', selector))
          .then(() => Promise.reject(e));
      });
  };

  /**
  * @return {String} text of an {attribute}
  * @param {String} selector of the element to find
  * Return an elements HTML string for matching
  */
  browser._getHTML = function (selector) {
    return browser.getHTML(selector)
      .catch((e) => {
        return screenshot(createTimeName('getHTML-error', selector))
          .then(() => Promise.reject(e));
      });
  }

  browser._frameworkAttached = true;
  return browser;
};
