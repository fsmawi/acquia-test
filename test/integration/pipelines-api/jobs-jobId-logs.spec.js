const supertest = require('supertest');
const qs = require('querystring');
const expect = require('chai').expect;
const logHelper = require('./log-helper').logHelper;

describe('Pipelines API /api/v1/ci/jobs/:jobId/logs', function () {
  const params = '?' + qs.stringify({applications: 'fbcd8f1f-4620-4bd6-9b60-f8d9d0f74fd0'});
  const API_URL = process.env.PIPELINES_API_URI;
  const N3_TOKEN = process.env.N3_KEY;
  const N3_SECRET = process.env.N3_SECRET;
  const N3_ENDPOINT = 'https://cloud.acquia.com';
  this.timeout(10000); // give every test this amount of timeout

  it('should return the logs for the job', () => {
    const route = '/api/v1/ci/jobs/' + 'ac40085f-7673-436d-935b-4d6ac3763400'
      + '/logs' + params; // route + jobid + params
    return supertest(API_URL) // returns a promise, so no done method
      .get(route)
      .set('X-ACQUIA-PIPELINES-N3-KEY', N3_TOKEN)
      .set('X-ACQUIA-PIPELINES-N3-SECRET', N3_SECRET)
      .set('X-ACQUIA-PIPELINES-N3-ENDPOINT', N3_ENDPOINT)
      .then((res) => {
        try {
          if (!res.ok && res.status !== 200) {
            throw res.text;
          } else {
            expect(res.status).to.equal(200);
            expect(res.header['content-type']).to.equal('application/json');
            expect(res.body).to.be.instanceof(Array);
            // Validating the first log json object in the array
            expect(res.body[0]).to.exist;
            expect(res.body[0].timestamp).to.exist;
            expect(res.body[0].level).to.exist;
            expect(res.body[0].message).to.exist;
          }
        } catch(e) {
          logHelper(res, route, params);
          throw e;
        }
      });
  });

  it('should return 404 if the job id is invalid', () => {
    const route = '/api/v1/ci/jobs/' + 'invalid-job-id' + '/logs' + params; // route + jobid + params
    return supertest(API_URL) // returns a promise, so no done method
      .get(route)
      .set('X-ACQUIA-PIPELINES-N3-KEY', N3_TOKEN)
      .set('X-ACQUIA-PIPELINES-N3-SECRET', N3_SECRET)
      .set('X-ACQUIA-PIPELINES-N3-ENDPOINT', N3_ENDPOINT)
      .expect('Content-Type', /json/)
      .then((res) => {
        try {
          if (!res.ok && res.status !== 404) {
            throw res.text;
          } else {
            expect(res.status).to.equal(404);
            expect(res.body.success).to.equal(false);
            expect(res.body.error).to.equal('Job with id invalid-job-id not found.');
            expect(res.body.message).to.equal('Job with id invalid-job-id not found.');
          }
        } catch(e) {
          logHelper(res, route, params);
          throw e;
        }
      });
  });

  it('should return 403', () => {
    const route = '/api/v1/ci/jobs/' + 'ac40085f-7673-436d-935b-4d6ac3763400'
                  + '/logs' + params; // route + jobid + params
    return supertest(API_URL) // returns a promise, so no done method
      .get(route)
      .set('X-ACQUIA-PIPELINES-N3-KEY', '')
      .set('X-ACQUIA-PIPELINES-N3-SECRET', '')
      .set('X-ACQUIA-PIPELINES-N3-ENDPOINT', '')
      .then((res) => {
        try {
          if (!res.ok && res.status !== 403) {
            throw res.text;
          } else {
            expect(res.status).to.equal(403);
            expect(res.body).to.deep.equal({});
            // Checking only if the text contains 'Error authorizing request: '
            // as the actual message is not standard
            // 'Error authorizing request: undefined method `downcase\' for nil:NilClass'
            expect(res.text).to.contain('Error authorizing request: ');
          }
        } catch(e) {
          logHelper(res, route, params);
          throw e;
        }
      });
  });
});
