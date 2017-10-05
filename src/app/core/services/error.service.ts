import {Injectable} from '@angular/core';
import {Router} from '@angular/router';
import {Response} from '@angular/http';

import {BaseApplication} from '../classes/base-application';
import {environment} from '../../../environments/environment';

declare const Bugsnag;

@Injectable()
export class ErrorService {

  /**
   * Error to be stored
   */
  error: Response;

  /**
   * Error messages to be shown wrt status codes
   */
  errorMessages = {
    '400': `Looks like poor API manners. We cannot make your request at this time.`,
    '401': `You are unauthorized to perform that action. Reach out to your manager or Acquia to request access.`,
    '403': `You are unauthorized to perform that action. Reach out to your manager or Acquia to request access.`,
    '404': `Yikes! We can’t find the page you're looking for.`,
    '500': `Oops! Looks like we messed up. Give us a moment to fix it.`,
    '501': `Oops! Looks like we messed up. Give us a moment to fix it.`,
    '503': `Oops! Looks like we messed up. Give us a moment to fix it.`,
    'forbidden_ip': `Oops! Looks like the application is configured to restrict access to certain IP addresses.
      If IP whitelisting is enabled, you have to use the CLI for using Pipelines.`
  };

  /**
   * Builds the component
   * @param router
   */
  constructor(private router: Router) {
  }

  /**
   * Handles the error and stores
   * @param e
   * @returns {ErrorService}
   */
  apiError(e) {
    this.error = e;
    return this;
  }

  /**
   * Show/handle error according the returned status code
   * @param tagMessage
   * @param tagLink
   */
  showError(tagMessage = '', tagLink = '') {
    // Show error screen for 400s
    if (this.error.status === 400 ||
      this.error.status === 403 ||
      this.error.status === 404) {
      this.showErrorScreen(tagMessage, tagLink);
    } else {
      // TO DO
      // Handle 500s
    }
  }

  /**
   * Show error screen with respect to the status code
   * @param tagMessage
   * @param tagLink
   * @param tagTarget
   */
  showErrorScreen(tagMessage = '', tagLink = '', tagTarget = '_self') {
    let forbiddenIPError = false;
    // Handle the IP whitelisting (where the Pipelines API's IP is restricted)
    if (this.error.status === 403 && this.error['_body'] !== undefined
      && typeof this.error['_body'] === 'string' && this.error['_body'].includes('forbidden_ip')) {
      forbiddenIPError = true;
      // Redirecting to Application Homepage (cloud) or standalone Homepage
      tagMessage = 'Homepage';
      tagTarget = '_top';
      tagLink =  environment.standalone ? '/applications/' :
        environment.authCloudRedirect + '/app/develop/' + (BaseApplication._appId ? 'applications/' + BaseApplication._appId : '');
    }
    this.router.navigate(
      ['/error'],
      {
        queryParams: {
          errorCode: this.error.status,
          errorTitle: this.error.statusText,
          errorMessage: (this.error.status === 403 && this.error['_body'] !== undefined) ?
            forbiddenIPError ? this.errorMessages['forbidden_ip'] : // show forbidden_ip error
              this.error['_body'] : this.errorMessages[this.error.status],
          tagMessage: tagMessage,
          tagLink: tagLink,
          tagTarget: tagTarget
        }
      });
  }

  /*
   * Reports an error to bugsnag
   * @param e Error to report
   * @param name Error label name
   * @param meta Custom object to save with the error
   * @param severity info|warning|error
   * @returns {ErrorService}
   */
  reportError(e: Error, name: string, meta: any, severity: string) {

    // Catch cancelled requests from the browser, and don't report
    // Where status is status code, and type is the @angular/http/Response.type enum (failed/3)
    if (e['status'] && e['type'] && e['status'].toString() === '0' && e['type'].toString() === '3') {
      return this;
    }

    if (environment.production) {
      meta.rawError = e;
      Bugsnag.notifyException(e, name, meta, severity);
    }
    return this;
  }
}
