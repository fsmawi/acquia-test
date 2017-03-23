const supertest = require('supertest');
const expect = require('chai').expect;
const qs = require('querystring');
const logAPICall = require('../log-helper').logAPICall;

describe('Pipelines API /api/v1/ci/jobs', function () {
  const token = process.env.N3_KEY;
  const secret = process.env.N3_SECRET;
  const endpoint = 'https://cloud.acquia.com';
  const route = '/api/v1/ci/jobs';
  this.timeout(10000);

  it('should return an array of job objects', () => {
    const params = '?' + qs.stringify({applications: 'fbcd8f1f-4620-4bd6-9b60-f8d9d0f74fd0'});
    return supertest(process.env.PIPELINES_API_URI)
      .get(route + params)
      .set('X-ACQUIA-PIPELINES-N3-ENDPOINT', endpoint)
      .set('X-ACQUIA-PIPELINES-N3-KEY', token)
      .set('X-ACQUIA-PIPELINES-N3-SECRET', secret)
      .expect(200)
      .expect('Content-Type', /json/)
      .then((res) => {
        try {
          if (!res.ok && res.status !== 200) {
            throw res.text;
          } else {
            expect(res.status).to.equal(200);
            expect(res.body).to.be.a('Array');
            expect(res.body[0]).to.be.a('Object');
            expect(res.body[0].branch).to.exist;
            expect(res.body[0].commit).to.not.be.undefined;
            expect(res.body[0].duration).to.not.be.undefined;
            expect(res.body[0].exit_message).to.exist;
            expect(res.body[0].finished_at).to.exist;
            expect(res.body[0].job_id).to.exist;
            // expect(res.body[0].output).to.exist;
            expect(res.body[0].pipeline_id).to.exist;
            expect(res.body[0].requested_at).to.exist;
            expect(res.body[0].sitename).to.exist;
            expect(res.body[0].started_at).to.exist;
            expect(res.body[0].status).to.exist;
          }
        } catch(e) {
          logAPICall(res, route, params);
          throw e;
        }
      });
  });

  it('should return 403 when headers are missing from request ', () => {
    const params = '?' + qs.stringify({applications: 'fbcd8f1f-4620-4bd6-9b60-f8d9d0f74fd0'});
    return supertest(process.env.PIPELINES_API_URI)
      .get(route + params)
      .then((res) => {
        try {
          if (!res.ok && res.status !== 403) {
            throw res.text;
          } else {
            expect(res.status).to.equal(403);
            expect(res.text).to.contain('Missing mandatory parameters: n3_endpoint');
          }
        } catch(e) {
          logAPICall(res, route, params);
          throw e;
        }
      });
  });

  it('should return 403 (not 404) when application ID dont exists', () => {
    const params = '?' + qs.stringify({applications: '123'});
    return supertest(process.env.PIPELINES_API_URI)
      .get(route + params)
      .set('X-ACQUIA-PIPELINES-N3-KEY', token)
      .set('X-ACQUIA-PIPELINES-N3-SECRET', secret)
      .set('X-ACQUIA-PIPELINES-N3-ENDPOINT', endpoint)
      .then((res) => {
        try {
          if (!res.ok && res.status !== 403) {
            throw res.text;
          } else {
            expect(res.status).to.equal(403);
            expect(res.text).to
            .contain('Error authorizing request: Expected([200, 201, 202, 203, 204, 205, 206, 302]) <=> Actual(400 Bad Request)');
          }
        } catch(e) {
          logAPICall(res, route, params);
          throw e;
        }
      });
  });
});
