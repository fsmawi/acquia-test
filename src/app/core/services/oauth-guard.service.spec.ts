import {ActivatedRouteSnapshot, Router} from '@angular/router';
import {TestBed, async, inject} from '@angular/core/testing';
import {OauthGuard} from './oauth-guard.service';
import {RouterTestingModule} from '@angular/router/testing';
import {HttpModule} from '@angular/http';

class MockRouter {
  navigate(route) {
    return route;
  }
}

class MockActivatedRouteSnapshot {

  public params = {'repo-type': 'acquia-git'};

  setType (type) {
    this.params['repo-type'] = type;
  }
}

describe('OauthGuardService', () => {
  beforeEach(() => {
    TestBed.configureTestingModule({
      imports: [
        RouterTestingModule,
        HttpModule
      ],
      providers: [
        OauthGuard,
        {provide: Router, useClass: MockRouter},
        {provide: ActivatedRouteSnapshot, useClass: MockActivatedRouteSnapshot},
      ],
    });
  });

  it('should ...', inject([OauthGuard], (service) => {
    expect(service).toBeTruthy();
  }));

  it('should navigate to error page when type is acquia-git',
    inject([OauthGuard, ActivatedRouteSnapshot, Router], (service, route, router) => {

    spyOn(router, 'navigate');

    service.canActivate(route);
    expect(router.navigate).toHaveBeenCalled();
  }));

  it('should validate route', inject([OauthGuard, ActivatedRouteSnapshot], (service, route) => {
    route.setType('github');
    service.canActivate(route).then((res) => {
      expect(res).toEqual(true);
    });
  }));
});
