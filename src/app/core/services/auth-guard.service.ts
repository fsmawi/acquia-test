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
    return this.checkLogin();
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
  checkLogin(): Promise<boolean> {
    return this.authService.isLoggedIn()
      .then(isLoggedIn => {
        if (isLoggedIn) {
          return Promise.resolve(true);
        } else if (environment.authRedirect) {
          window.top.location.href = environment.authRedirect;
        } else {
          this.router.navigateByUrl(environment.authRedirect || '/auth/tokens');
        }
        return Promise.resolve(false);
      });
  }
}
