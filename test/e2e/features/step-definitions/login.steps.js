const boostrap = require('../support/core').bootstrap;
let page = require('./page-objects/page');
let loginPage = require('./page-objects/login.page');

module.exports = function () {
  this.Given(/^I set jobs yml file "([^"]*)"$/, function (jobsYmlFile) {
    boostrap(this.browser);
    return page.setJobsYmlFile(jobsYmlFile)
      .pause(5000);
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
