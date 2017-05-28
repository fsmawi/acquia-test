const supertest = require('supertest');
const expect = require('chai').expect;
const qs = require('querystring');
const logAPICall = require('../log-helper').logAPICall;


describe('Pipelines API /api/v1/ci/applications/list', function () {
  const token = process.env.N3_KEY;
  const secret = process.env.N3_SECRET;
  const endpoint = 'https://cloud.acquia.com';
  const route = '/api/v1/ci/applications/list';
  this.timeout(10000);

  it('should return an array of application objects', () => {
    return supertest(process.env.PIPELINES_API_URI)
      .get(route)
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
            expect(res.body).to.be.a('Array');
            expect(res.body[0]).to.be.a('Object');
            expect(res.body[0].name).to.exist;
            expect(res.body[0].uuid).to.exist;
            expect(res.body[0].latest_job).toBeDefined;
          }
        } catch(e) {
          logAPICall(res, route);
          throw e;
        }
      });
  });

  /* This api was bit flacky. It's returing 500 and 599 statuses. Disabling this
  until it's actual status code when headers are missed from the request is confirmed
  from the api team
  it('should return 500 when headers are missing from request', () => {
    return supertest(process.env.PIPELINES_API_URI)
      .get(route)
      .then((res) => {
        try {
          if (!res.ok && res.status !== 500) {
            throw res.text;
          } else {
            expect(res.status).to.equal(500);
            expect(res.text).to.contain('Failed to get list of applications from Acquia Cloud');
          }
        } catch(e) {
          logAPICall(res, route);
          throw e;
        }
      });
});*/
});
