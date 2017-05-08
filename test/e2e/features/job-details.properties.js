/**
 * Created by stephen.raghunath on 2/24/17.
 */
// Define the feature level and scenario level properties here
module.exports = {
  // feature scope
  'jobs-list-table': '//e-card[//h4/span[text()="{0}"]]//e-card-content//app-job-list',
  'jobId-link': '//app-job-summary//div/a',
  'jobs': '//a[text()="example/repo"]',
  'job-logs': 'e-card#logs',
  'progress-bar': '.el-progress__loader__dot__inner',
  'empty-logs': '//pre[@class="logs" and contains(text(),"There are no logs for this job.")]',
  'count-up-time': '//e-data-label[div[span[contains(text(),"Duration")]]]/following-sibling::e-data-value',
  'commit-value': '#job-commit-value',
  'job-duration': '#job-duration-finished-value',
  'job-trigger': '#job-trigger-value',
  'job-branch': '#job-branch-value',
  'job-requested-by': '#job-requested-by-value',
  'job-duration-value': 'a few seconds',
  'job-trigger-value': 'Manual',
  'job-branch-value': 'MS-1170',
  'job-requested-by-value': 'admin@example.com'
};
