const request = require('superagent');
'use strict';

const accessToken = process.env.PIPELINES_SLACK_TOKEN;
const slackChannel = process.env.PIPELINES_SLACK_CHANNEL;

module.exports = {

  /**
   * Send sample notification to Slack
   * @param  color
   * @param  message
   * @return Promise
   */
  sendNotification: function (color, message) {
    return new Promise((resolve, reject) => {
      let url = `https://acquia.slack.com/services/hooks/jenkins-ci/${accessToken}`;
      request.post(url)
        .send({
          channel: slackChannel,
          attachments: [
            {
              text: message,
              color: color,
            },
          ],
        })
        .end((error, res) => {
          if (error) {
            return reject(error);
          } else {
            return resolve(res);
          }
        });
    });
  },
};
