/* tslint:disable:no-unused-variable */
import {async, ComponentFixture, TestBed, fakeAsync, tick, inject} from '@angular/core/testing';
import {By} from '@angular/platform-browser';
import {DebugElement, EventEmitter} from '@angular/core';
import {HttpModule, BaseRequestOptions, Http, ResponseOptions, Response} from '@angular/http';
import {MockBackend} from '@angular/http/testing';
import {ActivatedRoute} from '@angular/router';
import {RouterTestingModule} from '@angular/router/testing';

import {Alert} from '../core/models/alert';
import {AuthOauthComponent} from './auth-oauth.component';
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
import {TooltipService} from '../core/services/tooltip.service';
import {Repository} from '../core/models/repository';

class MockActivatedRoute {
  params = new EventEmitter<any>();
}

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
  attachOauthGitRepository(repo_name: string, appId: string, type: string) {
    return Promise.reject({status: 403, _body: 'some error.'});
  }
}

function setupConnections(mockBackend: MockBackend, options: any) {
  mockBackend.connections.subscribe((connection) => {
    connection.mockRespond(new Response(new ResponseOptions(options)));
  });
}

describe('AuthOauthComponent', () => {
  let component: AuthOauthComponent;
  let fixture: ComponentFixture<AuthOauthComponent>;
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
      declarations: [AuthOauthComponent],
      providers: [
        {provide: PipelinesService, useClass: MockPipelinesService},
        ErrorService,
        MockBackend,
        BaseRequestOptions,
        SegmentService,
        TooltipService,
        {provide: FlashMessageService, useClass: MockFlashMessage},
        {provide: HelpCenterService, useClass: MockHelpCenterService},
        {provide: ConfirmationModalService, useClass: MockConfirmationModalService},
        {provide: LiftService, useClass: MockLiftService},
        {provide: ActivatedRoute, useClass: MockActivatedRoute},
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
    fixture = TestBed.createComponent(AuthOauthComponent);
    component = fixture.componentInstance;
    injector = fixture.debugElement.injector;
    component.repoType = 'github';
    fixture.detectChanges();
  });

  it('should create',
    fakeAsync(inject([ActivatedRoute], (route) => {
    expect(component).toBeTruthy();
  })));

  it('should show error when faild to attach repository',
    fakeAsync(inject([ActivatedRoute], (route) => {

      spyOn(component, 'showAttachRepoAlert');

      const repo = new Repository({});
      component.attachRepository(repo);
      tick();
      expect(component.showAttachRepoAlert).toHaveBeenCalled();
  })));

  it('should show success when connected to github',
    fakeAsync(inject([ActivatedRoute], (route) => {

    const params = {
      success: 'true'
    };

    spyOn(component, 'showConnectionAlert');
    component.typeLabel = 'GitHub';

    component.checkAuthorization(params);
    expect(component.showConnectionAlert).toHaveBeenCalledWith('success', 'You are successfully connected to GitHub.');
  })));

  it('should show returned error when connection to github fails',
    fakeAsync(inject([ActivatedRoute], (route) => {

    const params = {
      success: 'false',
      reason: 'some reason'
    };

    spyOn(component, 'showConnectionAlert');

    component.checkAuthorization(params);
    expect(component.showConnectionAlert).toHaveBeenCalledWith('danger', 'some reason');
  })));

  it('should show specific error when connection to Github fails and no reason given',
    fakeAsync(inject([ActivatedRoute], (route) => {

    const params = {
      success: 'false'
    };

    spyOn(component, 'showConnectionAlert');
    component.typeLabel = 'GitHub';

    component.checkAuthorization(params);
    expect(component.showConnectionAlert).toHaveBeenCalledWith('danger', 'Sorry, we could not connect to GitHub at this time.');
  })));

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
