/* tslint:disable:no-unused-variable */

import {TestBed, async, inject} from '@angular/core/testing';
import {PipelinesService} from './pipelines.service';
import {HttpModule, BaseRequestOptions, Http, ResponseOptions, Response} from '@angular/http';
import {MockBackend} from '@angular/http/testing';

describe('PipelinesService', () => {
  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [
        PipelinesService,
        MockBackend,
        BaseRequestOptions,
        {
          provide: Http,
          useFactory: (mockBackend, options) => {
            return new Http(mockBackend, options);
          },
          deps: [MockBackend, BaseRequestOptions]
        }
      ],
      imports: []
    });
  });

  it('should ...', inject([PipelinesService], (service: PipelinesService) => {
    expect(service).toBeTruthy();
  }));

  it('should stop a job', inject([PipelinesService, MockBackend], (service: PipelinesService, mockBackend: MockBackend) => {
    // simulate http
    mockBackend.connections.subscribe((connection) => {
      connection.mockRespond(new Response(new ResponseOptions({
        body: JSON.stringify({message: 'OK'})
      })));
    });

    service.stopJob('someApp', 'someJob').then(res => {
      expect(res.message).toEqual('OK');
    });
  }));
});
