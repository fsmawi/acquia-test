/* tslint:disable:no-unused-variable */

import {TestBed, async, inject} from '@angular/core/testing';
import {HttpModule, BaseRequestOptions, Http, ResponseOptions, Response} from '@angular/http';
import {MockBackend} from '@angular/http/testing';

import {PipelinesService} from './pipelines.service';
import {Pipeline} from '../models/pipeline';
import {Application} from '../models/application';

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

  it('should create the service', inject([PipelinesService], (service: PipelinesService) => {
    expect(service).toBeTruthy();
  }));

  it('should authenticate with bakery', inject([PipelinesService, MockBackend], (service: PipelinesService, mockBackend: MockBackend) => {

    setupConnections(mockBackend, {
      body: JSON.stringify({authenticated: true})
    });

    service.authBakery().then(res => {
      expect(res.authenticated).toEqual(true);
    });
  }));

  it('should stop a job', inject([PipelinesService, MockBackend], (service: PipelinesService, mockBackend: MockBackend) => {

    setupConnections(mockBackend, {
      body: JSON.stringify({message: 'OK'})
    });

    service.stopJob('someApp', 'someJob').then(res => {
      expect(res.message).toEqual('OK');
    });
  }));

  it('should get the encrypted value', inject([PipelinesService, MockBackend], (service: PipelinesService, mockBackend: MockBackend) => {

    setupConnections(mockBackend, {
      body: 'encrypted-value'
    });

    service.getEncryptedValue('someApp', 'someDataItem').then(res => {
      expect(res).toEqual('encrypted-value');
    });
  }));

  it('should attach a Github repository',
    inject([PipelinesService, MockBackend], (service: PipelinesService, mockBackend: MockBackend) => {
      setupConnections(mockBackend, {
        body: JSON.stringify({
          success: true,
          deploy_key_url: 'key_url',
          webhook_url: 'webhook_url'
        })
      });

      service.attachOauthGitRepository('repoId', 'someAppId', 'github').then(res => {
        expect(res.success).toEqual(true);
        expect(res.deploy_key_url).toEqual('key_url');
        expect(res.webhook_url).toEqual('webhook_url');
      });
    }));

  it('should remove GitHub Authentication from the app',
    inject([PipelinesService, MockBackend], (service: PipelinesService, mockBackend: MockBackend) => {
      setupConnections(mockBackend, {
        body: JSON.stringify({
          status: 204,
          success: true
        })
      });

      service.removeOauthGitAuth('repo', 'app-id', 'github').then(res => {
        expect(res.status).toBe(204);
      });
    }));

  it('should update webhooks for the app',
    inject([PipelinesService, MockBackend], (service: PipelinesService, mockBackend: MockBackend) => {
      setupConnections(mockBackend, {
        body: JSON.stringify({
          status: 204,
          success: true
        })
      });

      service.updateWebhooks('app-id', true).then(res => {
        expect(res.success).toBe(true);
      });
    }));

  it('should get pipelines given an application ID',
    inject([PipelinesService, MockBackend], (service: PipelinesService, mockBackend: MockBackend) => {
      const pipeline = new Pipeline({
        repo_data: {
          repos: [
            {
              name: 'acquia/repo1',
              link: 'https://github.com/acquia/repo1',
              type: 'github'
            },
            {
              name: 'acquia/repo2',
              link: 'https://github.com/acquia/repo2',
              type: 'github'
            }
          ],
          branches: 'test11,test12'
        }
      });

      setupConnections(mockBackend, {
        body: JSON.stringify([pipeline])
      });

      service.getPipelineByAppId('someAppId').then(res => {
        expect(res.repo_data.repos[2].link).toEqual('https://github.com/acquia/repo2');
      });
    }));

  it('should get branches for an application ID',
    inject([PipelinesService, MockBackend], (service: PipelinesService, mockBackend: MockBackend) => {
      const application = new Application({
        branches: ['test11', 'test12']
      });

      setupConnections(mockBackend, {
        body: JSON.stringify(application)
      });

      service.getBranches('someAppId').then(res => {
        expect(res.length).toEqual(2);
        expect(res[1]).toEqual('test12');
      });
    }));

  it('should get the cloud API linking status',
    inject([PipelinesService, MockBackend], (service: PipelinesService, mockBackend: MockBackend) => {
      setupConnections(mockBackend, {
        body: JSON.stringify({
          status: 200,
          is_token_valid: true,
          token_attached: true,
          can_execute_pipelines: true

        })
      });

      service.getN3TokenInfo('app-id').then(res => {
        expect(res.is_token_valid).toBe(true);
        expect(res.token_attached).toBe(true);
        expect(res.can_execute_pipelines).toBe(true);
      });
    }));


  it('should set the cloud API tokens',
    inject([PipelinesService, MockBackend], (service: PipelinesService, mockBackend: MockBackend) => {
      setupConnections(mockBackend, {
        body: JSON.stringify({
          status: 200,
          success: true
        })
      });

      service.setN3Credentials('app-id').then(res => {
        expect(res.success).toBe(true);
      });
    }));

  it('should get application information',
    inject([PipelinesService, MockBackend], (service, mockBackend) => {

      setupConnections(mockBackend, {
        body: JSON.stringify({
          repo_url: 'https://github.com/acquia/repo1.git',
          repo_name: 'acquia/repo1',
          repo_type: 'github'
        })
      });

      service.getApplicationInfo()
        .then((info) => {
          expect(info.repo_name).toEqual('acquia/repo1');
          expect(info.repo_url).toEqual('https://github.com/acquia/repo1.git');
          expect(info.repo_type).toEqual('github');
        });

    }));

  it('should direct start a job',
    inject([PipelinesService, MockBackend], (service: PipelinesService, mockBackend: MockBackend) => {
      const pipeline = new Pipeline({
        pipeline_id: 'pipeline-id',
        repo_data: {
          branches: ['test11', 'test12']
        }
      });

      setupConnections(mockBackend,
        {body: JSON.stringify({job_id: 'job-id'})}
      );

      spyOn(service, 'getPipelineByAppId').and.callFake(() => {
        return Promise.resolve([pipeline]);
      });

      service.directStartJob('someAppId', 'someBranch').then(res => {
        expect(res).toBeTruthy();
        expect(res.job_id).toBe('job-id');
      });
    }));

});
