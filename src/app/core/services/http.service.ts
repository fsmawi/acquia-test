import { Injectable } from '@angular/core';
import {Http, RequestOptions, Headers, URLSearchParams} from '@angular/http';
import {environment} from '../../../environments/environment';
import 'rxjs/add/operator/toPromise';


@Injectable()
export class HttpService {

  /**
   * Initiate the service
   * @param http
   */
  constructor(protected http: Http) { }

  /**
   * Helper to make get requests. Adds Pipeline creds if supplied.
   * @param url
   * @param params
   * @param headers
   * @returns {Promise<HttpRequest>}
   */
  promiseGetRequest(url, params = {}, headers = {}) {
    // Create Request Options Object
    const reqOptions = this.generateReqOptions(params, headers);

    // Make Call
    return this.http.get(url, reqOptions).map(r => r.json()).toPromise();
  }

  /**
   * Helper to make post requests. Adds Pipeline creds if supplied.
   * @param url
   * @param body
   * @param params
   * @param headers
   * @returns {Promise<HttpRequest>}
   */
  promisePostRequest(url, body = {}, params = {}, headers = {}): Promise<any> {
    // Create Request Options Object
    const reqOptions = this.generateReqOptions(params, headers);

    // Make Call
    return this.http.post(url, body, reqOptions).map(r => r.json()).toPromise();
  }

  /**
   * Generate common headers and params.
   * @param params
   * @param headers
   * @returns {RequestOptions}
   */
  generateReqOptions(params, headers = {}) {
    const reqOptions = new RequestOptions();
    reqOptions.headers = reqOptions.headers || new Headers();

    // use token auth if needed
    if (environment.n3Secret && environment.n3Key) {
      reqOptions.headers.append('X-ACQUIA-PIPELINES-N3-ENDPOINT', 'https://cloud.acquia.com');
      reqOptions.headers.append('X-ACQUIA-PIPELINES-N3-KEY', environment.n3Key);
      reqOptions.headers.append('X-ACQUIA-PIPELINES-N3-SECRET', environment.n3Secret);
    }

    // add headers
    Object.keys(headers).forEach(k => reqOptions.headers.append(k, headers[k]));

    if (environment.headers) {
      Object.keys(environment.headers).forEach(k => reqOptions.headers.append(k, environment.headers[k]));
    }

    // add query params
    reqOptions.search = reqOptions.search || new URLSearchParams();
    Object.keys(params).forEach(k => reqOptions.search.append(k, params[k]));

    return reqOptions;
  }
}
