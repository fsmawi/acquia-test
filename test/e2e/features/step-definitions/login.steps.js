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

  this.When(/^I enter APP_ID "([^"]*)"$/, function (appId) {
    return loginPage.setAppId(appId);
  });

  this.When(/^API_TOKEN "([^"]*)"$/, function (n3Token) {
    if (process.env.n3token == undefined) {
      console.log('please set the environment variable n3token before running the test');
      return;
    }
    return loginPage.setApiToken(process.env.n3token);
  });

  this.When(/^API_SECRET "([^"]*)"$/, function (n3Secret) {
    if (process.env.n3secret == undefined) {
      console.log('please set the environment variable n3secret before running the test');
      return;
    }
    return loginPage.setApiSecret(process.env.n3secret);
  });

  this.When(/^BETA_ACCESS_CODE "([^"]*)"$/, function (betaAccess) {
    return loginPage.setBetaAccessCode(betaAccess);
  });

  this.When(/^I Click on "([^"]*)" Button$/, function (signIn) {
    return loginPage.doSignIn();
  });
};
