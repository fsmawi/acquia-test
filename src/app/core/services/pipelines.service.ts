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
   * Helper to make get requests. Adds Pipeline creds if supplied.
   * @param url
   * @param params
   * @returns {Promise<HttpRequest>}
   */
  promiseGetRequest(url, params = {}) {
    // Create Request Options Object
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

    // Make Call
    return this.http.get(url, reqOptions).map(r => r.json()).toPromise();
  }
}
