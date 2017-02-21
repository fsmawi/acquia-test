import {Injectable} from '@angular/core';
import 'rxjs/add/operator/toPromise';
import 'rxjs/add/operator/map';
import {environment} from '../../../environments/environment';
import {Http, Headers, RequestOptions} from '@angular/http';

@Injectable()
export class AuthService {

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
    const reqOptions = new RequestOptions();
    reqOptions.headers = reqOptions.headers || new Headers();

    // All request headers
    if (environment.headers) {
      Object.keys(environment.headers).forEach(k => reqOptions.headers.append(k, environment.headers[k]));
    }

    // Add cookie headers
    reqOptions.withCredentials = environment.production;

    // execute
    return this.http.get(environment.apiEndpoint + '/api/v1/auth/bakery', reqOptions)
      .map(r => r.json())
      .toPromise()
      .then(res => Promise.resolve(res.authenticated))
      .catch(e => Promise.resolve(e.authenticated));
  };
}
