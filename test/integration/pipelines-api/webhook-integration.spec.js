const supertest = require('supertest');
const expect = require('chai').expect;
const qs = require('querystring');
const logAPICall = require('../log-helper').logAPICall;

describe('Pipelines API /api/v1/ci/webhook/integration', function () {
  const token = process.env.N3_KEY;
  const secret = process.env.N3_SECRET;
  const endpoint = 'https://cloud.acquia.com';
  const route = '/api/v1/ci/webhook/integration';
  const appId = 'd6a43c82-cc6e-4426-b6eb-883cbe4a99ea';
  this.timeout(10000);

  it('should enable acquia-git webhook', () => {
    const params = '?' + qs.stringify({
      applications: appId,
      webhook: true,
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
            expect(res.status).to.equal(200);
            expect(res.body).to.exist;
            expect(res.body).to.be.not.null;
            expect(res.body).to.be.a('Object');
            expect(res.body.success).to.exist;
            expect(res.body.success).to.be.true;
            expect(res.body.webhook).to.exist;
            expect(res.body.webhook).to.be.true;
          }
        } catch (e) {
          logAPICall(res, route, params);
          throw e;
        }
      });
  });

  it('should disable acquia-git webhook', () => {
    const params = '?' + qs.stringify({
      applications: appId,
      webhook: false,
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
            expect(res.status).to.equal(200);
            expect(res.body).to.exist;
            expect(res.body).to.be.not.null;
            expect(res.body).to.be.a('Object');
            expect(res.body.success).to.exist;
            expect(res.body.success).to.be.true;
            expect(res.body.webhook).to.exist;
            expect(res.body.webhook).to.be.false;
          }
        } catch (e) {
          logAPICall(res, route, params);
          throw e;
        }
      });
  });

  it('should returns 500 when trying to disable acquia-git webhook again', () => {
    const params = '?' + qs.stringify({
      applications: appId,
      webhook: false,
    });
    return supertest(process.env.PIPELINES_API_URI)
      .post(route + params)
      .set('X-ACQUIA-PIPELINES-N3-ENDPOINT', endpoint)
      .set('X-ACQUIA-PIPELINES-N3-KEY', token)
      .set('X-ACQUIA-PIPELINES-N3-SECRET', secret)
      .then((res) => {
        try {
          if (!res.ok && res.status !== 500) {
            throw res.text;
          } else {
            expect(res.header['content-type']).to.equal('application/json');
            expect(res.status).to.equal(500);
            expect(res.body).to.exist;
            expect(res.body).to.be.not.null;
            expect(res.body).to.be.a('Object');
            expect(res.body.success).to.exist;
            expect(res.body.success).to.be.false;
            expect(res.body.message).to.exist;
            expect(res.body.message).to.contain('No Webhooks found for Sitegroup with the name');
          }
        } catch (e) {
          logAPICall(res, route, params);
          throw e;
        }
      });
  });
});
