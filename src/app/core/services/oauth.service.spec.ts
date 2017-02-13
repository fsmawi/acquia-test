/* tslint:disable:no-unused-variable */

import { TestBed, async, inject } from '@angular/core/testing';
import { OauthService } from './oauth.service';
import {HttpModule, BaseRequestOptions, Http, ResponseOptions, Response} from '@angular/http';
import {MockBackend} from '@angular/http/testing';

describe('OauthService', () => {
  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [
        OauthService,
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

  it('should ...', inject([OauthService], (service: OauthService) => {
    expect(service).toBeTruthy();
  }));

  it('should set redirect url', inject([OauthService], (service: OauthService) => {
    service.setRedirectUrl('some url');
    expect(service.redirectUrl).toEqual('some url');
  }));

  it('should create a nonce', inject([OauthService], (service: OauthService) => {
    const nonce = service.createNonce();
    expect(nonce.length).toEqual(40);
  }));

  it('should fetch accessToken', inject([OauthService, MockBackend], (service: OauthService, mockBackend: MockBackend) => {
     setupConnections(mockBackend, {
      body: JSON.stringify({
        access_token: 'accesstoken',
        scope: 'user',
        token_type: 'bearer'
      }),
    });

    const params = {
      code: 'some code',
      state: 'some state',
      client_id: 'client id',
      client_secret: 'secret',
      redirect_uri: 'url redirect'
    };

    service.fetchOauthAccessToken(params).then(res => {
      expect(res.access_token).not.toBeUndefined();
      expect(res.access_token).toEqual('accesstoken');
    });
  }));

  describe('Test Login', () => {

    const params = {
      code: 'some code',
      state: 'some state'
    };

    const tokenParams = Object.assign({
      client_id: 'clientID',
      client_secret: 'client secret'
    }, params);

    it('should fail login when access code is wrong', inject([OauthService], (service: OauthService) => {

      spyOn(service, 'fetchOauthAccessToken').and.callFake(function() {
       return new Promise((resolve, reject) => {
          resolve({});
        });
      });

      service.login(params)
        .catch((e) => {
          expect(e.access_token).toBeUndefined();
        });
    }));

    it('should fail login when auth service fail', inject([OauthService], (service: OauthService) => {

      spyOn(service, 'fetchOauthAccessToken').and.callFake(function() {
       return new Promise((resolve, reject) => {
          reject({error: 'bad_verification_code'});
        });
      });

      service.login(params)
        .catch((e) => {
          expect(e.error).not.toBeUndefined();
          expect(e.error).toEqual('bad_verification_code');
        });
    }));

    it('should login successfully', inject([OauthService], (service: OauthService) => {

      spyOn(service, 'fetchOauthAccessToken').and.callFake(function() {
       return new Promise((resolve, reject) => {
          resolve({
                    access_token: 'accesstoken',
                    scope: 'somsstate',
                    token_type: 'bearer'
                  });
        });
      });

      service.login(params)
        .then((r) => {
          expect(r).toEqual('accesstoken');
        });

    }));

    it('should fail login when there is no access code', inject([OauthService], (service: OauthService) => {
      service.login({})
        .catch((e) => {
          expect(e.error).toEqual('error in temporary access code');
        });
    }));
  });
});
