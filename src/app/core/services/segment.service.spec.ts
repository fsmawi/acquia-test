/* tslint:disable:no-unused-variable */

import {TestBed, async, inject} from '@angular/core/testing';
import {SegmentService} from './segment.service';

describe('SegmentService', () => {
  beforeEach(() => {
    global['analyticsMock'] = true;
    global['analytics'] = {
      load: (key: string) => {
        return true;
      },
      page: () => {
        return true;
      },
      track: (eventName: string, eventData: Object) => {
        return 'success';
      }
    };
    TestBed.configureTestingModule({
      providers: [SegmentService]
    });
  });

  it('should create the service', inject([SegmentService], (service: SegmentService) => {
    expect(service).toBeTruthy();
  }));

  it('should call the trackEvent', inject([SegmentService], (service: SegmentService) => {
    expect(service).toBeTruthy();
    const res = service.trackEvent('EventName' , { data : 'DATA' });
    expect(res).toEqual('success');
  }));

});
