import { Injectable } from '@angular/core';
import { Http, Headers} from '@angular/http';
import { OauthService } from './oauth.service';
import { environment } from '../../../environments/environment';
import 'rxjs/add/operator/toPromise';

@Injectable()
export class GithubService extends OauthService {

  /**
   * Github service parameters
   * @type Object
   */
  AUTH = environment.auth.github;

  /**
   * Initiate Service
   * @param http
   */
  constructor(protected http: Http) {
    super(http);
    this.setParams(this.AUTH);
  }

  /**
   * Get all repositories of connected user
   * @param token
   */
  getRepositories(token: string)  {
    const headers = new Headers();
    headers.set('Authorization', 'token ' + token);
    return this.promiseGetRequest(this.apiEndpoint + `/user/repos`, {headers});
  }
}
