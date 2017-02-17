/* tslint:disable:no-unused-variable */

import {TestBed, async, inject} from '@angular/core/testing';
import {AmplitudeService} from './amplitude.service';

describe('AmplitudeService', () => {
  beforeEach(() => {
    global['ampMock'] = {
      getInstance: () => {
        return {
          init: () => {
          },
          logEvent: () => {
            return true;
          }
        };
      }
    };
    TestBed.configureTestingModule({
      providers: [AmplitudeService]
    });
  });

  it('should initialize the amplitude service', inject([AmplitudeService], (service: AmplitudeService) => {
    expect(service).toBeTruthy();
    expect(service.amp).toBeTruthy();
  }));

  it('should log an amplitude event', inject([AmplitudeService], (service: AmplitudeService) => {
    expect(service.logEvent('event')).toBeTruthy();
  }));
});
