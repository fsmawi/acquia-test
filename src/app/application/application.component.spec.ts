/* tslint:disable:no-unused-variable */
import {async, ComponentFixture, TestBed, fakeAsync, tick, inject} from '@angular/core/testing';
import {EventEmitter} from '@angular/core';
import {MaterialModule} from '@angular/material';
import {BaseRequestOptions, Http, ResponseOptions, Response} from '@angular/http';
import {MockBackend} from '@angular/http/testing';
import {RouterTestingModule} from '@angular/router/testing';
import {ActivatedRoute} from '@angular/router';

import {ApplicationComponent} from './application.component';
import {ErrorService} from '../core/services/error.service';
import {FlashMessageService} from '../core/services/flash-message.service';
import {PipelinesService} from '../core/services/pipelines.service';
import {SegmentService} from '../core/services/segment.service';
import {ElementalModule} from '../elemental/elemental.module';
import {SharedModule} from '../shared/shared.module';

class MockActivatedRoute {
  params = new EventEmitter<any>();
}

class MockFlashMessage {
  showError(message: string, e: any) {
    return true;
  }
}

function setupConnections(mockBackend: MockBackend, options: any) {
  mockBackend.connections.subscribe((connection) => {
    connection.mockRespond(new Response(new ResponseOptions(options)));
  });
}

describe('ApplicationComponent', () => {
  let component: ApplicationComponent;
  let fixture: ComponentFixture<ApplicationComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [
        ApplicationComponent
      ],
      providers: [
        ErrorService,
        FlashMessageService,
        MockBackend,
        BaseRequestOptions,
        PipelinesService,
        SegmentService,
        {provide: ActivatedRoute, useClass: MockActivatedRoute},
        {provide: FlashMessageService, useClass: MockFlashMessage},
        {
          provide: Http,
          useFactory: (mockBackend, options) => {
            return new Http(mockBackend, options);
          },
          deps: [MockBackend, BaseRequestOptions]
        }
      ],
      imports: [
        MaterialModule.forRoot(),
        ElementalModule,
        RouterTestingModule,
        SharedModule
      ]
    })
      .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(ApplicationComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should get git info using pipeline service', fakeAsync(inject([ActivatedRoute, MockBackend], (route, mockBackend) => {

    setupConnections(mockBackend, {
      body: JSON.stringify([{
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
      }])
    });

    component.getConfigurationInfo();
    tick();
    fixture.detectChanges();
    expect(component.gitUrl).toEqual('https://github.com/acquia/repo1');
    expect(component.gitClone).toEqual('git clone --branch [branch] https://github.com/acquia/repo1 [destination]');
  })));

  it('should show error when getting empty array from pipeline service',
    fakeAsync(inject([ActivatedRoute, FlashMessageService, MockBackend], (route, flashMessage, mockBackend) => {

      setupConnections(mockBackend, {
        body: JSON.stringify([])
      });

      spyOn(flashMessage, 'showError');

      component.getConfigurationInfo();
      tick();
      expect(flashMessage.showError).toHaveBeenCalledWith('Unable to find pipeline information for this application.');
    })));

  it('should show error when getting empty array from pipeline service',
    fakeAsync(inject([ActivatedRoute, FlashMessageService, MockBackend], (route, flashMessage, mockBackend) => {

      setupConnections(mockBackend, {
        body: JSON.stringify({})
      });

      spyOn(flashMessage, 'showError');

      component.getConfigurationInfo();
      tick();
      expect(flashMessage.showError).toHaveBeenCalled();
    })));
});
