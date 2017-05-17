import {Injectable} from '@angular/core';
import 'rxjs/add/operator/toPromise';
import 'rxjs/add/operator/map';
import {environment} from '../../../environments/environment';
import {Http, Headers, RequestOptions} from '@angular/http';

@Injectable()
export class AuthService {

  /**
   * Store the bakery result, so we don't have to call it on every route change
   */
  static authResponse: boolean;

  /**
   * Builds the service
   * @param http
   */
  constructor(private http: Http) {
  }

  /**
   * Checks for Bakery Authentication existence
   * @returns {Promise<boolean>}
   */
  isLoggedIn(): Promise<boolean> {

    // Used the cache response to speed up route activation
    if (AuthService.authResponse) {
      return Promise.resolve(true);
    }

    const reqOptions = new RequestOptions();
    reqOptions.headers = reqOptions.headers || new Headers();

    // All request headers
    if (environment.headers) {
      Object.keys(environment.headers).forEach(k => reqOptions.headers.append(k, environment.headers[k]));
    }

    // Add cookie headers
    reqOptions.withCredentials = environment.production;

    // execute getRequest
    return this.http.get(environment.apiEndpoint + '/api/v1/auth/bakery', reqOptions)
      .map(r => r.json())
      .toPromise()
      .then(res => {
        AuthService.authResponse = res.authenticated;
        return Promise.resolve(res.authenticated);
      }).catch(e => Promise.resolve(e.authenticated));
  };
}
