let page = require('./page-objects/page');
let loginPage = require('./page-objects/login.page');
let CONSTANTS = require('../../support/constants');

module.exports = function () {
  this.Given(/^I have navigated to "([^"]*)"$/, function (pagePath) {
    page.setBrowser(this.browser);
    let URL = null;
    // if PIPELINES_URL is set in environment then considering as it requires basic auth
    if (process.env.PIPELINES_URL) {
      URL = process.env.BASIC_AUTH_USER + ':' + process.env.BASIC_AUTH_PASS;
      if (process.env.PIPELINES_URL.startsWith('http://')) {
        URL = 'http://' + URL + '@' + process.env.PIPELINES_URL.replace('http://', '');
      }
      else if (process.env.PIPELINES_URL.startsWith('https://')) {
        URL = 'https://' + URL + '@' + process.env.PIPELINES_URL.replace('https://', '');
      }
    }
    else
      URL = CONSTANTS.PIPELINES_URL;
    return page.open.call(this, URL + pagePath);
  });

  this.Given(/^jobs yml file "([^"]*)"$/, function (jobsYmlFile) {
    let URL = CONSTANTS.PIPELINES_URL;
    if (process.env.PIPELINES_URL) {
      URL = process.env.PIPELINES_URL;
    }
    return page.setJobsYmlFile(jobsYmlFile, URL.replace('/index.html/', '') + '/mock/header')
      .pause(5000)
      .then(() => loginPage.setAppId('1'))
      .then(() => loginPage.setApiToken('2'))
      .then(() => loginPage.setApiSecret('3'))
      .then(() => loginPage.doSignIn())
      .then(() => this.browser.pause(5000));
  });

  this.When(/^I enter APP_ID "([^"]*)"$/, function (appId) {
    return loginPage.setAppId(appId);
  });

  this.When(/^API_TOKEN "([^"]*)"$/, function (arg) {
    if (process.env.N3_KEY == undefined) {
      console.error('please set the environment variable N3_KEY before running the test');
      return process.exit(1);
    }
    return loginPage.setApiToken(process.env.N3_KEY);
  });

  this.When(/^API_SECRET "([^"]*)"$/, function (arg) {
    if (process.env.N3_SECRET == undefined) {
      console.error('please set the environment variable N3_SECRET before running the test');
      return process.exit(1);
    }
    return loginPage.setApiSecret(process.env.N3_SECRET);
  });

  this.When(/^BETA_ACCESS_CODE "([^"]*)"$/, function (betaAccess) {
    return loginPage.setBetaAccessCode(betaAccess);
  });

  this.When(/^I Click on "([^"]*)" Button$/, function (arg) {
    return loginPage.doSignIn();
  });
};
