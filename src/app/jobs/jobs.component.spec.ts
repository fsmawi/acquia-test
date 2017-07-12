/* tslint:disable:no-unused-variable */
import {async, ComponentFixture, TestBed, inject, fakeAsync, tick, discardPeriodicTasks} from '@angular/core/testing';
import {BrowserAnimationsModule} from '@angular/platform-browser/animations';
import {DebugElement} from '@angular/core';
import {MdDialog} from '@angular/material';
import {FormsModule} from '@angular/forms';
import {RouterTestingModule} from '@angular/router/testing';

import {MomentModule} from 'angular2-moment';

import {CoreModule} from '../core/core.module';
import {ElementalModule} from '../elemental/elemental.module';
import {ErrorService} from '../core/services/error.service';
import {Job} from '../core/models/job';
import {JobsComponent} from './jobs.component';
import {JobListComponent} from './job-list/job-list.component';
import {JobSummaryComponent} from './job-summary/job-summary.component';
import {N3Service} from '../core/services/n3.service';
import {PipelinesService} from '../core/services/pipelines.service';
import {SharedModule} from '../shared/shared.module';
import {NoJobsComponent} from './no-jobs/no-jobs.component';
import {LiftService} from '../core/services/lift.service';
import {BaseApplication} from '../core/classes/base-application';


class MockLiftService {
  captureEvent(eventName: string, eventData: Object) {
    return true;
  }
}

class MockN3Service {
  getEnvironments(appId: string) {
    return Promise.resolve([{vcs: {type: 'git'}}]);
  }
}

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

  getJobsByAppId(appId: string, params = {}) {
    let jobs = [];
    if (appId === 'app-with-out-jobs') {
      jobs = [];
    } else if (appId === 'app-with-jobs') {
      jobs = [this.job];
    } else {
      return Promise.reject({});
    }
    return Promise.resolve(jobs);
  }
}

class MockMdDialog {
  open(component: any) {
    return true;
  }
}

