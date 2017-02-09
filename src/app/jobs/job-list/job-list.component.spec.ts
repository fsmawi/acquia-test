/* tslint:disable:no-unused-variable */
import {async, ComponentFixture, TestBed} from '@angular/core/testing';
import {By} from '@angular/platform-browser';
import {DebugElement} from '@angular/core';

import {JobListComponent} from './job-list.component';
import {SharedModule} from '../../shared/shared.module';
import {MaterialModule} from '@angular/material';
import {RouterModule} from '@angular/router';
import {MomentModule} from 'angular2-moment';
import {PipelinesService} from '../../core/services/pipelines.service';
import {ErrorService} from '../../core/services/error.service';

describe('JobListComponent', () => {
  let component: JobListComponent;
  let fixture: ComponentFixture<JobListComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [JobListComponent],
      imports: [MaterialModule.forRoot(), SharedModule, RouterModule, MomentModule],
      providers: [PipelinesService, ErrorService]
    })
      .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(JobListComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
