/* tslint:disable:no-unused-variable */
import { async, ComponentFixture, TestBed } from '@angular/core/testing';
import { By } from '@angular/platform-browser';
import { DebugElement } from '@angular/core';

import { AuthGithubComponent } from './auth-github.component';
import { MaterialModule } from '@angular/material';
import { GithubService } from '../core/services/github.service';
import { PipelinesService } from '../core/services/pipelines.service';
import { ErrorService } from '../core/services/error.service';
import {RouterTestingModule} from '@angular/router/testing';
import { ElementalModule } from '../elemental/elemental.module';


describe('AuthGithubComponent', () => {
  let component: AuthGithubComponent;
  let fixture: ComponentFixture<AuthGithubComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ AuthGithubComponent ],
      providers: [GithubService, PipelinesService, ErrorService],
      imports: [MaterialModule.forRoot(), RouterTestingModule, ElementalModule]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(AuthGithubComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
