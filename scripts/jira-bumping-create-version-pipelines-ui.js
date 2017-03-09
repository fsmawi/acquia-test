const request = require('superagent');
const exec = require('child_process').exec;
const moment = require('moment');
const USER = encodeURIComponent(process.env.JIRA_USER);
const PASSWORD = encodeURIComponent(process.env.JIRA_PASSWORD);

versionPatch()
  .then((version) => {
    // create version in jira
    createJiraVersion(version);
  })
  .catch((err) => console.log(err));

function versionPatch() {
  // execute npm version patch
  return new Promise((resolve, reject) => {
    exec(`npm version patch`, (error, stdout, stderr) => {
      if (error) {
        return reject(error);
      } else {
        let packageFile = require(__dirname + '/../package.json');
        return resolve(stdout.replace('v', '').replace(/\n/g, ''))
      }
    });
  });
}

function createJiraVersion(version) {
  return new Promise((resolve, reject) => {
    request.post(`https://${USER}:${PASSWORD}@backlog.acquia.com/rest/api/2/version`)
      .send({
        description: "pipelines",
        name: version,
        userReleaseDate: moment().format('DD/MMM/YYYY'),
        project: "pipelines UX",
        archived: false,
        released: false
      })
      .end((err, res) => {
        if (err) {
          console.log(err);
          return reject(err);
        } else {
          return resolve();
        }
      })
  })
}
