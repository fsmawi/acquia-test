const exec = require('child_process').exec;
const request = require('superagent');
const path = require('path');

// Hipchat paramerters
const accessToken = process.env.PIPELINES_HIPCHAT_TOKEN;
const roomId = encodeURIComponent(process.env.PIPELINES_HIPCHAT_ROOM);
const branch = process.env.DEPLOY_VCS_PATH;
const appId = process.env.PIPELINE_APPLICATION_ID;
const jobId = process.env.PIPELINE_JOB_ID;

// get arguments
const step = process.argv.slice(2)[0];
const command = process.argv.slice(2)[1];
const directory = process.argv.slice(2)[2];

if(!command) {
  switch(step) {
    case 'start':
      return sendNotification('green', `Starting job for ${branch}.`);
    case 'end':
      return sendNotification('green', `Job for ${branch} successful.`);
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

  exec(command, {
    cwd: (directory !== undefined) ? directory : path.join(__dirname, '..'),
    maxBuffer: 1024 * 1024
  }, (error, stdout) => {

    // keep colors
    console.log(stdout);

    if (error) {

      // send notification to hipchat
      sendNotification('red', `Job for ${branch} failed.\nExecution step: ${step} \nCommand failed: ${command}\nhttps://cloud.acquia.com/app/develop/${appId}/pipelines/jobs/${jobId}`)
        .then((res) => {
          console.log('Notification sent to hipchat room');
        })
        .catch((err) => {
          console.log('Failed to sent notification to hipchat room', err);
        })
        .then(() => {
          // keep colors
          console.error(error);
          process.exit(1);
        });
    }
  });
}

/**
 * Send sample notification to Hipchat
 * @param  color
 * @param  message
 * @return Promise
 */
function sendNotification(color, message) {
  return new Promise((resolve, reject) => {
  let url = `https://acquia.hipchat.com/v2/room/${roomId}/notification?auth_token=${accessToken}`;
  request.post(url)
    .send({
      color: color,
      message_format: 'text',
      message: message,
    })
    .end((error, res) => {
      if (error) {
        return reject(error);
      } else {
        return resolve(res);
      }
    });
  });
}

