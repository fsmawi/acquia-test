import {Injectable} from '@angular/core';
import {Http, RequestOptions, Headers, URLSearchParams} from '@angular/http';
import {environment} from '../../../environments/environment';
import 'rxjs/add/operator/toPromise';
import {Job} from '../models/job';

@Injectable()
export class PipelinesService {

  /**
   * Pipelines API Endpoint with Version
   * @type {string}
   */
  URI = `${environment.apiEndpoint}/api/v1`;

  /**
   * Create the Service
   * @param http
   */
  constructor(private http: Http) {
  }

  /**
   * Gets a list of jobs for the supplied app id
   * @param appId
   * @param params
   * @returns {Promise<Array<Job>>}
   */
  getJobsByAppId(appId: string, params = {}) {
    return this.promiseGetRequest(this.URI + `/ci/jobs?applications=${appId}`, params).then(r => r.map(j => new Job(j)));
  }

  /**
   * Get an individual Job
   * @param appId
   * @param jobId
   * @param params
   * @returns {Promise<Job>}
   */
  getJobByJobId(appId: string, jobId: string, params = {}) {
    return this.promiseGetRequest(this.URI + `/ci/jobs/${jobId}?applications=${appId}`, params).then(r => new Job(r));
  }

  /**
   * Attach a Github repository
   * @param token
   * @param repositoy
   * @param applications
   */
  attachGithubRepository(token: string, repositoy: string, applications = []) {
    return this.promisePostRequest(this.URI + `/ci/github/init`, {
      github_token: token,
      repo: repositoy,
      applications: applications
    }, {});
  }

  /**
   * Get a presigned redirecting URL
   * @param appId
   */
  getPresignedUrl(appId: string) {
    return this.promisePostRequest(environment.apiEndpoint + `/redirect/create`, {
      application_id: appId,
      url: environment.URL + `/auth/github/code/${appId}`
    }, {});
  }

  /**
   * Gets the logs from a job
   * @param appId
   * @param jobId
   * @param params
   * @returns {Promise<Array<JobLog>>}
   */
  getLogFile(appId: string, jobId: string, params = {}) {
    return this.promiseGetRequest(this.URI + `/ci/jobs/${jobId}/logs?applications=${appId}`, params);
  }

  /**
   * Stops a job
   * @param appId
   * @param jobId
   * @param buildstepsEndpoint
   * @param buildstepsUser
   * @param buildstepsPass
   * @returns {Promise<HttpResponse>}
   */
  stopJob(appId: string,
          jobId: string,
          buildstepsEndpoint: string = undefined,
          buildstepsUser: string = undefined,
          buildstepsPass: string = undefined) {
    return this.promisePostRequest(this.URI + `/ci/jobs/${jobId}/terminate`, {
      applications: [appId],
      buildsteps_endpoint: buildstepsEndpoint,
      buildsteps_user: buildstepsUser,
      buildsteps_pass: buildstepsPass
    });
  }

  /**
   * Starts a pipelines job
   * @param appId
   * @param pipelineId
   * @param options
   * @returns {Promise<HttpRequest>}
   */
  startJob(appId: string, pipelineId: string, options = {}) {
    // Default Options
    Object.assign(options, {
      applications: [appId]
    });

    return this.promisePostRequest(this.URI + `/ci/pipelines/${pipelineId}/start`, options);
  }

  /**
   * Helper to make get requests. Adds Pipeline creds if supplied.
   * @param url
   * @param params
   * @returns {Promise<HttpRequest>}
   */
  promiseGetRequest(url, params = {}) {
    // Create Request Options Object
    const reqOptions = this.generateReqOptions(params);

    // Make Call
    return this.http.get(url, reqOptions).map(r => r.json()).toPromise();
  }

  /**
   * Helper to make post requests. Adds Pipeline creds if supplied.
   * @param url
   * @param body
   * @param params
   * @returns {Promise<HttpRequest>}
   */
  promisePostRequest(url, body = {}, params = {}) {
    const reqOptions = this.generateReqOptions(params);

    // Make Call
    return this.http.post(url, body, reqOptions).map(r => r.json()).toPromise();
  }

  /**
   * Generate common headers and params.
   * @param params
   * @returns {RequestOptions}
   */
  generateReqOptions(params) {
    const reqOptions = new RequestOptions();

    // use token auth if needed
    if (environment.n3Secret && environment.n3Key) {
      reqOptions.headers = reqOptions.headers || new Headers();
      reqOptions.headers.append('X-ACQUIA-PIPELINES-N3-ENDPOINT', 'https://cloud.acquia.com');
      reqOptions.headers.append('X-ACQUIA-PIPELINES-N3-KEY', environment.n3Key);
      reqOptions.headers.append('X-ACQUIA-PIPELINES-N3-SECRET', environment.n3Secret);
    }

    // add query params
    reqOptions.search = reqOptions.search || new URLSearchParams();
    Object.keys(params).forEach(k => reqOptions.search.append(k, params[k]));

    return reqOptions;
  }
}
