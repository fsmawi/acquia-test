/* tslint:disable:no-unused-variable */
import {async, ComponentFixture, TestBed, inject, fakeAsync, tick, discardPeriodicTasks} from '@angular/core/testing';
import {By} from '@angular/platform-browser';
import {DebugElement} from '@angular/core';

import {LandingPageComponent} from './landing-page.component';
import {MaterialModule} from '@angular/material';
import {ElementalModule} from '../elemental/elemental.module';
import {LocalStorageService} from '../core/services/local-storage.service';
import {PipelinesService} from '../core/services/pipelines.service';
import {ErrorService} from '../core/services/error.service';
import {FlashMessageService} from '../core/services/flash-message.service';
import {Router, ActivatedRoute} from '@angular/router';

class MockRouter {
  navigateByUrl(route) {
    return route;
  }
}

class MockActivatedRoute {
  snapshot = {
    params: {
      'app-id': '1234'
    }
  };
}

class MockPipelinesService {
  getPipelineByAppId(id: string) {
    switch (id) {
      case 'not-enabled':
        return Promise.reject({status: 403});
      case 'not-connected':
        return Promise.resolve([{repo_data: {repos: []}}]);
      default:
        return Promise.resolve([{repo_data: {repos: [{type: 'github'}]}}]);
    }
  }
}

describe('LandingPageComponent', () => {
  let component: LandingPageComponent;
  let fixture: ComponentFixture<LandingPageComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [LandingPageComponent],
      imports: [ElementalModule, MaterialModule],
      providers: [LocalStorageService, ErrorService, FlashMessageService, {
        provide: Router, useClass: MockRouter
      }, {
        provide: ActivatedRoute, useClass: MockActivatedRoute
      }, {
        provide: PipelinesService, useClass: MockPipelinesService
      }]
    }).compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(LandingPageComponent);
    component = fixture.componentInstance;
    component.appId = '1234';
    fixture.detectChanges();
  });

  beforeAll(() => {
    window.onbeforeunload = () => {
    };
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  // Removed: Karma + Angular + A redirect outside don't play nice together: https://github.com/angular/angular/issues/10127
  // it('should redirect based on a non enabled customer to upsell route', fakeAsync(() => {
  //   component.appId = 'not-enabled';
  //   component.go();
  //   tick();
  //   fixture.detectChanges();
  //   expect(component.isEnabled).toEqual(false);
  //   expect(component.isConnected).toEqual(undefined);
  //   discardPeriodicTasks();
  // }));

  it('should redirect based on an enabled but non connected customer to the github route', fakeAsync(() => {
    component.appId = 'not-connected';
    component.go();
    tick();
    fixture.detectChanges();
    expect(component.isEnabled).toBeTruthy();
    expect(component.isConnected).toEqual(undefined);
    discardPeriodicTasks();
  }));

  it('should redirect based on an enabled and connected customer to the jobs route', fakeAsync(() => {
    component.go();
    tick();
    fixture.detectChanges();
    expect(component.isEnabled).toBeTruthy();
    expect(component.isConnected).toBeTruthy();
    discardPeriodicTasks();
  }));
});
