/* tslint:disable:no-unused-variable */
import {async, ComponentFixture, TestBed, fakeAsync, tick, inject} from '@angular/core/testing';
import {EventEmitter} from '@angular/core';
import {MaterialModule} from '@angular/material';
import {BaseRequestOptions, Http, ResponseOptions, Response} from '@angular/http';
import {MockBackend} from '@angular/http/testing';
import {RouterTestingModule} from '@angular/router/testing';
import {ActivatedRoute, Router} from '@angular/router';

import {ApplicationComponent} from './application.component';
import {ErrorService} from '../core/services/error.service';
import {FlashMessageService} from '../core/services/flash-message.service';
import {PipelinesService} from '../core/services/pipelines.service';
import {SegmentService} from '../core/services/segment.service';
import {ElementalModule} from '../elemental/elemental.module';
import {SharedModule} from '../shared/shared.module';
import {N3Service} from '../core/services/n3.service';
import {ConfirmationModalService} from '../core/services/confirmation-modal.service';


class MockActivatedRoute {
  params = new EventEmitter<any>();
}

class MockConfirmationModalService {
  openDialog(title: string, message: string, primaryActionText: string, secondaryActionText = '') {
    return Promise.resolve(true);
  }
}

class MockFlashMessage {
  showError(message: string, e: any) {
    return true;
  }

  showInfo(message: string, e: any = {}) {
    return true;
  }

  showSuccess(message: string, e: any = {}) {
    return true;
  }
}

class MockN3Service {
  getEnvironments(appId: string) {
    return Promise.resolve([{ vcs : { type : 'git'}}]);
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
        {provide: N3Service, useClass: MockN3Service},
        {provide: ActivatedRoute, useClass: MockActivatedRoute},
        {provide: FlashMessageService, useClass: MockFlashMessage},
        {provide: ConfirmationModalService, useClass: MockConfirmationModalService},
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
        SharedModule,
        RouterTestingModule
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
      body: JSON.stringify({
        'undefined': {
          repo_url: 'https://github.com/acquia/repo1.git',
          connected: true
        }
      })
    });

    component.getConfigurationInfo();
    tick();
    fixture.detectChanges();
    expect(component.gitUrl).toEqual('https://github.com/acquia/repo1.git');
    expect(component.gitClone).toEqual('git clone --branch [branch] https://github.com/acquia/repo1.git [destination]');
  })));

  it('should show a not connected alert when the repo is not connected',
    fakeAsync(inject([ActivatedRoute, FlashMessageService, MockBackend], (route, flashMessage, mockBackend) => {

      setupConnections(mockBackend, {
        body: JSON.stringify({})
      });

      spyOn(flashMessage, 'showInfo');

      component.getConfigurationInfo();
      tick();
      expect(flashMessage.showInfo).toHaveBeenCalledWith('You are not connected yet');
    })));

  it('should show a success message after removing GitHub authentication',
    fakeAsync(inject([ActivatedRoute, FlashMessageService, MockBackend],
                  (route, flashMessage, mockBackend) => {

      setupConnections(mockBackend, {
        body: JSON.stringify({
          'status' : 204
        })
      });

      spyOn(flashMessage, 'showSuccess');
      component.gitUrl = 'git@github.com:aq/pipe.git';
      component.appId = 'appId';
      component.removeAuth();
      tick();
      expect(flashMessage.showSuccess).toHaveBeenCalledWith('GitHub authentication has been removed.');
    })));

});
