/* tslint:disable:no-unused-variable */
import {async, ComponentFixture, TestBed, inject, fakeAsync, tick} from '@angular/core/testing';
import {By} from '@angular/platform-browser';
import {DebugElement} from '@angular/core';
import {MaterialModule} from '@angular/material';
import {RouterTestingModule} from '@angular/router/testing';

import {MomentModule} from 'angular2-moment';

import {PipelinesService} from '../../core/services/pipelines.service';
import {ErrorService} from '../../core/services/error.service';
import {Job} from '../../core/models/job';
import {JobLog} from '../../core/models/job-log';
import {AnsiService} from '../../core/services/ansi.service';
import {SegmentService} from '../../core/services/segment.service';
import {ElementalModule} from '../../elemental/elemental.module';
import {JobsDetailComponent} from './jobs-detail.component';
import {JobSummaryComponent} from '../job-summary/job-summary.component';
import {SharedModule} from '../../shared/shared.module';
import {FlashMessageService} from '../../core/services/flash-message.service';
import {WebSocketService} from '../../core/services/web-socket.service';

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
          message: 'failure!'
        })]);
      default:
        return Promise.resolve([new JobLog({
          timeline: 1462297477,
          level: 'INFO',
          message: 'success!'
        })]);
    }
  }
}

class MockSegmentService {
  page() {
  }
}

class MockWebSocketService {
  socket = {
    subscribe: (fn) => {
      fn({name: 'connected'});
      fn({name: 'list-available', argument: {items: [{type: 'log'}]}});
      fn({name: 'line', argument: {unix_time: 0, text: 'check me!'}});
      fn({name: 'close'});
    },
    send: () => {
    }
  };

  connect() {
    return this.socket;
  }
}

class MockWebSocketServiceFailure {
  socket = {
    subscribe: (fn) => {
      fn({name: 'error', argument: 'Too bad, so sad'});
    },
    send: () => {
    }
  };

  connect() {
    return this.socket;
  }
}

describe('JobsDetailComponent', () => {
  let component: JobsDetailComponent;
  let fixture: ComponentFixture<JobsDetailComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [
        JobsDetailComponent,
        JobSummaryComponent
      ],
      imports: [
        MaterialModule.forRoot(),
        MomentModule,
        RouterTestingModule,
        ElementalModule,
        SharedModule
      ],
      providers: [
        {provide: PipelinesService, useClass: MockPipelinesService},
        {provide: SegmentService, useClass: MockSegmentService},
        {provide: WebSocketService, useClass: MockWebSocketService},
        AnsiService,
        ErrorService,
        FlashMessageService
      ]
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

  it('should show job details for a successful job', fakeAsync(inject([], () => {
    component.refresh();
    tick();
    fixture.detectChanges();
    const compiled = fixture.debugElement.nativeElement;
    expect(compiled.querySelector('.logs').innerHTML).toContain('success!');
  })));

  it('should show job details for a failed job', fakeAsync(inject([], () => {
    component.jobId = 'failed';
    component.refresh();
    tick();
    fixture.detectChanges();
    const compiled = fixture.debugElement.nativeElement;
    expect(compiled.querySelector('.logs').innerHTML).toContain('failure!');
  })));

  it('should stream logs for an in progress job', fakeAsync(inject([], () => {
    component.jobId = 'current';
    component.refresh();
    tick();
    fixture.detectChanges();
    const compiled = fixture.debugElement.nativeElement;
    expect(compiled.querySelector('.logs').innerHTML).toContain('check me!');
  })));

  it('should fail to stream logs for an in progress job', fakeAsync(inject([WebSocketService], (ws: MockWebSocketService) => {
    component.jobId = 'current';
    ws.socket = new MockWebSocketServiceFailure().socket;
    component.refresh();
    tick();
    fixture.detectChanges();
    expect(component.streaming).toBe(false);
  })));
});
