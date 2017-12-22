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

  it('should validate json string', inject([ErrorService], (service: ErrorService) => {
    expect(service.isValidJson('{}')).toBeTruthy();
    expect(service.isValidJson('{"a": "b"}')).toBeTruthy();
    expect(service.isValidJson('invalid-json-string')).toBeFalsy();
  }));


  it('should check is the error is related to forbidden ip', inject([ErrorService], (service: ErrorService) => {
    service.apiError({status: 403, _body: 'forbidden_ip'});
    expect(service.isForbiddenIPError()).toBeTruthy();
    service.apiError({status: 404, _body: 'forbidden_ip'});
    expect(service.isForbiddenIPError()).toBeFalsy();
    service.apiError({status: 403, _body: 'forbidden'});
    expect(service.isForbiddenIPError()).toBeFalsy();
    service.apiError({status: 403, _body: '{}'});
    expect(service.isForbiddenIPError()).toBeFalsy();
  }));

  it('should check is the error is related to forbidden pipelines', inject([ErrorService], (service: ErrorService) => {
    service.apiError({status: 403, _body: '{"message": "forbidden_pipelines: user doesn\'t have execute pipelines permission"}'});
    expect(service.isForbiddenPipelinesError()).toBeTruthy();
    service.apiError({status: 404, _body: '{"message": "forbidden_pipelines: user doesn\'t have execute pipelines permission"}'});
    expect(service.isForbiddenPipelinesError()).toBeFalsy();
    service.apiError({status: 403, _body: 'error-string'});
    expect(service.isForbiddenPipelinesError()).toBeFalsy();
    service.apiError({status: 403, _body: '{}'});
    expect(service.isForbiddenPipelinesError()).toBeFalsy();
  }));

});
