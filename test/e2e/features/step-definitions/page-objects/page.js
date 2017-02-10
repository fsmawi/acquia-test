/* eslint space-before-function-paren: off */
/**
 * This is the common page for any page class which contains generic function definitions
 */
class Page {
  /**
   * Default constructor initializing pagetitle and browser object to its defaults
   */
  constructor() {
    this.title = 'Base Page';
    this.browser = null;
  }
  /**
   * @param {object} browser propogated from steps js file
   * This sets the browser object to be used for all subsequent actions
   */
  setBrowser(browser) {
    this.browser = browser;
  }
  /**
   * @return {object} browser object
   * get the browser object
   */
  getBrowser() {
    return this.browser;
  }
  /**
   * @param {string} path subpath of the application baseurl
   * @return {object} browser object
   * navigate to sub url by appending subURL path to current browser location
   */
  openSubUrl(path) {
    return this.browser.url('/' + path);
  }
  /**
   * @param {string} path application baseurl
   * @return {object} browser object
   * open the browser with the given path as location
   */
  open(path) {
    return this.browser.url(path);
  }
  /**
   * @param {object} element whose value is to set
   * @param {string} value to set
   * @return {object} browser object
   * wait for text field to be visible, clear the content then sets the new value
   */
  setValue(element, value) {
    element.waitForVisible(3000);
    element.clearElement();
    return element.setValue(value).pause(1000);
  }
}
module.exports = new Page();
