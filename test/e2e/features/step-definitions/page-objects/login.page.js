let page = require('./page');

let LoginPage = Object.create(page, {

  // page elements
  /**
   * App ID input element
   */
  appId: {
    get: function () {
      return this.browser.element('input[name="AppId"]');
    },
  },

  /**
   * API Token input element
   */
  apiToken: {
    get: function () {
      return this.browser.element('input[name="N3AccessKey"]');
    },
  },

  /**
   * API Secret Input element
   */
  apiSecret: {
    get: function () {
      return this.browser.element('input[name="N3Secret"]');
    },
  },

  /**
   * Access Code input element
   */
  betaAccessCode: {
    get: function () {
      return this.browser.element('input[name="BetaAccessCode"]');
    },
  },

  /**
   * Sign in input element
   */
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
