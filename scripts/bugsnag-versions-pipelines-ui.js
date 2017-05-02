const request = require('superagent');
const colors = require('colors');

const packageJson = require(__dirname + '/../package.json');

// Send Deploy information to bugsnag
request
  .post(`https://notify.bugsnag.com/deploy`)
  .send({
    apiKey: process.env.API_KEY,
    appVersion: packageJson.version
  })
  .end((err, res) => {
    if (err) {
      console.log(`Bugsnag : unable to send deployment information`.red);
      throw err;
    }
    console.log(`Bugsnag : deployment information sent ${packageJson.version}`.green);
  });
