import {Injectable} from '@angular/core';
import {environment} from '../../../environments/environment';

// Global Scope, Amplitude will either come from a script tag in index.html,
// or mocked by scope vars in tests
declare const amplitude, ampMock;

@Injectable()
export class AmplitudeService {

  /**
   * One time initialization flag
   */
  static registered: boolean;

  /**
   * Amplitude Instance
   */
  amp: any;

  /**
   * Build the service
   */
  constructor() {
    if (environment.production || ampMock) {
      this.amp = ampMock ? ampMock.getInstance() : amplitude.getInstance();
      this.amp.init(environment.amplitudeAPIKey);

      // Global event to see how people load the app in/out of IFrame
      if (!AmplitudeService.registered) {
        let inIFrame = false;

        // use try/catch because same origin policy may through an error
        try {
          inIFrame = window.self !== window.top;
        } catch (e) {
          inIFrame = true;
        }

        //  Log event for type if load
        if (inIFrame) {
          this.amp.logEvent('LOADED_IFRAME_APP');
        } else {
          this.amp.logEvent('LOADED_SOLO_APP');
        }

        // register load complete
        AmplitudeService.registered = true;
      }
    }
  }

  /**
   * Log Event Helper
   * @param eventName
   * @returns {any}
   */
  logEvent(eventName: string) {
    return this.fn('logEvent', [eventName]);
  }

  /**
   * Runs an amplitude sdk function only in production
   * @param name
   * @param argArray
   */
  fn(name: string, argArray) {
    if (environment.production || ampMock) {
      return this.amp[name].apply(this, argArray);
    }
  }
}
