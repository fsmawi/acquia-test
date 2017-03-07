/* tslint:disable:no-unused-variable */

import {TestBed, async, inject} from '@angular/core/testing';
import {BugsnagService} from './bugsnag.service';
import {environment} from '../../../environments/environment';

declare const window;

describe('BugsnagService', () => {
  beforeEach(() => {
    global['envProdMock'] = true;
    TestBed.configureTestingModule({
      providers: [
        BugsnagService
        ]
    });
  });

  it('should create the service', inject([BugsnagService], (service: BugsnagService) => {
    expect(service).toBeTruthy();
  }));

  it('should test that script is injected', inject([BugsnagService], (service: BugsnagService) => {
    expect(BugsnagService.bootstrap).toEqual(true);
  }));
});
