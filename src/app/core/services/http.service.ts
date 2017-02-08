import { Injectable } from '@angular/core';
import { Http } from '@angular/http';
import 'rxjs/add/operator/toPromise';


@Injectable()
export class HttpService {

  constructor(protected http: Http) { }

  /**
   * Helper to make get requests.
   * @param url
   * @param params
   */
  promiseGetRequest(url, params) {
    return this.http.get(url, params).map(r => r.json()).toPromise();
  }

  /**
   * Helper to make post requests
   * @param url
   * @param params
   * @param headers
   * @returns {Promise<HttpRequest>}
   */
  promisePostRequest(url, params, headers): Promise<any> {
    return this.http.post(url, params, headers).map(r => r.json()).toPromise();
  }
}
