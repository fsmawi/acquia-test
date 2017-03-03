/* tslint:disable:no-unused-variable */
import {async, ComponentFixture, TestBed, fakeAsync, tick, inject} from '@angular/core/testing';
import {By} from '@angular/platform-browser';
import {DebugElement} from '@angular/core';
import {RouterTestingModule} from '@angular/router/testing';
import {MaterialModule} from '@angular/material';
import {MockBackend} from '@angular/http/testing';
import {HttpModule, BaseRequestOptions, Http, ResponseOptions, Response} from '@angular/http';
import {Router} from '@angular/router';

import {AuthGithubComponent} from './auth-github.component';
import {PipelinesService} from '../core/services/pipelines.service';
import {ErrorService} from '../core/services/error.service';
import {FlashMessageService} from '../core/services/flash-message.service';
import {SegmentService} from '../core/services/segment.service';
import {ElementalModule} from '../elemental/elemental.module';

class MockFlashMessage {
  showError(message: string, e: any) {
    return true;
  }

  showSuccess(message: string, e: any) {
    return true;
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
    TestBed.configureTestingModule({
      declarations: [AuthGithubComponent],
      providers: [
        PipelinesService,
        ErrorService,
        FlashMessageService,
        MockBackend,
        BaseRequestOptions,
        SegmentService,
        {provide: FlashMessageService, useClass: MockFlashMessage},
        {
          provide: Http,
          useFactory: (mockBackend, options) => {
            return new Http(mockBackend, options);
          },
          deps: [MockBackend, BaseRequestOptions]
        }
      ],
      imports: [MaterialModule.forRoot(), RouterTestingModule, ElementalModule]
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
    fakeAsync(inject([FlashMessageService, MockBackend], (flashMessage, mockBackend) => {

      setupConnections(mockBackend, {
        body: JSON.stringify({})
      });

      spyOn(flashMessage, 'showError');

      component.attachRepository({});
      tick();
      expect(flashMessage.showError).toHaveBeenCalled();
    })));

  it('should show success when connected to github', inject([FlashMessageService], (flashMessage) => {

    const params = {
      success: 'true'
    };

    spyOn(flashMessage, 'showSuccess');

    component.checkAuthorization(params);
    expect(flashMessage.showSuccess).toHaveBeenCalledWith('You are successfully connected to Github.');
  }));

  it('should show returned error when connection to github fails', inject([FlashMessageService], (flashMessage) => {

    const params = {
      success: 'false',
      reason: 'some reason'
    };

    spyOn(flashMessage, 'showError');

    component.checkAuthorization(params);
    expect(flashMessage.showError).toHaveBeenCalledWith('some reason');

  }));

  it('should show specific error when connection to github fails and no reason given', inject([FlashMessageService], (flashMessage) => {

    const params = {
      success: 'false'
    };

    spyOn(flashMessage, 'showError');

    component.checkAuthorization(params);
    expect(flashMessage.showError).toHaveBeenCalledWith('Sorry, we could not connect to github at this time.');
  }));
});
