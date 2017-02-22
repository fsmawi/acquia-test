/* tslint:disable:no-unused-variable */
import {async, ComponentFixture, TestBed, inject, fakeAsync, tick} from '@angular/core/testing';
import {By} from '@angular/platform-browser';
import {DebugElement} from '@angular/core';

import {JobsDetailComponent} from './jobs-detail.component';
import {MaterialModule} from '@angular/material';
import {MomentModule} from 'angular2-moment';
import {PipelinesService} from '../../core/services/pipelines.service';
import {ErrorService} from '../../core/services/error.service';
import {RouterTestingModule} from '@angular/router/testing';
import {ElementalModule} from '../../elemental/elemental.module';
import {Job} from '../../core/models/job';
import {JobLog} from '../../core/models/job-log';
import {JobSummaryComponent} from '../job-summary/job-summary.component';
import {SharedModule} from '../../shared/shared.module';
import {AnsiService} from '../../core/services/ansi.service';

class MockPipelinesService {

  job = new Job({
    job_id: 'job-id',
    sitename: 'sitename',
    pipeline_id: 'pipeline-id',
    branch: 'master',
    commit: 'commit',
    status: 'succeeded',
    requested_by: 'user@acquia.com',
    requested_at: 1462297477,
    started_at: 1462297477,
    finished_at: 1462297477,
    duration: 90000,
    output: '',
    exit_message: ''
  });

  log = new JobLog({
    timeline: 1462297477,
    level: 'INFO',
    message: 'Job has started successfully.'
  });

  getJobByJobId(appId: string, jobId: string, params = {}) {
    return Promise.resolve(this.job);
  }
  getLogFile(appId: string, jobId: string, params = {}) {
    return Promise.resolve([this.log]);
  }
}

describe('JobsDetailComponent', () => {
  let component: JobsDetailComponent;
  let fixture: ComponentFixture<JobsDetailComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [JobsDetailComponent, JobSummaryComponent],
      imports: [MaterialModule.forRoot(), MomentModule, RouterTestingModule, ElementalModule, SharedModule],
      providers: [
        { provide: PipelinesService, useClass: MockPipelinesService },
        AnsiService,
        ErrorService]
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

  it('should show job details.', fakeAsync(inject([], () => {
    component.refresh();
    tick();
    fixture.detectChanges();
    const compiled = fixture.debugElement.nativeElement;
    expect(compiled.querySelector('#jobDetails h3').innerText).toContain('Jobs');
  })));
});
