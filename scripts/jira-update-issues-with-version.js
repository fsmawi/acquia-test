const request = require('superagent');
const exec = require('child_process').exec;
const USER = encodeURIComponent(process.env.JIRA_USER);
const PASSWORD = encodeURIComponent(process.env.JIRA_PASSWORD);

getIssueIdFromGitLog()
  .then((issueId) => {
    issueId = issueId.replace(":", "");
    console.log(issueId);
    // create version in jira
    updateIssueWithVersion(issueId);
  })
  .catch((err) => console.log(err));

/**
 * fetch the latest git commit summary info. and extract JIRA issue Id from it
 */
function getIssueIdFromGitLog() {
  return new Promise((resolve, reject) => {
    exec('git log -1 --pretty=%B', (error, stdout, stderr) => {
      if (error) {
        return reject(error);
      } else {
        stdout = stdout.replace(/[\r\n]/g, "");
        console.log('git commit history is:', stdout);
        console.log('matched issueId: ', stdout.match(/\w+-([\d+])+:/g)[0]);
        return resolve(stdout.match(/\w+-([\d+])+:/g)[0]);
      }
    });
  });
}

/**
 * Update the JIRA issueId with the latest version found in package.json file
 */
function updateIssueWithVersion(issueId) {
  console.log('updating the issue id:', issueId);
  return new Promise((resolve, reject) => {
    let packageJson = require(__dirname + '../package.json');
    let url = 'http://${USER}:${PASSWORD}@backlog.acquia.com/rest/api/2/issue/' + issueId;
    request.put(url)
      .send({
        update: {
          fixVersions: [
            {
              set:
              [
                { name: packageJson.version }
              ]
            }
          ]
        }
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
