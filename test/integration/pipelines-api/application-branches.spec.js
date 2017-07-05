const supertest = require('supertest');
const expect = require('chai').expect;
const qs = require('querystring');
const logAPICall = require('../log-helper').logAPICall;

describe('Pipelines API /api/v1/ci/applications/:id?include_branches', function () {
  const token = process.env.N3_KEY;
  const secret = process.env.N3_SECRET;
  const endpoint = 'https://cloud.acquia.com';
  const route = '/api/v1/ci/applications';
  this.timeout(10000);

  it('should return an array of branches', () => {
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
          if (!res.ok && res.status !== 200) {
            throw res.text;
          } else {
            expect(res.header['content-type']).to.equal('application/json');
            expect(res.status).to.equal(200);
            expect(res.body.branches).to.be.a('Array');
            res.body.branches.forEach(b => expect(b).to.be.a('String'));
            expect(res.body.branches).to.be.not.null;
            expect(res.body.repo_type).to.exist;
            expect(res.body.repo_name).to.exist;
          }
        } catch (e) {
          logAPICall(res, route, params);
          throw e;
        }
      });
  });

  it('should return 403 when site doesn\'t have pipelines enabled', () => {
    const params = '?' + qs.stringify({
      applications: '410025b5-326d-7a84-b1bf-40ae95fb45f5',
      include_branches: 1,
    });
    return supertest(process.env.PIPELINES_API_URI)
      .get(route + params)
      .set('X-ACQUIA-PIPELINES-N3-ENDPOINT', endpoint)
      .set('X-ACQUIA-PIPELINES-N3-KEY', token)
      .set('X-ACQUIA-PIPELINES-N3-SECRET', secret)
      .then((res) => {
        try {
          if (!res.ok && res.status !== 403) {
            throw res.text;
          } else {
            expect(res.status).to.equal(403);
            expect(res.text).to.contain('Error authorizing request: site doesn\'t have pipelines enabled');
          }
        } catch (e) {
          logAPICall(res, route, params);
          throw e;
        }
      });
  });

  it('should return 403 when application ID dont exists', () => {
    const params = '?' + qs.stringify({
      applications: 'abcdef123-4620-4bd6-9b60-f8d9d0f74fd0',
      include_branches: 1,
    });
    return supertest(process.env.PIPELINES_API_URI)
      .get(route + params)
      .set('X-ACQUIA-PIPELINES-N3-ENDPOINT', endpoint)
      .set('X-ACQUIA-PIPELINES-N3-KEY', token)
      .set('X-ACQUIA-PIPELINES-N3-SECRET', secret)
      .then((res) => {
        try {
          if (!res.ok && res.status !== 403) {
            throw res.text;
          } else {
            expect(res.status).to.equal(403);
            expect(res.text).to
              .contain('Error authorizing request: N3 auth failed');
          }
        } catch (e) {
          logAPICall(res, route, params);
          throw e;
        }
      });
  });

  it('should return 403 when headers are missing from request ', () => {
    const params = '?' + qs.stringify({
      applications: 'fbcd8f1f-4620-4bd6-9b60-f8d9d0f74fd0',
      include_branches: 1
    });
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
        } catch (e) {
          logAPICall(res, route, params);
          throw e;
        }
      });
  });
});
