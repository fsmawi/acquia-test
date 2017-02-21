/* tslint:disable:no-unused-variable */

import {TestBed, async, inject} from '@angular/core/testing';
import {AuthGuard} from './auth-guard.service';
import {AuthService} from './auth.service';
import {RouterTestingModule} from '@angular/router/testing';
import {HttpModule} from '@angular/http';

describe('AuthGuardService', () => {
  beforeEach(() => {
    TestBed.configureTestingModule({
      imports: [RouterTestingModule, HttpModule],
      providers: [AuthGuard, AuthService]
    });
  });

  it('should ...', inject([AuthGuard], (service: AuthGuard) => {
    expect(service).toBeTruthy();
  }));
});
