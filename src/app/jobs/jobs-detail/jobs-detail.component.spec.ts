/* tslint:disable:no-unused-variable */
import {async, ComponentFixture, TestBed} from '@angular/core/testing';
import {By} from '@angular/platform-browser';
import {DebugElement} from '@angular/core';

import {JobsDetailComponent} from './jobs-detail.component';
import {MaterialModule} from '@angular/material';
import {MomentModule} from 'angular2-moment';
import {PipelinesService} from '../../core/services/pipelines.service';
import {ErrorService} from '../../core/services/error.service';
import {RouterTestingModule} from '@angular/router/testing';
import {ElementalModule} from '../../elemental/elemental.module';

describe('JobsDetailComponent', () => {
  let component: JobsDetailComponent;
  let fixture: ComponentFixture<JobsDetailComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [JobsDetailComponent],
      imports: [MaterialModule.forRoot(), MomentModule, RouterTestingModule, ElementalModule],
      providers: [PipelinesService, ErrorService]
    })
      .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(JobsDetailComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
