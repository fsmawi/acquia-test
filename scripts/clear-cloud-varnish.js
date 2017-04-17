const request = require('superagent');

// Cloud api credentials
const CLOUD_API_ENDPOINT = process.env.PIPELINES_CLOUD_API_ENDPOINT; //'https://cloudapi.acquia.com/v1';
const USER = process.env.N3_KEY;
const PASSWORD = process.env.N3_SECRET;

const REALM = process.argv.slice(2)[0];
const SITE = process.argv.slice(2)[1];
const ENV = process.argv.slice(2)[2];

// 1 - Get All domain
// 2 - Purge cache for each domain
getEnvDomains()
  .then((domains) => {
    return Promise.all(domains.map(domain => {
      return purgeCache(domain.name);
    }));
  })
  .then((res) => {
    res.map(item => {
      console.log(item.description);
    });
  })
  .catch((err) => {
    console.error(err);
    process.exit(1);
  });

/**
 * Get All domains for current environment
 * @return {Promise}
 */
function getEnvDomains() {
  return new Promise((resolve, reject) => {
    let url = CLOUD_API_ENDPOINT + `/sites/${REALM}:${SITE}/envs/${ENV}/domains.json`;
    request
      .get(url)
      .auth(USER, PASSWORD)
      .retry(3)
      .end((err, res) => {
        if (err) {
          return reject(err);
        } else {
          return resolve(res.body.filter(function (domain) {
            return domain.name.indexOf('elb.amazonaws.com') == -1;
          }));
        }
      });
  });
}

/**
 * Purge the Varnish cache for a given domain
 * @param  domain
 * @return {Promise}
 */
function purgeCache(domain) {
  return new Promise((resolve, reject) => {
    let url = CLOUD_API_ENDPOINT + `/sites/${REALM}:${SITE}/envs/${ENV}/domains/${domain}/cache.json`;
    request
      .delete(url)
      .auth(USER, PASSWORD)
      .retry(3)
      .end(function(err, res) {
        if (err) {
          return reject(err);
        } else {
          return resolve(res.body);
        }
      });
  });
}
