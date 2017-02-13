/* tslint:disable:no-unused-variable */
import { async, ComponentFixture, TestBed, inject } from '@angular/core/testing';
import { By } from '@angular/platform-browser';
import { DebugElement } from '@angular/core';
import { AuthGithubComponent } from './auth-github.component';
import { MaterialModule } from '@angular/material';
import { GithubService } from '../core/services/github.service';
import { PipelinesService } from '../core/services/pipelines.service';
import { ErrorService } from '../core/services/error.service';
import { RouterTestingModule } from '@angular/router/testing';
import { ElementalModule } from '../elemental/elemental.module';
import { FlashMessageService } from '../core/services/flash-message.service';
import { MockBackend } from '@angular/http/testing';
import { BaseRequestOptions, Http } from '@angular/http';
import { Router } from '@angular/router';

describe('AuthGithubComponent', () => {
  let component: AuthGithubComponent;
  let fixture: ComponentFixture<AuthGithubComponent>;
  let injector: any;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ AuthGithubComponent ],
      providers: [
        GithubService,
        PipelinesService,
        ErrorService,
        FlashMessageService,
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
});
