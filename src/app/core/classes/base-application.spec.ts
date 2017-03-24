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

class MockPipelinesService {
  getApplicationInfo() {
    return Promise.resolve(
      new Application({
              repo_url: 'https://github.com/acquia/repo1.git',
              repo_name: 'acquia/repo1',
              repo_type: 'github'
            }));
  }
}

@Component({
  selector: 'e-mock-component',
  template: '<p></p>',
})
export class MockComponent extends BaseApplication {
  constructor(
    protected errorHandler: ErrorService,
    protected pipeline: PipelinesService) {
    super(errorHandler, pipeline);
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

  it('should get application info from static object', () => {
    BaseApplication.info = new Application({
      repo_name: 'acquia/repo2',
      url: '',
      type: ''
    });

    component.getInfo()
      .then((info) => {
        expect(info.repo_name).toEqual('acquia/repo2');
      });
  });
});
