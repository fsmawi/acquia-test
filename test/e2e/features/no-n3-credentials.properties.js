// Define the feature level and scenario level properties here
module.exports = {
// feature scope
  'no-n3-creds-file': 'no-n3-creds.yml',
  'no-n3-dialog': '//app-confirmation-modal',
  'yes-button': '//button[text()="Yes"]',
  'no-button': '//button[text()="No"]',
  'api-token-title': '//span[text()="API Token"]',
  'job-list-link': '//a[text()=" Job list "]',
  'start-job-widget': '#start-job',
  'job-list-title': 'Job List',
  'repo-info-widget': '#repoInfo',
  'rerun-job-widget': '#rerun-job',
  'job-detail-forbidden-pipelines': '//a[text()="Job list"]',
  'flash-message-alert': '#flash-message',
  'no-n3-yes-flash-message-1': 'An API Token was created and linked with your user account and ' +
    'associated successfully with the application.',
  'no-n3-yes-flash-message-2': 'All Pipelines features have been enabled.',
  'no-n3-no-flash-message-1': 'By choosing to not create an API Token linked with your user account ' +
    'and associate it with your application, ',
  'no-n3-no-flash-message-2': 'you will not be able to run automated jobs ' +
    'triggered through webhooks or deploy build results to CDEs.',
};
