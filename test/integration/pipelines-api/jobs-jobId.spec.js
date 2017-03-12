const supertest = require('supertest');
const qs = require('querystring');
const expect = require('chai').expect;

describe('Pipelines API /api/v1/ci/jobs/:jobId', function () {
  const params = '?' + qs.stringify({applications: 'fbcd8f1f-4620-4bd6-9b60-f8d9d0f74fd0'});
  const API_URL = process.env.PIPELINES_API_URI;
  const N3_TOKEN = process.env.N3_KEY;
  const N3_SECRET = process.env.N3_SECRET;
  const N3_ENDPOINT = 'https://cloud.acquia.com';
  this.timeout(10000); // give every test this amount of timeout

  it('should return the details of the job', () => {
    const route = '/api/v1/ci/jobs/' + 'ac40085f-7673-436d-935b-4d6ac3763400' + params; // route + jobId + params
    return supertest(API_URL) // returns a promise, so no done method
        .get(route)
        .set('X-ACQUIA-PIPELINES-N3-KEY', N3_TOKEN)
        .set('X-ACQUIA-PIPELINES-N3-SECRET', N3_SECRET)
        .set('X-ACQUIA-PIPELINES-N3-ENDPOINT', N3_ENDPOINT)
        .expect(200)
        .expect('Content-Type', /json/)
        .then((res) => {
          expect(res.body).to.exist;
          expect(res.body.branch).to.exist;
          expect(res.body.pipeline_id).to.exist;
          expect(res.body.job_id).to.exist;
          expect(res.body.status).to.exist;
          expect(res.body.started_at).to.exist;
          expect(res.body.sitename).to.exist;
          expect(res.body.requested_at).to.exist;
          expect(res.body.requested_by).to.exist;
          expect(res.body.finished_at).to.exist;
          expect(res.body.exit_message).to.exist;
          expect(res.body.commit).to.not.be.undefined;
    });
  });

  it('should return 404 if the job id is invalid', () => {
    const route = '/api/v1/ci/jobs/' + 'invalid-job-id' + params; // route + jobId + params
    return supertest(API_URL) // returns a promise, so no done method
        .get(route)
        .set('X-ACQUIA-PIPELINES-N3-KEY', N3_TOKEN)
        .set('X-ACQUIA-PIPELINES-N3-SECRET', N3_SECRET)
        .set('X-ACQUIA-PIPELINES-N3-ENDPOINT', N3_ENDPOINT)
        .expect(404)
        .then((res) => {
            expect(res.body.success).to.equal(false);
            expect(res.body.error).to.equal('Job with id invalid-job-id not found.');
            expect(res.body.message).to.equal('Job with id invalid-job-id not found.');
         });
  });

  it('should return 403', () => {
    const route = '/api/v1/ci/jobs/' + 'ac40085f-7673-436d-935b-4d6ac3763400' + params; // route + jobId + params
    return supertest(API_URL) // returns a promise, so no done method
        .get(route)
        .set('X-ACQUIA-PIPELINES-N3-KEY', '')
        .set('X-ACQUIA-PIPELINES-N3-SECRET', '')
        .set('X-ACQUIA-PIPELINES-N3-ENDPOINT', '')
        .expect(403)
        .then((res) => {
          expect(res.body).to.deep.equal({});
          // Checking only if the text contains 'Error authorizing request: '
          // as the actual message is not standard
          // 'Error authorizing request: undefined method `downcase\' for nil:NilClass'
          expect(res.text).to.contain('Error authorizing request: ');
        });
  });
});