describe('JobsComponent', () => {
  let component: JobsComponent;
  let fixture: ComponentFixture<JobsComponent>;

  beforeEach(async(() => {
    global['analyticsMock'] = {};
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
        JobsComponent,
        JobListComponent,
        JobSummaryComponent,
        NoJobsComponent
      ],
      providers: [
        {provide: PipelinesService, useClass: MockPipelinesService},
        {provide: MdDialog, useClass: MockMdDialog},
        {provide: N3Service, useClass: MockN3Service},
        {provide: LiftService, useClass: MockLiftService},
        ErrorService
      ],
      imports: [
        RouterTestingModule,
        CoreModule,
        MomentModule,
        ElementalModule,
        SharedModule,
        ElementalModule,
        BrowserAnimationsModule,
        FormsModule
      ]
    })
      .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(JobsComponent);
    component = fixture.componentInstance;
    BaseApplication._appId = 'default';
    fixture.detectChanges();
  });

  it('should create JobsComponent.', () => {
    expect(component).toBeTruthy();
  });

  it('should show no jobs message.', fakeAsync(inject([], () => {
    BaseApplication._appId = 'app-with-out-jobs';
    component.appId = 'app-with-out-jobs';
    component.getJobs();
    tick();
    fixture.detectChanges();
    const compiled = fixture.debugElement.nativeElement;
    expect(compiled.querySelector('#no-jobs h2').innerText).toEqual('Get started with Pipelines');
  })));

  it('should show no jobs component.', fakeAsync(inject([], () => {
    BaseApplication._appId = 'app-with-out-jobs';
    component.appId = 'app-with-out-jobs';
    component.getJobs();
    tick();
    fixture.detectChanges();
    const compiled = fixture.debugElement.nativeElement;
    expect(compiled.querySelector('app-no-jobs')).toBeTruthy();
  })));

  it('should not show the filter input when jobs are not available', fakeAsync(inject([], () => {
    BaseApplication._appId = 'app-with-out-jobs';
    component.getJobs();
    tick();
    fixture.detectChanges();
    const compiled = fixture.debugElement.nativeElement;
    expect(component.filteredJobs.length).toEqual(0);

    fixture.detectChanges();
    expect(compiled.querySelector('#filtered-text')).toBeFalsy();
  })));

  it('should filter the jobs shown.', () => {
    expect(component).toBeTruthy();

    const job = new Job({
      job_id: 'job-id',
      sitename: 'sitename',
      pipeline_id: 'pipeline-id',
      branch: 'master',
      commit: 'commit',
      status: 'succeeded',
      trigger: 'push',
      requested_by: 'user@acquia.com',
      requested_at: 1462297477,
      started_at: 1462297477,
      finished_at: 1462297477,
      duration: 90000,
      output: '',
      exit_message: ''
    });

    component.jobs = [job];

    component.filterText = '';
    component.filter();
    expect(component.filteredJobs.length).toEqual(1);

    component.filterText = 'master';
    component.filter();
    expect(component.filteredJobs.length).toEqual(1);

    component.filterText = 'push';
    component.filter();
    expect(component.filteredJobs.length).toEqual(1);

    component.filterText = 'pass';
    component.filter();
    expect(component.filteredJobs.length).toEqual(1);

    component.filterText = 'random text';
    component.filter();
    expect(component.filteredJobs.length).toEqual(0);
  });

  it('should clear the filter.', () => {
    expect(component).toBeTruthy();

    const job = new Job({
      job_id: 'job-id',
      sitename: 'sitename',
      pipeline_id: 'pipeline-id',
      branch: 'master',
      commit: 'commit',
      status: 'succeeded',
      trigger: 'push',
      requested_by: 'user@acquia.com',
      requested_at: 1462297477,
      started_at: 1462297477,
      finished_at: 1462297477,
      duration: 90000,
      output: '',
      exit_message: ''
    });

    component.jobs = [job];

    component.filterText = '';
    component.filter();
    expect(component.filteredJobs.length).toEqual(1);

    component.filterText = 'random text';
    component.filter();
    expect(component.filteredJobs.length).toEqual(0);

    component.clearFilter();
    expect(component.filteredJobs.length).toEqual(1);
  });

  it('should return the trigger type for the filter text.', () => {
    expect(component).toBeTruthy();

    expect(component.getTriggerTypeFromFilterText('push')).toEqual('push');
    expect(component.getTriggerTypeFromFilterText('branch')).toEqual('push');
    expect(component.getTriggerTypeFromFilterText('pr')).toEqual('pull_request');
    expect(component.getTriggerTypeFromFilterText('pull')).toEqual('pull_request');
    expect(component.getTriggerTypeFromFilterText('pull request')).toEqual('pull_request');
    expect(component.getTriggerTypeFromFilterText('manual')).toEqual('manual');
    expect(component.getTriggerTypeFromFilterText('random text')).toEqual('');
  });

  it('should return the status for the filter text.', () => {
    expect(component).toBeTruthy();

    expect(component.getStatusFromFilterText('pass')).toEqual('success');
    expect(component.getStatusFromFilterText('passed')).toEqual('success');
    expect(component.getStatusFromFilterText('success')).toEqual('success');
    expect(component.getStatusFromFilterText('succeeded')).toEqual('success');
    expect(component.getStatusFromFilterText('fail')).toEqual('failure');
    expect(component.getStatusFromFilterText('failed')).toEqual('failure');
    expect(component.getStatusFromFilterText('error')).toEqual('failure');
    expect(component.getStatusFromFilterText('errored')).toEqual('failure');
    expect(component.getStatusFromFilterText('random text')).toEqual('');
  });

  it('should show more jobs.', fakeAsync(inject([], () => {
    BaseApplication._appId = 'app-with-jobs';
    component.appId = 'app-with-jobs';
    component.showMoreJobs();
    tick();
    expect(component.page).toBeTruthy(2);
  })));

});
