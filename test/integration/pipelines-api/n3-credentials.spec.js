const supertest = require('supertest');
const expect = require('chai').expect;
const qs = require('querystring');
const logAPICall = require('../log-helper').logAPICall;


describe('Pipelines API /api/v1/ci/applications/n3-token', function() {
  const token = process.env.N3_KEY;
  const secret = process.env.N3_SECRET;
  const endpoint = 'https://cloud.acquia.com';
  const getN3TokenInfoRoute = '/api/v1/ci/applications/cloudapi-linking-status';
  const setN3TokenRoute = '/api/v1/ci/applications/cloudapi-token';
  this.timeout(10000);
  const params = '?' + qs.stringify({
      applications: 'd6a43c82-cc6e-4426-b6eb-883cbe4a99ea',
    });

  it('should return the n3 token info', () => {
    return supertest(process.env.PIPELINES_API_URI)
      .get(getN3TokenInfoRoute + params)
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
            expect(res.body).to.be.a('Object');
            expect(res.body.is_token_valid).to.exist;
            expect(res.body.token_attached).to.exist;
            expect(res.body.can_execute_pipelines).to.exist;
          }
        } catch (e) {
          logAPICall(res, getN3TokenInfoRoute);
          throw e;
        }
      });
  });

  it('should set the n3 credentials', () => {
    return supertest(process.env.PIPELINES_API_URI)
      .post(setN3TokenRoute + params)
      .set('X-ACQUIA-PIPELINES-N3-ENDPOINT', endpoint)
      .set('X-ACQUIA-PIPELINES-N3-KEY', token)
      .set('X-ACQUIA-PIPELINES-N3-SECRET', secret)
      .then((res) => {
        try {
          if (!res.ok) {
            throw res.text;
          } else {
            const validStatus = [200, 201];
            expect(res.header['content-type']).to.equal('application/json');
            expect(res.status).to.be.oneOf(validStatus);
            expect(res.body).to.be.a('Object');
            expect(res.body.success).to.exist;
          }
        } catch (e) {
          logAPICall(res, setN3TokenRoute);
          throw e;
        }
      });
  });
});
