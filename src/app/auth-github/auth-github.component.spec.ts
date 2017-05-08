/* tslint:disable:no-unused-variable */
import {async, ComponentFixture, TestBed, fakeAsync, tick, inject} from '@angular/core/testing';
import {By} from '@angular/platform-browser';
import {DebugElement} from '@angular/core';
import {HttpModule, BaseRequestOptions, Http, ResponseOptions, Response} from '@angular/http';
import {MockBackend} from '@angular/http/testing';
import {Router} from '@angular/router';
import {RouterTestingModule} from '@angular/router/testing';

import {Alert} from '../core/models/alert';
import {AuthGithubComponent} from './auth-github.component';
import {ElementalModule} from '../elemental/elemental.module';
import {ErrorService} from '../core/services/error.service';
import {PipelinesService} from '../core/services/pipelines.service';
import {SegmentService} from '../core/services/segment.service';
import {SharedModule} from '../shared/shared.module';
import {LiftService} from '../core/services/lift.service';
import {FlashMessageService} from '../core/services/flash-message.service';
import {ConfirmationModalService} from '../core/services/confirmation-modal.service';
import {HelpCenterService} from '../core/services/help-center.service';
import {ApplicationModule} from '../application/application.module';

class MockHelpCenterService {
  show() {
    return true;
  }
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

class MockLiftService {
  captureEvent(eventName: string, eventData: Object) {
    return true;
  }
}

class MockPipelinesService {
  attachGithubRepository(repo_name: string, appId: string) {
    return Promise.reject({status: 403, _body: 'some error.'});
  }
}

function setupConnections(mockBackend: MockBackend, options: any) {
  mockBackend.connections.subscribe((connection) => {
    connection.mockRespond(new Response(new ResponseOptions(options)));
  });
}

describe('AuthGithubComponent', () => {
  let component: AuthGithubComponent;
  let fixture: ComponentFixture<AuthGithubComponent>;
  let injector: any;

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
      declarations: [AuthGithubComponent],
      providers: [
        {provide: PipelinesService, useClass: MockPipelinesService},
        ErrorService,
        MockBackend,
        BaseRequestOptions,
        SegmentService,
        {provide: FlashMessageService, useClass: MockFlashMessage},
        {provide: HelpCenterService, useClass: MockHelpCenterService},
        {provide: ConfirmationModalService, useClass: MockConfirmationModalService},
        {provide: LiftService, useClass: MockLiftService},
        {
          provide: Http,
          useFactory: (mockBackend, options) => {
            return new Http(mockBackend, options);
          },
          deps: [MockBackend, BaseRequestOptions]
        }
      ],
      imports: [
        RouterTestingModule,
        ElementalModule,
        SharedModule,
        ApplicationModule
      ]
    })
      .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(AuthGithubComponent);
    component = fixture.componentInstance;
    injector = fixture.debugElement.injector;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should show error when faild to attach repository',
    fakeAsync(inject([], () => {

      spyOn(component, 'showAttachRepoAlert');

      component.attachRepository({});
      tick();
      expect(component.showAttachRepoAlert).toHaveBeenCalled();
  })));

  it('should show success when connected to github', () => {

    const params = {
      success: 'true'
    };

    spyOn(component, 'showConnectionAlert');

    component.checkAuthorization(params);
    expect(component.showConnectionAlert).toHaveBeenCalledWith('success', 'You are successfully connected to Github.');
  });

  it('should show returned error when connection to github fails', () => {

    const params = {
      success: 'false',
      reason: 'some reason'
    };

    spyOn(component, 'showConnectionAlert');

    component.checkAuthorization(params);
    expect(component.showConnectionAlert).toHaveBeenCalledWith('danger', 'some reason');
  });

  it('should show specific error when connection to github fails and no reason given', () => {

    const params = {
      success: 'false'
    };

    spyOn(component, 'showConnectionAlert');

    component.checkAuthorization(params);
    expect(component.showConnectionAlert).toHaveBeenCalledWith('danger', 'Sorry, we could not connect to github at this time.');
  });

  it('should connection alerts', () => {
    component.showConnectionAlert('success', 'a message');
    expect(component.connectionAlert.display).toEqual(true);
    expect(component.connectionAlert.type).toEqual('success');
    expect(component.connectionAlert.message).toEqual('a message');
  });

  it('should attach repository alerts', () => {
    component.showAttachRepoAlert('danger', 'a message');
    expect(component.attachRepoAlert.display).toEqual(true);
    expect(component.attachRepoAlert.type).toEqual('danger');
    expect(component.attachRepoAlert.message).toEqual('a message');
  });
});
