import {Injectable} from '@angular/core';
import 'rxjs/add/operator/toPromise';
import 'rxjs/add/operator/map';
import {environment} from '../../../environments/environment';
import {Http} from '@angular/http';

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
    return this.http.get(environment.apiEndpoint + '/api/v1/auth/bakery', {withCredentials: environment.production})
      .map(r => r.json())
      .toPromise()
      .then(res => Promise.resolve(res.authenticated))
      .catch(e => Promise.resolve(e.authenticated));
  };
}
