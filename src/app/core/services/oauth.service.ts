import { Injectable } from '@angular/core';
import { HttpService } from './http.service';
import { Http, Headers} from '@angular/http';

@Injectable()
export class OauthService extends HttpService {

  /**
   * Authorisation endpoint
   * @type {String}
   */
  oauthEndpoint = '';

  /**
   * Authorisation token endpoint
   * @type {String}
   */
  oauthTokenEndpoint = '';

  /**
   * Api endpoint
   * @type {String}
   */
  apiEndpoint = '';

  /**
   * client ID
   * @type {String}
   */
  clientId = '';

  /**
   * Client Secret
   * @type {String}
   */
  clientSecret = '';

  /**
   * Redirect URI
   * @type {String}
   */
  redirectUrl = '';

  /**
   * Scopes
   * @type {String}
   */
  scopes = '';

  constructor(protected http: Http) {
    super(http);
  }

  /**
   * Set OAuth parameters
   * @param params
   */
  setParams(params) {
    this.oauthEndpoint = params.oauthEndpoint;
    this.oauthTokenEndpoint = params.oauthTokenEndpoint;
    this.apiEndpoint = params.apiEndpoint;
    this.clientId = params.clientId;
    this.clientSecret = params.clientSecret;
    this.redirectUrl = params.redirectUrl;
    this.scopes = params.scopes;
  }

  /**
   * Override the default redirectUrl
   * @param url
   */
  setRedirectUrl (url) {
    this.redirectUrl = url;
  }

  /**
   * Redirect to authentication page
   */
  authenticate() {
    const url = this.oauthEndpoint
      + '?client_id='
      + encodeURIComponent(this.clientId)
      + '&state='
      + encodeURIComponent(this.createNonce())
      + '&redirect_uri='
      + encodeURIComponent(this.redirectUrl)
      + '&scope='
      + encodeURIComponent(this.scopes);

    window.top.location.href = url;
  };

  /**
   * Log in to get the access_token
   * @param  options
   * @return {Promise<any>}
   */
  login(options): Promise<any> {

    return new Promise((resolve, reject) => {

      if (!options['code'] || !options['state']) {
        reject({error: 'error in temporary access code',
                error_description: 'error in temporary access code'});
        return;
      }

      const params = {
        code: options['code'],
        state: options['state'],
        client_id: this.clientId,
        client_secret: this.clientSecret,
        redirect_uri: this.redirectUrl
      };

      this.fetchOauthAccessToken(params)
        .then(r => {
          if (r.access_token !== undefined) {
            resolve(r.access_token);
          } else {
            reject(r);
          }
        })
        .catch(e => reject(e));
    });
  }

  /**
   * Request access_token
   * @param params
   */
  fetchOauthAccessToken(params) {
    const headers = new Headers({ 'Accept': 'application/json' });
    return this.promisePostRequest(this.oauthTokenEndpoint, params, { headers: headers });
  }

  /**
   * Create nonce
   */
  createNonce() {

    let text = '';
    const possible = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

    for (let i = 0; i < 40; i++) {
      text += possible.charAt(Math.floor(Math.random() * possible.length));
    }

    return text;
  }
}
