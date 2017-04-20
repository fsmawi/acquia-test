const request = require('superagent');
const exec = require('child_process').exec;
const moment = require('moment');
const USER = process.env.JIRA_USER;
const PASSWORD = process.env.JIRA_PASSWORD;

const packageJson = require(__dirname + '/../package.json');
var versionId = packageJson.version; // old version

// 1 - Bump the version
// 3 - Get issues resolved since last version
// 4 - Update issues with the new vesion id
versionPatch()
  .then((version) => createJiraVersion(version))
  .then((version) => {
    versionId = version; // new version
    return getVersionIssues(packageJson.version);
  })
  .then((issues) => Promise.all(issues.map(issue => updateIssueWithVersion(issue, versionId))))
  .then(() => console.log('Issues updated'))
  .catch((e) => {
    console.error(e);
    process.exit(1);
  });

/**
 * Bump the version
 */
function versionPatch() {
  // execute npm version patch
  return new Promise((resolve, reject) => {
    exec(`npm version patch`, (error, stdout, stderr) => {
      if (error) {
        return reject(error);
      } else {
        return resolve(stdout.replace('v', '').replace(/\n/g, ''))
      }
    });
  });
}

/**
 * Create new version in Jira
 */
function createJiraVersion(version) {
  console.log('new version : ' + version);
  return new Promise((resolve, reject) => {
    request.post(`https://backlog.acquia.com/rest/api/2/version`)
      .auth(USER, PASSWORD)
      .send({
        description: "pipelines",
        name: `pipelines-ui-${version}`,
        userReleaseDate: moment().format('DD/MMM/YYYY'),
        project: "MS",
        archived: false,
        released: false
      })
      .end((err, res) => {
        if (err) {
          return reject(err);
        } else {
          return resolve(version);
        }
      })
  })
}

/**
 * Get all issue number resolved since given version
 */
function getVersionIssues(versionId) {
  return new Promise((resolve, reject) => {
    exec('git --no-pager log -100', (error, stdout, stderr) => {
      if (error) {
        return reject(error);
      } else {
        const search = stdout.match(versionId);
        const tmp = stdout.substring(0, search.index).match(/MS-\d{4,}/g) || [];

        let issues = [];
        for (var i = 0; i < tmp.length; i++) {
          if (issues.indexOf(tmp[i]) == -1) {
            issues.push(tmp[i]);
          }
        }
        console.log(issues);
        return resolve(issues);
      }
    });
  });
}

/**
 * Update the JIRA issue with the given version
 */
function updateIssueWithVersion(issueId, version) {
  console.log('updating the issue id:', issueId);
  return new Promise((resolve, reject) => {
    let url = `https://backlog.acquia.com/rest/api/2/issue/` + issueId;
    request.put(url)
      .auth(USER, PASSWORD)
      .send({
        update: {
          fixVersions: [{
            set: [{
              name: `pipelines-ui-${version}`
            }]
          }]
        }
      })
      .end((err, res) => {
        if (err) {
          return reject(err);
        } else {
          return resolve();
        }
      })
  })
}
