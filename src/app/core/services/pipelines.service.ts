import {Injectable} from '@angular/core';
import {Http, RequestOptions, Headers, URLSearchParams} from '@angular/http';

import 'rxjs/add/operator/toPromise';
import 'rxjs/add/operator/map';

import {Application} from '../models/application';
import {environment} from '../../../environments/environment';
import {Job} from '../models/job';
import {Pipeline} from '../models/pipeline';
import {Repository} from '../models/repository';

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
   * Gets bakery authentication cookies if missing
   * @returns {Promise<any>}
   */
  authBakery() {
    // Does not use the promisePost/Get methods because it is used within
    const reqOptions = new RequestOptions();
    reqOptions.headers = reqOptions.headers || new Headers();

    // All request headers
    if (environment.headers) {
      Object.keys(environment.headers).forEach(k => reqOptions.headers.append(k, environment.headers[k]));
    }

    // Add cookie headers
    reqOptions.withCredentials = environment.production;
    return this.http.get(this.URI + '/auth/bakery', reqOptions)
      .map(r => r.json())
      .toPromise()
      .catch(e => {
        // User is not authenticated, redirect
        if (e.authenticated === false) {
          window.top.location.href = environment.authCloudRedirect;
        } else {
          throw e;
        }
      });
  }

  /**
   * Gets a list of jobs for the supplied app id
   * @param appId
   * @param params
   * @returns {Promise<Array<Job>>}
   */
  getJobsByAppId(appId: string, params = {}) {
    return this.promiseGetRequest(this.URI + `/ci/jobs?applications=${appId}`, params).then((r: any) => r.map(j => new Job(j)));
  }

  /**
   * Get an individual Job
   * @param appId
   * @param jobId
   * @param params
   * @returns {Promise<Job>}
   */
  getJobByJobId(appId: string, jobId: string, params = {}) {
    return this.promiseGetRequest(this.URI + `/ci/jobs/${jobId}?applications=${appId}`, params).then((r: any) => new Job(r));
  }

  /**
   * Attach an OauthGit repository
   * @param repositoy
   * @param application
   * @param repoType
   */
  attachOauthGitRepository(repositoy: string, application: string, repoType: string) {
    return this.promisePostRequest(this.URI + `/ci/webhook/${repoType}/init`, {
      repo: repositoy,
      applications: [application]
    });
  }

  /**
   * Removes OauthGit Auth from the application
   * @param repository
   * @param application
   * @param repoType
   * @returns {Promise<T>}
   */
  removeOauthGitAuth(repository: string, application: string, repoType: string) {
    return this.promiseDeleteRequest(this.URI + `/ci/webhook/${repoType}`, {
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
    return this.promiseGetRequest(this.URI + `/ci/jobs/${jobId}/logs?applications=${appId}`, params).then((r: any) => r);
  }

  /**
   * Gets the encrypted value for the dataItem
   * @param appId
   * @param dataItem
   * @returns {any}
   */
  getEncryptedValue(appId: string, dataItem: string) {
    return this.promisePostRequest(this.URI + `/ci/encrypt`, {
      applications: [appId],
      data_item: dataItem
    });
  }

  /**
   * Get pipeline for the supplied app id
   * @param  appId
   * @param  getRepoMeta
   * @returns {Promise<Array<Pipeline>>}
   */
  getPipelineByAppId(appId: string, getRepoMeta = true) {
    return this.promiseGetRequest(this.URI + `/ci/pipelines`, {
      include_repo_data: getRepoMeta ? 1 : undefined,
      applications: [appId]
    }).then((r: any) => r.map(p => new Pipeline(p)));
  }

  /**
   * Get the application information
   * @param appId
   * @returns {Promise<Application>}
   */
  getApplicationInfo(appId: string) {
    return this.promiseGetRequest(this.URI + '/ci/applications', {
      applications: [appId]
    }).then((r: any) => new Application(r));
  }

  /**
   * Get the N3 Token info for the application
   * @param appId
   * @returns {Promise<Application>}
   */
  getN3TokenInfo(appId: string) {
    return this.promiseGetRequest(this.URI + '/ci/applications/cloudapi-linking-status', {
      applications: [appId]
    });
  }

  /**
   * Sets the N3 key/secret for the application
   * @param appId
   * @param options
   * @returns {Promise<HttpRequest>}
   */
  setN3Credentials(appId: string, options = {}) {
    // Default Options
    Object.assign(options, {
      applications: [appId]
    });

    return this.promisePostRequest(this.URI + `/ci/applications/cloudapi-token`, options);
  }

  /**
   * Get all connected user's repositories
   * @param pager
   * @param appId
   * @param repoType
   */
  getRepositoriesByPage(pager: string, appId: string, repoType: string) {
    if (pager !== '') {
      pager = `&${pager}`;
    }
    return this.promiseGetRequest(this.URI + `/ci/webhook/${repoType}/repositories?per_page=100&applications=${appId}${pager}`, {})
      .then((res: any) => {
        res.values = res.values.map(r => new Repository(r));
        return res;
      });
  }

  /**
   * Get all the branches available for an appId
   * @param appId
   * @returns {Promise<Array<String>>}
   */
  getBranches(appId: string) {
    return this.promiseGetRequest(this.URI + `/ci/applications?applications=${appId}&include_branches=1`, {})
      .then((res: any) => res.branches);
  }

  /**
   * Get all the applications available
   * @returns {Promise<T>}
   */
  getApplications() {
    return this.promiseGetRequest(this.URI + `/ci/applications/list`, {})
      .then(res => {
        return res.map(r => new Application(r));
      });
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
   * @param options
   * @returns {Promise<HttpRequest>}
   */
  startJob(appId: string, options = {}) {
    // Default Options
    Object.assign(options, {
      applications: [appId]
    });

    return this.promisePostRequest(this.URI + `/ci/pipelines/start`, options);
  }

  /**
   * Updates webhooks for an app
   * @param appId
   * @param enable
   * @param options
   * @returns {Promise<HttpRequest>}
   */
  updateWebhooks(appId: string, enable = true, options = {}) {
    // Default Options
    Object.assign(options, {
      applications: [appId],
      webhook: enable
    });

    return this.promisePostRequest(this.URI + `/ci/webhook/integration`, options);
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
    let deploy_vcs_path = `pipelines-build-${options['branch'] || branch}`;
    // check if the original trigger is a PR
    if (options['trigger'] && options['trigger'] === 'pull_request') {
      deploy_vcs_path = `pipelines-build-${'pr-' + options['metadata']['pull_request'] || branch}`;
    }

    Object.assign(options, {
      applications: [appId],
      branch: options['branch'] || branch,
      deploy_vcs_path: deploy_vcs_path,
    });

    // Update metadata as well for oauth repo types
    Object.assign(options, options['metadata'] || {});
    return this.promisePostRequest(this.URI + `/ci/pipelines/direct-start`, options);
  }

  /**
   * Helper to make get requests. Adds Pipeline creds if supplied.
   * @param url
   * @param params
   * @param firstTime Flag for first time calls, allowing a retry after bakery
   * @returns {Promise<HttpRequest>}
   */
  promiseGetRequest(url, params = {}, firstTime = true) {
    // Create Request Options Object
    const reqOptions = this.generateReqOptions(params);

    // Make Call
    return this.http.get(url, reqOptions).map(r => r.json()).toPromise()
      .catch(e => {
        if (e.status === 403 && firstTime) {
          return this.authBakery()
            .then(() => this.promiseGetRequest(url, params, false));
        } else {
          return Promise.reject(e);
        }
      });
  }

  /**
   * Helper to make post requests. Adds Pipeline creds if supplied.
   * @param url
   * @param body
   * @param params
   * @param firstTime Flag for first time calls, allowing a retry after bakery
   * @returns {Promise<HttpRequest>}
   */
  promisePostRequest(url, body = {}, params = {}, firstTime = true): Promise<any> {
    const reqOptions = this.generateReqOptions(params);

    // Make Call
    return this.http.post(url, body, reqOptions).map(r => r.json()).toPromise()
      .catch(e => {
        if (e.status === 403 && firstTime) {
          return this.authBakery()
            .then(() => this.promisePostRequest(url, body, params, false));
        } else {
          return Promise.reject(e);
        }
      });
  }

  /**
   * Helper to make delete requests. Adds Pipeline creds if supplied.
   * @param url
   * @param params
   * @param firstTime Flag for first time calls, allowing a retry after bakery
   * @returns {Promise<HttpRequest>}
   */
  promiseDeleteRequest(url, params = {}, firstTime = true) {

    // Create Request Options Object
    const reqOptions = this.generateReqOptions(params);

    // Make Call
    return this.http.delete(url, reqOptions).map(r => r.json()).toPromise()
      .catch(e => {
        if (e.status === 403 && firstTime) {
          return this.authBakery()
            .then(() => this.promiseDeleteRequest(url, params, false));
        } else {
          return Promise.reject(e);
        }
      });
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
