/* tslint:disable:no-unused-variable */
import {ActivatedRoute, Router} from '@angular/router';
import {async, ComponentFixture, TestBed, fakeAsync, tick, inject} from '@angular/core/testing';
import {BaseRequestOptions, Http, ResponseOptions, Response} from '@angular/http';
import {By} from '@angular/platform-browser';
import {DebugElement} from '@angular/core';
import {EventEmitter} from '@angular/core';
import {MaterialModule} from '@angular/material';
import {MdDialog} from '@angular/material';
import {MockBackend} from '@angular/http/testing';
import {RouterTestingModule} from '@angular/router/testing';

import {Alert} from '../core/models/alert';
import {AuthAcquiaComponent} from './auth-acquia.component';
import {ElementalModule} from '../elemental/elemental.module';
import {features} from '../core/features';
import {ErrorService} from '../core/services/error.service';
import {SegmentService} from '../core/services/segment.service';
import {SharedModule} from '../shared/shared.module';
import {PipelinesService} from '../core/services/pipelines.service';

class MockActivatedRoute {
  params = new EventEmitter<any>();
}

function setupConnections(mockBackend: MockBackend, options: any) {
  mockBackend.connections.subscribe((connection) => {
    connection.mockRespond(new Response(new ResponseOptions(options)));
  });
}

describe('AuthAcquiaComponent', () => {
  let component: AuthAcquiaComponent;
  let fixture: ComponentFixture<AuthAcquiaComponent>;

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
        AuthAcquiaComponent
      ],
      providers: [
        MockBackend,
        BaseRequestOptions,
        ErrorService,
        SegmentService,
        PipelinesService,
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
        MaterialModule.forRoot(),
        RouterTestingModule,
        ElementalModule,
        SharedModule
      ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(AuthAcquiaComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should get git info using pipeline service',
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
    expect(component.isConnected).toEqual(false);
    expect(component.repository).toEqual('acquia/repo1');
  })));

  it('should show an error alert when the service is throwing error',
    fakeAsync(inject([ActivatedRoute], (route) => {

      spyOn(component, 'showConnectionAlert');

      spyOn(component, 'getInfo').and.callFake(function() {
        return Promise.reject({status: 500, _body: 'some error.'});
      });

      component.getConfigurationInfo();
      tick();
      expect(component.showConnectionAlert).toHaveBeenCalledWith('danger', '500 : some error.');
    })));

  it('should open start job modal',  inject([MdDialog], (dialog) => {
    spyOn(dialog, 'open');
    component.startJob();
    expect(dialog.open).toHaveBeenCalled();
  }));

  it('should enable Acquia Git',
    fakeAsync(inject([ActivatedRoute, MockBackend], (route, mockBackend) => {

     setupConnections(mockBackend, {
      body: JSON.stringify({
        success: true
      })
    });

    spyOn(component, 'refresh');

    component.enableAcquiaGit();
    tick();
    expect(component.refresh).toHaveBeenCalled();
  })));
});
