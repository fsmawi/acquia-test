/* tslint:disable:no-unused-variable */

import { TestBed, async, inject } from '@angular/core/testing';
import { GithubService } from './github.service';
import {HttpModule, BaseRequestOptions, Http, ResponseOptions, Response, RequestMethod} from '@angular/http';
import {MockBackend} from '@angular/http/testing';

describe('GithubService', () => {
  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [
        GithubService,
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

  it('should ...', inject([GithubService], (service: GithubService) => {
    expect(service).toBeTruthy();
  }));

  it('should get list of repositories', inject([GithubService, MockBackend], (service: GithubService, mockBackend: MockBackend) => {

    const options = {
      body: JSON.stringify([
         {full_name: 'repo1', url: 'url1'},
         {full_name: 'repo2', url: 'url2'},
         {full_name: 'repo3', url: 'url3'}
      ])
    };

    mockBackend.connections.subscribe((connection) => {
      expect(connection.request.method).toBe(RequestMethod.Get);
      connection.mockRespond(new Response(new ResponseOptions(options)));
    });

    service.getRepositories('token').then(res => {
      expect(res.length).toEqual(3);
      expect(res[0].full_name).toEqual('repo1');
      expect(res[1].full_name).toEqual('repo2');
    });
  }));
});
