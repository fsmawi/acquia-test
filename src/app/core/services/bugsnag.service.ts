import {Injectable} from '@angular/core';
import {environment} from '../../../environments/environment';

// Global Scope, Window
declare const window;

// Global Scope, envProdMock is used to simulate production environment in tests
declare const envProdMock;

@Injectable()
export class BugsnagService {

  /**
   * Flag to check the script injection
   * @type {boolean}
   */
   static bootstrap: boolean;

   /**
    * Build the service
    */
  constructor() {

     if (envProdMock || environment.production && !BugsnagService.bootstrap && window.location.hostname === 'pipelines.acquia.com') {

      const node = document.createElement('script');
      node.type = 'text/javascript';
      node.src = '//d2wy8f7a9ursnm.cloudfront.net/bugsnag-3.min.js';
      node.async = true;
      document.getElementsByTagName('head')[0].appendChild(node);

      // set API KEY
      node.onload = function () {
        window.Bugsnag.apiKey = environment.bugsnagAPIKey;
      };

      BugsnagService.bootstrap = true;
    }
  }
}
