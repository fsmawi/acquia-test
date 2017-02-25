/**
 * Created by stephen.raghunath on 2/24/17.
 */

const path = require('path');
/**
 * It contains customized webdriverio functions; taken care of synchronisation
 * with pagetoload/elements existence/visibility etc.. and meaningful exception messages on
 * failure and then screenshot capture for better debugging
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
   */
  let createTimeName = (name, value) => `${+new Date()}__${name}__${value.replace(/(:|\/|\.|\[|\]|"|\=|@|#)/g, '')}`;

  /**
   * @return {Promise}
   * @param {String} name for the screenshot
   * take the screenshot of current screen and place it inside test-logs of current scenrio folder
   */
  let screenshot = function (name) {
    return new Promise(resolve => {
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
    let options = { pauseAfter: 5000 };
    Object.assign(options, params || {});
    return browser.url(url).pause(options.pauseAfter).then(() => screenshot(createTimeName('url', url)))
  };

  /**
   * @return {Promise}
   * @param {String} selector
   * @param {Array} params
   *  wait for element to be visible before performing a click
   */
  browser._click = function (selector, params) {
    let options = { timeout: 5000 };
    Object.assign(options, params || {});

    return browser.waitForVisible(selector, options.timeout)
      .then(() => screenshot(createTimeName('click', selector)))
      .click(selector)
      .pause(500)
      .catch(e => {
        return screenshot(createTimeName('click-error', selector))
          .then(() => Promise.reject(e));
      });
  };

  /**
   * @return {Promise}
   * @param {String} value in seconds/milliseconds
   * @param {String} format could be either one of second/seconds/millisecond/milliseconds
   * @param {}
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
    return browser.getUrl()
      .then(url => expect(url).to.equal(expectedUrl))
      .catch(e => {
        return screenshot(createTimeName('url-error', expectedUrl))
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
    let options = Object.assign({ timeout: 5000 }, params);
    return browser.isExistingWithTimeout(selector, options.timeout)
      .then(exists => {
        if (exists) {
          return Promise.resolve();
        } else {
          throw `${selector} did not exist`;
        }
      })
      .then(() => browser.pause(options.afterPause || 1000))
      .then(() => screenshot(createTimeName('exist', selector)))
      .catch(e => {
        return screenshot(createTimeName('exist-error', selector))
          .then(() => Promise.reject(e));
      });
  };

  browser._frameworkAttached = true;
  return browser;
};
