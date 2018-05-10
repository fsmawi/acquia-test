import {async, ComponentFixture, TestBed} from '@angular/core/testing';
import {BaseRequestOptions, Http, ResponseOptions, Response} from '@angular/http';
import {By} from '@angular/platform-browser';
import {Component} from '@angular/core';
import {DebugElement} from '@angular/core';
import {MockBackend} from '@angular/http/testing';
import {RouterTestingModule} from '@angular/router/testing';

import {Application} from '../models/application';
import {BaseApplication} from './base-application';
import {ErrorService} from '../services/error.service';
import {PipelinesService} from '../services/pipelines.service';
import {FlashMessageService} from '../services/flash-message.service';
import {ConfirmationModalService} from '../services/confirmation-modal.service';

class MockPipelinesService {
  getApplicationInfo() {
    return Promise.resolve(
      new Application({
              repo_url: 'https://github.com/acquia/repo1.git',
              repo_name: 'acquia/repo1',
              repo_type: 'github'
            }));
  }

  getN3TokenInfo() {
    return Promise.resolve(
      {
        token_attached: false,
        is_token_valid: false,
        can_execute_pipelines: true
      }
    );
  }

  setN3Credentials() {
    return Promise.resolve(
      {
        success: true
      }
    );
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

  showSuccess(message: string, e: any) {
    return true;
  }
}

@Component({
  selector: 'e-mock-component',
  template: '<p></p>',
})
export class MockComponent extends BaseApplication {
  constructor(
    protected flashMessage: FlashMessageService,
    protected errorHandler: ErrorService,
    protected pipeline: PipelinesService,
    protected confirmationModalService: ConfirmationModalService) {
    super(flashMessage, errorHandler, pipeline, confirmationModalService);
  }
}

describe('MockComponent', () => {
  let component: MockComponent;
  let fixture: ComponentFixture<MockComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [
        MockComponent
      ],
      providers: [
        ErrorService,
        {provide: FlashMessageService, useClass: MockFlashMessage},
        {provide: ConfirmationModalService, useClass: MockConfirmationModalService},
        {provide: PipelinesService, useClass: MockPipelinesService},
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
      imports: [
        RouterTestingModule
      ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(MockComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should get application info', () => {
    component.getInfo(true)
      .then((info) => {
        expect(info.repo_name).toEqual('acquia/repo1');
      });
  });

  it('should get static application info', () => {
    expect(component.staticInfo).toBeTruthy();
    expect(component.staticInfo.repo_name).toEqual('acquia/repo1');
  });

  it('should get static N3CredentialsAttached', () => {
    BaseApplication.n3CredentialsAttached = true;
    expect(component.staticN3CredentialsAttached).toBeTruthy();
    expect(component.staticN3CredentialsAttached).toEqual(true);
  });

  it('should get application info from static object', () => {
    BaseApplication.info = new Application({
      repo_name: 'acquia/repo2',
      url: '',
      type: ''
    });
    BaseApplication.n3PopupShown = false;

    component.getInfo()
      .then((info) => {
        expect(info.repo_name).toEqual('acquia/repo2');
        expect(BaseApplication.n3PopupShown).toEqual(true);
      });
  });

  it('should show confirmation dialog and set the n3 credentials', () => {
    BaseApplication.n3CredentialsAttached = false;
    component.showN3CredentialsPopup()
      .then((res) => {
        expect(BaseApplication.n3CredentialsAttached).toEqual(true);
      });
  });

});
