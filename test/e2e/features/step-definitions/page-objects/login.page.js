let page = require('./page');

let LoginPage = Object.create(page, {

  // page elements
  appId: {
    get: function () {
      return this.browser.element('input[name="AppId"]');
    },
  },
  apiToken: {
    get: function () {
      return this.browser.element('input[name="N3AccessKey"]');
    },
  },
  apiSecret: {
    get: function () {
      return this.browser.element('input[name="N3Secret"]');
    },
  },
  betaAccessCode: {
    get: function () {
      return this.browser.element('input[name="BetaAccessCode"]');
    },
  },
  signIn: {
    get: function () {
      return this.browser.element('.md-primary');
    },
  },

  // method definitions
  /**
   * @param {String} appIdValue
   * sets the value for appId text field
   */
  setAppId: {
    value: function (appIdValue) {
      return page.setValue(this.appId, appIdValue);
    },
  },
  /**
   * @param {String} apiTokenValue
   * * sets the value for n3Token text field
   */
  setApiToken: {
    value: function (apiTokenValue) {
      return page.setValue(this.apiToken, apiTokenValue);
    },
  },
  /**
   * @param {String} apiSecretValue
   * * sets the value for n3Secret text field
   */
  setApiSecret: {
    value: function (apiSecretValue) {
      return page.setValue(this.apiSecret, apiSecretValue);
    },
  },
  /**
   * @param {String} betaAccessCodeValue
   * sets the value for betaAccessCode text field
   */
  setBetaAccessCode: {
    value: function (betaAccessCodeValue) {
      return page.setValue(this.betaAccessCode, betaAccessCodeValue);
    },
  },
  /**
   * clicks on signIn button
   */
  doSignIn: {
    value: function () {
      return this.signIn.submitForm();
    },
  },

});
module.exports = LoginPage;
