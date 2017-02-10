/* tslint:disable:no-unused-variable */

import {TestBed, async, inject} from '@angular/core/testing';
import {ErrorService} from './error.service';
import {RouterTestingModule} from '@angular/router/testing';

describe('ErrorService', () => {
  beforeEach(() => {
    TestBed.configureTestingModule({
      imports: [RouterTestingModule],
      providers: [ErrorService]
    });
  });

  it('should ...', inject([ErrorService], (service: ErrorService) => {
    expect(service).toBeTruthy();
  }));

  it('should report an event to bugsnag, and return the service for chaining', inject([ErrorService], (service: ErrorService) => {
    const result = service.reportError(Error('123'), 'Test', {}, 'info');
    expect(result).toEqual(jasmine.any(ErrorService));
  }));
});
