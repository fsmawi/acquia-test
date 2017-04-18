const AcquiaHttpHmac = require('http-hmac-javascript');
const XMLHttpRequest = require('xmlhttprequest').XMLHttpRequest;
const colors = require('colors');

// Get env variables
const APP_ID = process.env.PIPELINE_APPLICATION_ID;
const REALM = process.env.PIPELINE_CLOUD_REALM;
const ENV = process.env.PIPELINES_DEPLOYMENT_NAME;
const KEY = process.env.N3_KEY;
const SECRET = process.env.N3_SECRET;

let signed_headers = {}, content_type = 'application/json';

// Create HMAC instance.
let hmac_config = {
  realm: REALM,
  public_key: KEY,
  secret_key: SECRET
};

const HMAC = new AcquiaHttpHmac(hmac_config);

var envId;

getEnvironmentId()
  .then((env) => {
    envId = env;
    console.log(`Environment ID :` + `${envId}`.cyan);
    return getDomains(envId);
  })
  .then((domains) => {
    return Promise.all(domains.map((domain) => {
      return clearCache(envId, domain.name);
    }));
  })
  .then((res) => {
    console.log(`All caches cleared`.green);
  })
  .catch((err) => console.log(err));

/**
 * Get the current environment id
 * @return {Promise}
 */
function getEnvironmentId() {
  console.log(`Getting current Environment ID`.gray);
  let path = `https://cloud.acquia.com/api/applications/${APP_ID}/environments`;
  return new Promise((resolve, reject) => {

    xhr2promise(path, 'GET', 200)
      .then((res) => {
        // filter result using ENV name
        let envs = res._embedded.items.map((item) => {
          return {id: item.id, label: item.label, name: item.name}
        }).filter((item) => {
          return item.name == ENV;
        });

        // return first matching result or reject
        if (envs.length) {
          return resolve(envs[0].id);
        } else {
          return reject('No environment found');
        }
      })
      .catch((err) => {
        return reject(err);
      });
  });
}


/**
 * Get all domains for the given environment id
 * @return {Promise}
 */
function getDomains(envId) {
  console.log(`Getting all domains for current Environment`.gray);
  return new Promise((resolve, reject) => {

    let path = `https://cloud.acquia.com/api/environments/${envId}/domains`;

    xhr2promise(path, 'GET', 200)
      .then((res) => {

        let domains = res._embedded.items.map((item) => {
          return {name: item.hostname}
        });

        domains.map((domain) => console.log(`${domain.name}`.cyan));

        if (domains.length) {
          return resolve(domains);
        } else {
          return reject('No domains found');
        }

      })
      .catch((err) => {
        return reject(err);
      });
  });
}

/**
 * Clear the Varnish cache for teh given domain
 * @return {Promise}
 */
function clearCache(envId, domain) {

  console.log(`Clearing cache for domain :`.gray +`${domain}`);

  let path = `https://cloud.acquia.com/api/environments/${envId}/domains/${domain}/actions/clear-varnish`;
  return xhr2promise(path, 'POST', 202, true);
}

/**
 * Make an XMLHttpRequest request and return a Promise
 * @param  path
 * @param  method
 * @param  status
 * @return {Promise}
 */
function xhr2promise(path, method, status) {
  return new Promise((resolve, reject) => {

    let request = new XMLHttpRequest();

    request.onreadystatechange = () => {

      if (request.readyState === 4) {

        if (request.status !== status) {
          return reject(request);
        }

        if (!HMAC.hasValidResponse(request)) {
          return reject('The request does not have a valid response.');
        }

        return resolve(JSON.parse(request.responseText));
      }
    };

    // Sign the request
    let sign_parameters = {request, method, path, signed_headers, content_type};
    HMAC.sign(sign_parameters);

    request.setRequestHeader('Content-Type', content_type);
    request.send();
  });
}
