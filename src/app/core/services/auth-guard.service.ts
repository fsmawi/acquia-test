import {Injectable} from '@angular/core';
import {
  CanActivate,
  Router,
  ActivatedRouteSnapshot,
  RouterStateSnapshot,
  CanActivateChild
} from '@angular/router';
import {AuthService} from './auth.service';
import {environment} from '../../../environments/environment';

// Global Scope, Window
// or mocked by scope vars in tests
declare const window;
declare const document;

@Injectable()
export class AuthGuard implements CanActivate, CanActivateChild {

  /**
   * Builds the service
   * @param authService
   * @param router
   */
  constructor(private authService: AuthService, private router: Router) {
  }

  /**
   * Parent Route Guard. Checks API for authentication
   * @param route
   * @param state
   * @returns {Promise<boolean>}
   */
  canActivate(route: ActivatedRouteSnapshot, state: RouterStateSnapshot): Promise<boolean> {
    // catch redirects with mock api usage
    if (route.queryParams['HTTP_X_ACQUIA_PIPELINES_N3_APIFILE'] !== undefined) {
      environment.headers['X-ACQUIA-PIPELINES-N3-APIFILE'] = route.queryParams['HTTP_X_ACQUIA_PIPELINES_N3_APIFILE'];
    }
    return this.checkLogin(state);
  }

  /**
   * Subscribes as the same canActivate guard
   * @param route
   * @param state
   * @returns {Promise<boolean>}
   */
  canActivateChild(route: ActivatedRouteSnapshot, state: RouterStateSnapshot): Promise<boolean> {
    return this.canActivate(route, state);
  }

  /**
   * Checks auth at the API, and redirects if needed
   * @returns {Promise<boolean>}
   */
  checkLogin(state: RouterStateSnapshot): Promise<boolean> {
    return this.authService.isLoggedIn()
      .then(isLoggedIn => {
        if (isLoggedIn) {
          return Promise.resolve(true);
        } else {
          if (environment.production && environment.name === 'prod') {
            if (!environment.standalone) {
              // get cloud path from referrer
              const l = document.createElement('a');
              l.href = document.referrer;
              window.top.location.href = `${environment.authAccountRedirect}/?site=cloud&path=${l.pathname}`;
            } else {
              window.location.href = `${environment.authAccountRedirect}/?site=pipelines&path=${state.url}`;
            }
          } else {
            this.router.navigateByUrl(`/auth/tokens?redirectUrl=${state.url}`);
          }
        }
        return Promise.resolve(false);
      });
  }
}
