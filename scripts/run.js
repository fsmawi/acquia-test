const exec = require('child_process').exec;
const request = require('superagent');
const path = require('path');
const sendNotification = require('./helper').sendNotification;

const branch = process.env.DEPLOY_VCS_PATH;
const appId = process.env.PIPELINE_APPLICATION_ID;
const jobId = process.env.PIPELINE_JOB_ID;

// get arguments
const step = process.argv.slice(2)[0];
const command = process.argv.slice(2)[1];
const directory = process.argv.slice(2)[2];

if (!command) {
  switch (step) {
    case 'start':
      return sendNotification('good', `Starting job ${jobId} for ${branch}.`);
    case 'end':
      return sendNotification('good', `Job ${jobId} for ${branch} successful.`);
    default:
      throw 'Unknown run step';
  }
} else {
  executeCommand();
}

/**
 * Execute a commande
 */
function executeCommand() {

  const intervalCmd = setInterval(() => {
    console.log(`Running ${step} ...`);
  }, 60000);

  exec(command, {
    cwd: (directory !== undefined) ? directory : path.join(__dirname, '..'),
    maxBuffer: 1024 * 1024
  }, (error, stdout) => {

    // keep colors
    console.log(stdout);
    clearTimeout(intervalCmd);

    if (error) {

      // send notification to slack
      sendNotification('danger', `Job ${jobId} for ${branch} failed.\nExecution step: ${step} \nCommand failed: ${command}\nhttps://cloud.acquia.com/app/develop/applications/${appId}/pipelines/jobs/${jobId}`)
        .then((res) => {
          console.log('Notification sent to slack channel');
        })
        .catch((err) => {
          console.log('Failed to sent notification to slack channel', err);
        })
        .then(() => {
          // keep colors
          console.error(error);
          process.exit(1);
        });
    }
  });
}
