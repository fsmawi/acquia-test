import {Injectable} from '@angular/core';
import {environment} from '../../../environments/environment';

// Global Scope, analytics will either come from a script tag in index.html,
// or mocked by scope vars in tests
declare const analytics, analyticsMock;

@Injectable()
export class SegmentService {

  /**
   * One time initialization flag
   */
  static registered: boolean;

  /**
   * Builds the service
   */
  constructor() {
    if (environment.production || analyticsMock) {
      if (!SegmentService.registered) {
        analytics.load(environment.segmentWriteKey);
        analytics.page();
        SegmentService.registered = true;
      }
    }
  }

  /**
   * Tracks the event
   * @param eventIdentifier
   * @param eventData
   */
  trackEvent(eventIdentifier: string, eventData: Object) {
    if (environment.production || analyticsMock) {
      return analytics.track(eventIdentifier, eventData);
    }
  }

  /**
   * Tracks a page view
   * @param name
   * @returns {any|boolean}
   */
  page(name: string) {
    if (environment.production || analyticsMock) {
      return analytics.page(name);
    }
  }
}
