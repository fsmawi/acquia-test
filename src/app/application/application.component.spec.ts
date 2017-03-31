/* tslint:disable:no-unused-variable */
import {ActivatedRoute, Router} from '@angular/router';
import {async, ComponentFixture, TestBed, fakeAsync, tick, inject} from '@angular/core/testing';
import {BaseRequestOptions, Http, ResponseOptions, Response} from '@angular/http';
import {EventEmitter} from '@angular/core';
import {MaterialModule} from '@angular/material';
import {MockBackend} from '@angular/http/testing';
import {RouterTestingModule} from '@angular/router/testing';

import {ApplicationComponent} from './application.component';
import {ConfirmationModalService} from '../core/services/confirmation-modal.service';
import {ElementalModule} from '../elemental/elemental.module';
import {ErrorService} from '../core/services/error.service';
import {FlashMessageService} from '../core/services/flash-message.service';
import {PipelinesService} from '../core/services/pipelines.service';
import {SegmentService} from '../core/services/segment.service';
import {SharedModule} from '../shared/shared.module';
import {LiftService} from '../core/services/lift.service';

class MockLiftService {
  captureEvent(eventName: string, eventData: Object) {
    return true;
  }
}

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
        {provide: LiftService, useClass: MockLiftService},
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

  it('should get application info using pipeline service',
    fakeAsync(inject([ActivatedRoute, MockBackend], (route, mockBackend) => {

    setupConnections(mockBackend, {
      body: JSON.stringify({
        repo_url: 'https://github.com/acquia/repo1.git',
        repo_name: 'acquia/repo1',
        repo_type: 'github'
      })
    });

    component.getConfigurationInfo();
    tick();
    fixture.detectChanges();
    expect(component.gitUrl).toEqual('https://github.com/acquia/repo1.git');
    expect(component.gitClone).toEqual('git clone --branch [branch] https://github.com/acquia/repo1.git [destination]');
  })));

  it('should show an error message when the service is throwing error',
    fakeAsync(inject([ActivatedRoute, FlashMessageService, MockBackend], (route, flashMessage, mockBackend) => {

      spyOn(flashMessage, 'showError');

      spyOn(component, 'getInfo').and.callFake(function() {
        return Promise.reject({status: 500, _body: 'some error.'});
      });

      component.getConfigurationInfo();
      tick();
      expect(flashMessage.showError).toHaveBeenCalledWith('500 : some error.');
    })));
});
