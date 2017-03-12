/**
 * Created by stephen.raghunath on 2/24/17.
 */

// Define the feature level and scenario level properties here
module.exports = {
  // feature scope
  'pipelines-unauthenticated-url': '/auth/tokens',
  'mock-header': '/mock/header',
  'jobs-yml-file-name': 'jobs.yml',
  'no-jobs-yml-file-name': 'no-jobs.yml',
  'save': 'button.md-primary',
  'header-value': 'input[name="headerValue"]',
  'app-id': '58bb63dd-57db-4d50-9a49-6b60d5921d14',
  'app-input': '[name="AppId"]',
  'sign-in': 'button.md-primary',
  'login': '#edit-submit-user-login',
  'job-detail': '//a[text()="Jobs "]',
  'jobs-list': '.el-card__title',
  'jobs-list-table': '//e-card[//h4/span[text()="{0}"]]//e-card-content//app-job-list',
  'jobId-link': '//app-job-summary//div/a',
  'jobs': '//a[text()="Jobs "]',
  'job-logs': 'e-card#logs',
  'progress-bar': '//e-card//md-progress-bar[@role="progressbar"]',
  'empty-logs': '//i[contains(text(),"There are no logs for this job.")]',
  'count-up-time': '//e-data-label[div[span[contains(text(),"Duration")]]]/following-sibling::e-data-value',
};
