let page = require('./page');

let githubFlowPage = Object.create(page, {

  /**
   * repositories modal dialog
   */
  repositoryModalDialog: {
    get: function () {
      return '.md-dialog-container';
    },
  },

  /**
   * filter repositories text field
   */
  filterRepositoriesTextField: {
    get: function () {
      return '.el-select-list__filter__input';
    },
  },

  /**
   * application-information page title
   */
  pageTitle: {
    get: function () {
      return 'h1.page-title';
    },
  },

  /**
   * @param {String} flashMsgId identifier of flash message
   * @param {String} flashMsgText expected flash Message
   * assert the content of flash message either success or failure
   */
  assertFlashMessage: {
    value: function (flashMsgId, flashMsgText) {
      return this.browser._waitUntil(flashMsgId, {timeout: 10000})
        .then(() =>
          this.browser._getText(flashMsgId)
            .then((actualFlashMsgText) => {
              expect(actualFlashMsgText).to.contain(flashMsgText);
            })
        );
    },
  },

  /**
   * assert that the repository selection modal dialog was closed
   */
  assertRepositoryModalWasClosed: {
    value: function () {
      return this.browser._notExists(this.repositoryModalDialog);
    },
  },

  /**
   * @param {String} identifier of repositories in the list
   * assert that the repository list is not empty
   */
  assertRepoListIsNotEmpty: {
    value: function (repositoryList) {
      return this.browser._exists(repositoryList).then(() => {
        this.browser.elements(repositoryList).then((response) => {
          expect(response.value).to.be.length.above(0);
        });
      });
    },
  },

  /**
   * @param {String} repositoryList identifier for repository-list
   * assert that the repository list is empty
   */
  assertRepoListIsEmpty: {
    value: function (repositoryList) {
      return this.browser._exists(this.filterRepositoriesTextField).then(() => {
        this.browser.elements(repositoryList).then((response) => {
          expect(response.value).to.have.lengthOf(0);
        });
      });
    },
  },
  /**
   * assert the title of github Application Information page
   */
  assertGithubApplicationPage: {
    value: function () {
      return this.browser._getText(this.pageTitle)
        .then((pageTitle) => {
          expect(pageTitle).to.be.equal('Application Information');
        });
    },
  },

  /**
   * @param {String} repoList identifier for elements in repo-list
   * @param {String} filterText repositories filtered with text
   * assert the filtered repositories list contains only the repositories matches with filterText
   */
  assertFilteredRepositoriesList: {
    value: function (repoList, filterText) {
      return this.browser._exists(repoList)
        .then(() => {
          return this.browser.elements(repoList);
        })
        .then((res) => Promise.all(res.value.map((r) => this.browser.elementIdText(r.ELEMENT))))
        .then((elems) => elems.map((e) => e.value))
        .then((repositoriesValues) => {
          for (let i in repositoriesValues) {
            if (repositoriesValues.hasOwnProperty(i)) {
              expect(repositoriesValues[i]).to.contain(filterText);
            }
          }
        });
    },
  },
});
module.exports = githubFlowPage;
