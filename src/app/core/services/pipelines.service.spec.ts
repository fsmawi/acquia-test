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

  function setupConnections(mockBackend: MockBackend, options: any) {
    mockBackend.connections.subscribe((connection) => {
      connection.mockRespond(new Response(new ResponseOptions(options)));
    });
  }

  it('should ...', inject([PipelinesService], (service: PipelinesService) => {
    expect(service).toBeTruthy();
  }));

  it('should stop a job', inject([PipelinesService, MockBackend], (service: PipelinesService, mockBackend: MockBackend) => {

    setupConnections(mockBackend, {
      body: JSON.stringify({message: 'OK'})
    });

    service.stopJob('someApp', 'someJob').then(res => {
      expect(res.message).toEqual('OK');
    });
  }));

  it('should attach a Github repository', inject([PipelinesService, MockBackend], (service: PipelinesService, mockBackend: MockBackend) => {
     setupConnections(mockBackend, {
      body: JSON.stringify({
        success: true,
        deploy_key_url: 'key_url',
        webhook_url: 'webhook_url'
      }),
      // status: 201
    });

    service.attachGithubRepository('someToken', 'repoId', ['someAppId']).then(res => {
      expect(res.success).toEqual(true);
      expect(res.deploy_key_url).toEqual('key_url');
      expect(res.webhook_url).toEqual('webhook_url');
    });
  }));
});
