/* tslint:disable:no-unused-variable */
import {async, ComponentFixture, TestBed, fakeAsync, tick} from '@angular/core/testing';
import {By} from '@angular/platform-browser';
import {DebugElement} from '@angular/core';
import {MaterialModule} from '@angular/material';
import {RouterModule} from '@angular/router';
import {RouterTestingModule} from '@angular/router/testing/router_testing_module';

import {MomentModule} from 'angular2-moment';

import {PipelinesService} from '../../core/services/pipelines.service';
import {ErrorService} from '../../core/services/error.service';
import {Job} from '../../core/models/job';
import {FlashMessageService} from '../../core/services/flash-message.service';
import {ConfirmationModalService} from '../../core/services/confirmation-modal.service';
import {SegmentService} from '../../core/services/segment.service';
import {ElementalModule} from '../../elemental/elemental.module';
import {JobListComponent} from './job-list.component';
import {SharedModule} from '../../shared/shared.module';

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

  stopJob(
    appId: string,
    jobId: string,
    buildstepsEndpoint: string = undefined,
    buildstepsUser: string = undefined,
    buildstepsPass: string = undefined) {
    return Promise.resolve([]);
  }

  startJob(appId: string, pipelineId: string, options = {}) {
    // Default Options
    Object.assign(options, {
      applications: [appId]
    });
    return Promise.resolve([]);
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

describe('JobListComponent', () => {
  let component: JobListComponent;
  let fixture: ComponentFixture<JobListComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [
        JobListComponent
      ],
      imports: [
        MaterialModule.forRoot(),
        SharedModule,
        RouterTestingModule,
        MomentModule,
        ElementalModule
      ],
      providers: [
        {provide: PipelinesService, useClass: MockPipelinesService},
        {provide: FlashMessageService, useClass: MockFlashMessage},
        {provide: ConfirmationModalService, useClass: MockConfirmationModalService},
        SegmentService,
        ErrorService]
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

  it('should restart job', fakeAsync(() => {

    const job = new Job({
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
    component.restartJob(job);
    tick();
    fixture.detectChanges();
    expect(component).toBeTruthy();
  }));

  it('should stop job', fakeAsync(() => {
    const job = new Job({
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
    component.stopJob(job);
    tick();
    fixture.detectChanges();
    expect(component).toBeTruthy();
  }));

});
