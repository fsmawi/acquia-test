/**
 * Created by vamsi.dharmavarapu on 4/14/17.
 */

// Define the feature level and scenario level properties here
module.exports = {
  // feature scope
  'pipelines-unauthenticated-url': '/auth/tokens',
  'mock-header': '/mock/header',
  'jobs-yml-file-name': 'jobs.yml',
  'no-jobs-yml-file-name': 'no-jobs.yml',
  'save': 'button.mat-primary',
  'header-value': 'input[name="headerValue"]',
  'app-id': '58bb63dd-57db-4d50-9a49-6b60d5921d14',
  'app-input': '[name="AppId"]',
  'sign-in': 'button.mat-primary',
  'login': '#edit-submit-user-login',
  'job-detail': '//a[text()="Jobs "]',
  'jobs-list': '.el-card__title',
  'jobs-list-table': '//e-card[//h4/span[text()="{0}"]]//e-card-content//app-job-list',
  'jobId-link': '//app-job-summary//div/a',
  'jobs': '//a[text()="Jobs "]',
  'job-logs': 'e-card#logs',
  'progress-bar': '.el-progress__loader__dot__inner',
  'empty-logs': '//pre[@class="logs" and contains(text(),"There are no logs for this job.")]',
  'count-up-time': '//e-data-label[div[span[contains(text(),"Duration")]]]/following-sibling::e-data-value',
  'deployment-link-value' : '#job-deployment-link-value.el-data__value',
  'commit-value' : '#job-commit-value.el-data__value',
  'duration-value' : '#job-duration-value.el-data__value',
  'started-at-value' : '#job-started-at-value.el-data__value',
  'trigger-value' : '#job-trigger-value.el-data__value',
  'pull-request-value' : 'j#ob-pull-request-value.el-data__value',
  'branch-value' : '#job-branch-value.el-data__value',
  'source-branch-value' : '#job-source-branch-value.el-data__value',
  'target-branch-value' : '#job-target-branch-value.el-data__value',
  'requested-by-value' : '#job-requested-by-value.el-data__value'

};
