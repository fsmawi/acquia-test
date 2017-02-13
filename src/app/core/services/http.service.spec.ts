/* tslint:disable:no-unused-variable */

import { TestBed, async, inject } from '@angular/core/testing';
import { HttpService } from './http.service';
import {HttpModule, BaseRequestOptions, Http, ResponseOptions, Response, RequestMethod} from '@angular/http';
import {MockBackend} from '@angular/http/testing';

describe('HttpService', () => {
  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [
        HttpService,
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

  const url = 'http://test.com';
  const options = {
    body: JSON.stringify({success: true})
  };
  const params = {
    param1: 'value1',
    param2: 'value2',
    param3: 'value3',
  };

  it('should ...', inject([HttpService], (service: HttpService) => {
    expect(service).toBeTruthy();
  }));

  it('should perform a get request', inject([HttpService, MockBackend], (service: HttpService, mockBackend: MockBackend) => {
    mockBackend.connections.subscribe((connection) => {
      expect(connection.request.method).toBe(RequestMethod.Get);
      expect(connection.request.url).toBe(url);
      connection.mockRespond(new Response(new ResponseOptions(options)));
    });

    service.promiseGetRequest(url, params).then(res => {
      expect(res.success).toEqual(true);
    });
  }));

  it('should perform a post request', inject([HttpService, MockBackend], (service: HttpService, mockBackend: MockBackend) => {
    mockBackend.connections.subscribe((connection) => {
      expect(connection.request.method).toBe(RequestMethod.Post);
      expect(connection.request.url).toBe(url);
      connection.mockRespond(new Response(new ResponseOptions(options)));
    });

    service.promisePostRequest(url, params, {}).then(res => {
      expect(res.success).toEqual(true);
    });
  }));
});
