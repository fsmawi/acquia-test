import {Injectable} from '@angular/core';
import {Http, RequestOptions, Headers, URLSearchParams} from '@angular/http';

import 'rxjs/add/operator/toPromise';

import {Application} from '../models/application';
import {environment} from '../../../environments/environment';
import {Job} from '../models/job';
import {Pipeline} from '../models/pipeline';
import {Repository} from '../models/repository';
import {GithubStatus} from '../models/github-status';

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
   * @param repositoy
   * @param application
   */
  attachGithubRepository(repositoy: string, application: string) {
    return this.promisePostRequest(this.URI + `/ci/github/init`, {
      repo: repositoy,
      applications: [application]
    });
  }

  /**
   * Removes Github Auth from the application
   * @param repository
   * @param application
   * @returns {Promise<T>}
   */
  removeGitHubAuth(repository: string, application: string) {
    return this.promiseDeleteRequest(this.URI + `/ci/github`, {
      repo: repository,
      applications: [application]
    });
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
   * Get pipeline for the supplied app id
   * @param  appId
   * @returns {Promise<Array<Pipeline>>}
   */
  getPipelineByAppId(appId: string) {
    return this.promiseGetRequest(this.URI + `/ci/pipelines`, {
      include_repo_data: 1,
      applications: [appId]
    }).then(r => r.map(p => new Pipeline(p)));
  }

  /**
   * Get the github status of an application
   * @param appId
   * @returns {Promise<GithubStatus>}
   */
  getGithubStatus(appId: string) {
    return this.promiseGetRequest(this.URI + '/ci/github/status', {
      applications: [appId]
    }).then(r => new GithubStatus(appId, r));
  }

  /**
   * Get the application information
   * @param appId
   * @returns {Promise<Application>}
   */
  getApplicationInfo(appId: string) {
    return this.promiseGetRequest(this.URI + '/ci/applications', {
      applications: [appId]
    }).then(r => new Application(r));
  }

  /**
   * Get all connected user's repositories
   * @param page
   * @param appId
   */
  getRepositoriesByPage(page: number, appId: string) {
    return this.promiseGetRequest(this.URI + `/ci/github/repos?per_page=100&page=${page}&applications=${appId}`, {})
      .then(res => res.map(r => new Repository(r)));
  }

  /**
   * Get all the branches available for an appId
   * @param appId
   * @returns {Promise<T>}
   */
  getBranches(appId: string) {
    return this.getPipelineByAppId(appId)
      .then(p => (p.length > 0) ? p[0].repo_data ? p[0].repo_data.branches || [] : [] : []);
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
  stopJob(
    appId: string,
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
   * Direct Start a pipelines job
   * @param appId
   * @param branch
   * @param options
   * @returns {Promise<HttpRequest>}
   */
  directStartJob(appId: string, branch: string, options = {}) {
    // Default Options
    Object.assign(options, {
      applications: [appId],
      branch: branch,
      deploy_vcs_path: `pipelines-build-${branch}`
    });

    return this.getPipelineByAppId(appId)
      .then(p => {
        if (p.length > 0) {
          return this.promisePostRequest(this.URI + `/ci/pipelines/${p[0].pipeline_id}/direct-start`, options);
        } else {
          return Promise.reject(new Error('No pipelines for the given application.'));
        }
      });
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
  promisePostRequest(url, body = {}, params = {}): Promise<any> {
    const reqOptions = this.generateReqOptions(params);

    // Make Call
    return this.http.post(url, body, reqOptions).map(r => r.json()).toPromise();
  }

  /**
   * Helper to make deleye requests. Adds Pipeline creds if supplied.
   * @param url
   * @param params
   * @returns {Promise<HttpRequest>}
   */
  promiseDeleteRequest(url, params = {}) {
    // Create Request Options Object
    const reqOptions = this.generateReqOptions(params);

    // Make Call
    return this.http.delete(url, reqOptions).map(r => r.json()).toPromise();
  }

  /**
   * Generate common headers and params.
   * @param params
   * @returns {RequestOptions}
   */
  generateReqOptions(params) {
    const reqOptions = new RequestOptions();
    reqOptions.headers = reqOptions.headers || new Headers();

    // All request headers
    if (environment.headers) {
      Object.keys(environment.headers).forEach(k => reqOptions.headers.append(k, environment.headers[k]));
    }

    // add query params
    reqOptions.search = reqOptions.search || new URLSearchParams();
    Object.keys(params).forEach(k => reqOptions.search.append(k, params[k]));

    // Add cookie headers
    reqOptions.withCredentials = environment.production;

    return reqOptions;
  }
}
