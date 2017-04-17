const request = require('superagent');
const semver = require('semver');
const colors = require('colors');
const sendNotification = require('./helper').sendNotification;

const USER = process.env.JIRA_USER;
const PASSWORD = process.env.JIRA_PASSWORD;

const packageJson = require(__dirname + '/../package.json');

// List all unreleased version
request
  .get(`https://backlog.acquia.com/rest/api/2/project/MS/versions`)
  .auth(USER, PASSWORD)
  .end((err, res) => {
    if (err) {
      throw err;
    }

    // look for semvers less than the current package.json.version
    // filter by us and non-released
    let versions = res.body.filter(version => {
      return version.name.match('pipelines-ui')
        && version.released === false
        && semver.lte(version.name.match(/([0-9]+\.[0-9]+\.[0-9]+)/)[0], packageJson.version);
    });

    // Update all versions
    Promise.all(versions.map(version => {
      return new Promise((resolve, reject) => {
        console.log('update version : ' + version.name);
        request.put(`https://backlog.acquia.com/rest/api/2/version/${version.id}`)
          .auth(USER, PASSWORD)
          .send({
            released: true
          })
          .end((err, res) => {
            if (err) {
              console.error(err);
              return reject(err);
            } else {
              return resolve();
            }
          });
      });
    }))
      .then(() => {
        console.log('All applicable versions released'.green, versions.map(v => v.name).join(', '));
        sendNotification('green', `Pipelines UI version ${packageJson.version} released`);
      })
      .catch(e => {
        throw err;
      });
  });
