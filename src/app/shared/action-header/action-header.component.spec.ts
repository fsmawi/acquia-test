/* tslint:disable:no-unused-variable */
import {async, ComponentFixture, TestBed, inject, fakeAsync, tick} from '@angular/core/testing';
import {By} from '@angular/platform-browser';
import {DebugElement} from '@angular/core';
import {MdDialog, MdProgressSpinnerModule, MdDialogModule, MdTooltipModule} from '@angular/material';
import {FormsModule} from '@angular/forms';
import {RouterTestingModule} from '@angular/router/testing';
import {ObservableMedia} from '@angular/flex-layout';

import {MomentModule} from 'angular2-moment';

import {ActionHeaderComponent} from './action-header.component';
import {PipelinesService} from '../../core/services/pipelines.service';
import {ErrorService} from '../../core/services/error.service';
import {Job} from '../../core/models/job';
import {JobLog} from '../../core/models/job-log';
import {SegmentService} from '../../core/services/segment.service';
import {FlashMessageService} from '../../core/services/flash-message.service';
import {ConfirmationModalService} from '../../core/services/confirmation-modal.service';
import {LiftService} from '../../core/services/lift.service';
import {ElementalModule} from '../../elemental/elemental.module';
import {StartJobComponent} from '../../jobs/start-job/start-job.component';
import {IframeLinkDirective} from '../directives/iframe-link.directive';
import {LiftDirective} from '../directives/lift.directive';
import {SegmentDirective} from '../directives/segment.directive';
import {TrackDirective} from '../directives/track.directive';
import {HelpCenterService} from '../../core/services/help-center.service';

class MockOberservableMedia {
 isActive(screenSize: string) {
      return true;
   }
}

class MockHelpCenterService {
  show() {
    return true;
  }
}

class MockLiftService {
  captureEvent(eventName: string, eventData: Object) {
    return true;
  }
}

class MockConfirmationModalService {
  openDialog(title: string, message: string, primaryActionText: string, secondaryActionText = '') {
    return Promise.resolve(true);
  }
}

class MockPipelinesService {

  successfulJob = new Job({
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

  failedJob = new Job({
    job_id: 'job-id',
    sitename: 'sitename',
    pipeline_id: 'pipeline-id',
    branch: 'master',
    commit: 'commit',
    status: 'failed_by_system',
    requested_by: 'user@acquia.com',
    requested_at: 1462297477,
    started_at: 1462297477,
    finished_at: 1462297477,
    duration: 90000,
    output: '',
    exit_message: ''
  });

  currentJob = new Job({
    job_id: 'job-id',
    sitename: 'sitename',
    pipeline_id: 'pipeline-id',
    branch: 'master',
    commit: 'commit',
    status: 'running',
    requested_by: 'user@acquia.com',
    requested_at: 1462297477,
    started_at: 1462297477,
    finished_at: 1462297477,
    duration: 90000,
    output: '',
    exit_message: '',
    metadata: {
      log_stream_secret: '123',
      log_stream_websocket: '12345'
    }
  });

  getJobByJobId(appId: string, jobId: string, params = {}) {
    switch (jobId) {
      case 'current':
        // change to successful job after
        const originalJob = JSON.parse(JSON.stringify(this.currentJob));
        this.currentJob = this.successfulJob;
        return Promise.resolve(originalJob);
      case 'failed':
        return Promise.resolve(this.failedJob);
      default:
        return Promise.resolve(this.successfulJob);
    }
  }

  getLogFile(appId: string, jobId: string, params = {}) {
    switch (jobId) {
      case 'failed':
        return Promise.resolve([new JobLog({
          timeline: 1462297477,
          level: 'INFO',
          message: 'Executing step test failure\nfailure!\nExiting step test failure'
        })]);
      default:
        return Promise.resolve([new JobLog({
          timeline: 1462297477,
          level: 'INFO',
          message: 'Executing step test success\nsuccess!\nExiting step test success'
        })]);
    }
  }

  stopJob(job: Job) {
    return Promise.resolve([]);
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

describe('ActionHeaderComponent', () => {
  let component: ActionHeaderComponent;
  let fixture: ComponentFixture<ActionHeaderComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [
        ActionHeaderComponent,
        StartJobComponent,
        IframeLinkDirective,
        LiftDirective,
        SegmentDirective,
        TrackDirective
      ],
      providers: [
        {provide: PipelinesService, useClass: MockPipelinesService},
        {provide: ConfirmationModalService, useClass: MockConfirmationModalService},
        {provide: LiftService, useClass: MockLiftService},
        {provide: FlashMessageService, useClass: MockFlashMessage},
        {provide: HelpCenterService, useClass: MockHelpCenterService},
        {provide: ObservableMedia, useClass: MockOberservableMedia},
        SegmentService,
        ErrorService
      ],
      imports: [
        MdProgressSpinnerModule,
        MdDialogModule,
        MdTooltipModule,
        MomentModule,
        RouterTestingModule,
        ElementalModule,
        FormsModule
      ],
    })
      .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(ActionHeaderComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should stop job', fakeAsync(inject([], () => {
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
  })));

  it('should open modal', inject([MdDialog], (dialog) => {
    spyOn(dialog, 'open');
    component.startJob();
    expect(dialog.open).toHaveBeenCalled();
  }));
});
