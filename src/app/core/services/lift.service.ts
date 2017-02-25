import {Injectable} from '@angular/core';
import {environment} from '../../../environments/environment';


// Global Scope, Window
// or mocked by scope vars in tests
declare const window;

@Injectable()
export class LiftService {

  /**
   * Flag to check the script injection
   */
  static bootstrap: boolean;

  /**
   * Builds the service
   */
  constructor() {
    // Static flag to check if the script had been appended
    if (!LiftService.bootstrap) {
      // Setup the AcquiaLift variables from the environment
      window.AcquiaLift = environment.lift;

      // Creates a <script> tag to download lift.js and appends it to <head>
      // the Lift Experience Builder script
      const node = document.createElement('script');
      node.type = 'text/javascript';
      node.src = 'https://lift3assets.lift.acquia.com/stable/lift.js';
      node.async = true;
      node.charset = 'utf-8';
      document.getElementsByTagName('head')[0].appendChild(node);

      // Flag to maintain bootstrap/injection status
      LiftService.bootstrap = true;
    }
  }
}
