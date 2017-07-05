import {Injectable} from '@angular/core';
import {environment} from '../../../environments/environment';


// Global Scope, Window
// or mocked by scope vars in tests
declare const window, _tcaq;

@Injectable()
export class LiftService {

  /**
   * Flag to check the script injection
   */
  static bootstrap: boolean;

  /**
   * Parameters (keys) mapping with Lift custom meta column data fields (user defined fields)
   */
  eventsUDFMappings = {appId : 'event_udf5'};

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

  /**
   * Captures the event using acquia lift tracking
   * @param eventName
   * @param eventData
   */
  captureEvent(eventName: string, eventData: Object) {
    // Find if the event mapping is available in events hashmap
    // if available, assign a new param with the found value for eventData
    // and delete the key/value from eventData
    Object.keys(eventData).forEach((key) => {
      if (this.eventsUDFMappings[key]) {
        eventData[this.eventsUDFMappings[key]] = eventData[key];
        delete eventData[key];
      }
    });

    // assign the persona property
    Object.assign(eventData, {persona: 'Developer'});

    return _tcaq.push(['capture', eventName, eventData]);
  }
}
