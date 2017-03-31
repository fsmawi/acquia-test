/* tslint:disable:no-unused-variable */
import {TestBed, async, inject} from '@angular/core/testing';
import {LiftService} from './lift.service';

describe('LiftService', () => {
  beforeEach(() => {
    global['_tcaq'] = {
      push: () => {
        return true;
      }
    };
    TestBed.configureTestingModule({
      providers: [LiftService]
    });
  });

  it('should create the service', inject([LiftService], (service: LiftService) => {
    expect(service).toBeTruthy();
  }));

  it('should set the bootstrap flag to true', inject([LiftService], (service: LiftService) => {
    expect(service).toBeTruthy();
    expect(LiftService.bootstrap).toEqual(true);
  }));

  it('should capture the event', inject([LiftService], (service: LiftService) => {
    expect(service).toBeTruthy();
    expect(LiftService.bootstrap).toEqual(true);
    expect(service.captureEvent('someEvent', {appId: 'someAppId'})).toEqual(true);
  }));

});
