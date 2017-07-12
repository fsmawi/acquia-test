import {Injectable} from '@angular/core';
import {
  CanActivate,
  Router,
  ActivatedRouteSnapshot,
  CanActivateChild
} from '@angular/router';
import {environment} from '../../../environments/environment';
import {repoType} from '../repository-types';


@Injectable()
export class OauthGuard implements CanActivate, CanActivateChild {

  /**
   * Builds the service
   */
  constructor(private router: Router) {
  }


  /**
   * Route Guard. Checks repository type
   * @param route
   * @returns {Promise<boolean>}
   */
  canActivate(route: ActivatedRouteSnapshot): Promise<boolean> {
    if (route.params['repo-type'] && repoType[route.params['repo-type']]) {
      return Promise.resolve(true);
    } else {
      this.router.navigate(
        ['/error'],
        {
          queryParams: {
            errorCode: 404,
            errorTitle: 'Not Found',
            errorMessage: 'Unknown repo type',
            tagMessage: 'Homepage',
            tagLink: '/'
          }
        });
    }
  }

  /**
   * Subscribes as the same canActivate guard
   * @param route
   * @returns {Promise<boolean>}
   */
  canActivateChild(route: ActivatedRouteSnapshot): Promise<boolean> {
    return this.canActivate(route);
  }
}
