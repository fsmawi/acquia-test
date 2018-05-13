const supertest = require('supertest');
const expect = require('chai').expect;
const qs = require('querystring');
const logAPICall = require('../log-helper').logAPICall;

describe('Pipelines API Dbs source configuration', function () {
  const token = process.env.N3_KEY;
  const secret = process.env.N3_SECRET;
  const endpoint = 'https://cloud.acquia.com';
  this.timeout(10000);

  it('should return an array of environments', () => {
    const route = '/api/v1/ci/applications';
    const params = '?' + qs.stringify({
      applications: 'fbcd8f1f-4620-4bd6-9b60-f8d9d0f74fd0',
      include_branches: 1,
    });
    return supertest(process.env.PIPELINES_API_URI)
      .get(route + params)
      .set('X-ACQUIA-PIPELINES-N3-ENDPOINT', endpoint)
      .set('X-ACQUIA-PIPELINES-N3-KEY', token)
      .set('X-ACQUIA-PIPELINES-N3-SECRET', secret)
      .then((res) => {
        try {
          if (!res.ok) {
            throw res.text;
          } else {
            expect(res.header['content-type']).to.equal('application/json');
            expect(res.status).to.equal(200);
            expect(res.body.db_sync_source_env).to.not.be.undefined;
            expect(res.body.environments).to.be.a('Array');
            res.body.environments.forEach(b => expect(b.id).to.exist);
            res.body.environments.forEach(b => expect(b.id).to.be.a('String'));
            res.body.environments.forEach(b => expect(b.label).to.exist);
            res.body.environments.forEach(b => expect(b.label).to.be.a('String'));
          }
        } catch (e) {
          logAPICall(res, route, params);
          throw e;
        }
      });
  });

  it('should configure DB Sync source environment', () => {
    const route = '/api/v1/ci/applications/sync-db-environment';
    const params = '?' + qs.stringify({
      applications: 'fbcd8f1f-4620-4bd6-9b60-f8d9d0f74fd0',
      env_id: '379-fbcd8f1f-4620-4bd6-9b60-f8d9d0f74fd0',
    });
    return supertest(process.env.PIPELINES_API_URI)
      .post(route + params)
      .set('X-ACQUIA-PIPELINES-N3-ENDPOINT', endpoint)
      .set('X-ACQUIA-PIPELINES-N3-KEY', token)
      .set('X-ACQUIA-PIPELINES-N3-SECRET', secret)
      .then((res) => {
        try {
          if (!res.ok) {
            throw res.text;
          } else {
            expect(res.header['content-type']).to.equal('application/json');
            expect(res.status).to.equal(201);
            expect(res.body.success).to.exist;
            expect(res.body.success).to.be.true;
          }
        } catch (e) {
          logAPICall(res, route, params);
          throw e;
        }
      });
  });
});
