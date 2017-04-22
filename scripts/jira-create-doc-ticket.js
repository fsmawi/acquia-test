const request = require('superagent');
const fs = require('fs');
const USER = encodeURIComponent(process.env.JIRA_USER);
const PASSWORD = encodeURIComponent(process.env.JIRA_PASSWORD);
const j2m = require('jira2md');
const packageJson = require(__dirname + '/../package.json');
let versionId = packageJson.version;
let description = "Creating of an issue using project keys and issue type names using the REST API";


description = getChangeLog();
description = 'Please update release documentation for Pipelines UX using the following: \n\n' + description;
console.log(description);
createDocTicket(description, versionId);

function getChangeLog() {
  const regex = /##([\s\S]*?)(<a)/gm;
  let str = fs.readFileSync('CHANGELOG.md', 'utf8');
  let versionText = str.match(regex);
  if (versionText != null)
    versionText[0] = versionText[0].replace('<a', '');
  return versionText ? j2m.to_jira(versionText[0]) : 'See https://github.com/acquia/pipelines-ui/blob/master/CHANGELOG.md';
};

/**
 * Create new doc ticket in Jira
 */
function createDocTicket(description, versionId) {
  return new Promise((resolve, reject) => {
    request.post(`https://${USER}:${PASSWORD}@backlog.acquia.com/rest/api/2/issue`)
      .send({
        "fields": {
          "project":
          {
            "key": "DOC"
          },
          "summary": "Prepare Pipelines UX Release: " + versionId,
          "description": description,
          "issuetype": {
            "name": "Story"
          }
        }
      })
      .end((err, res) => {
        if (err) {
          return reject(err);
        } else {
          return resolve();
        }
      });
  });
};
