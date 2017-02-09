import {Injectable} from '@angular/core';
import {environment} from '../../../environments/environment';

declare const Bugsnag;

@Injectable()
export class ErrorService {

  constructor() {
  }

  apiError(e: Error) {
    // TODO
    console.error(e);
  }

  /**
   * Reports an error to bugsnag
   * @param e Error to report
   * @param name Error label name
   * @param meta Custom object to save with the error
   * @param severity info|warning|error
   * @returns {ErrorService}
   */
  reportError(e: Error, name: string, meta: any, severity: string) {
    if (environment.production) {
      Bugsnag.notifyException(e, name, meta, severity);
    }
    return this;
  }
}
