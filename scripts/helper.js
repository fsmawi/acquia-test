const request = require('superagent');
'use strict';

const accessToken = process.env.PIPELINES_HIPCHAT_TOKEN;
const roomId = encodeURIComponent(process.env.PIPELINES_HIPCHAT_ROOM);

module.exports = {

  /**
   * Send sample notification to Hipchat
   * @param  color
   * @param  message
   * @return Promise
   */
  sendNotification: function (color, message) {
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
  },
};
