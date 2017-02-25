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

  /**
   * @param {String} jobsYmlFile yml file
   * @param {String} URL for mock header
   * @return {browser} object
   * set yaml file from which app reads the jobs to display
   */
  setJobsYmlFile(jobsYmlFile, URL) {
    // This log message is intentional to make sure that which URL we are browsing
    console.log('navigating to URL: ', URL);
    return this.browser.url(URL).pause(10000)
      .waitForVisible('input[name="headerValue"]')
      .setValue('input[name="headerValue"]', jobsYmlFile)
      .submitForm('.md-primary');
  }

  /**
   * @return {String} value of a property item
   * @param {String} item a property identifier
   * find the property in feature properties js file or else in environment variables
   */
  getDynamicValue(item) {
    let placeholder = item.substring(1);
    let newValue;
    try {
      if (item[0] === '*') {
        /* Priority
         1. Scenario scope
         2. Feature scope
         3. global scope
         4. Environment scope or value itself
         */
        if (global.currentScenario.properties && global.currentScenario.properties[placeholder]) {
          newValue = global.currentScenario.properties[placeholder];
        } else if (global.currentFeature.properties && global.currentFeature.properties[placeholder]) {
          newValue = global.currentFeature.properties[placeholder];
        } else if (global.currentRun.properties && global.currentRun.properties[placeholder]) {
          newValue = global.currentRun.properties[placeholder];
        } else {
          newValue = process.env[placeholder] || item;
        }
        return newValue;
      }
    } catch (e) {
      console.error('Failed to find Dynamic value for property: ' + placeholder, e);
    }
    return item;
  }
};
module.exports = new Page();
