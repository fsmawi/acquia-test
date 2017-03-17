/* tslint:disable:no-unused-variable */
import {async, ComponentFixture, TestBed, inject, fakeAsync, tick, discardPeriodicTasks} from '@angular/core/testing';
import {By} from '@angular/platform-browser';
import {DebugElement} from '@angular/core';
import {MdDialog} from '@angular/material';

import {JobsComponent} from './jobs.component';
import {MaterialModule} from '@angular/material';
import {RouterTestingModule} from '@angular/router/testing';
import {PipelinesService} from '../core/services/pipelines.service';
import {ErrorService} from '../core/services/error.service';
import {SharedModule} from '../shared/shared.module';
import {ElementalModule} from '../elemental/elemental.module';
import {MomentModule} from 'angular2-moment';
import {JobListComponent} from './job-list/job-list.component';
import {JobSummaryComponent} from './job-summary/job-summary.component';
import {Job} from '../core/models/job';
import {CoreModule} from '../core/core.module';
import {N3Service} from '../core/services/n3.service';

class MockN3Service {
  getEnvironments(appId: string) {
    return Promise.resolve([{ vcs : { type : 'git'}}]);
  }
}

class MockPipelinesService {

  job = new Job ({
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
    }else if (appId === 'app-with-jobs') {
      jobs = [this.job];
    }else {
      return Promise.reject({});
    }
    return Promise.resolve(jobs);
  }

  getGithubStatus(appId: string) {
    return Promise.resolve([{'undefined': {
      repo_url: 'https://github.com/acquia/repo1.git',
      connected: true
    }}]);
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
    TestBed.configureTestingModule({
      declarations: [JobsComponent, JobListComponent, JobSummaryComponent],
      providers: [
        { provide: PipelinesService, useClass: MockPipelinesService },
        { provide: MdDialog, useClass: MockMdDialog },
        {provide: N3Service, useClass: MockN3Service},
        ErrorService],
      imports: [MaterialModule.forRoot(), RouterTestingModule, CoreModule,
        MomentModule, SharedModule, ElementalModule]
    })
      .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(JobsComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create JobsComponent.', () => {
    expect(component).toBeTruthy();
  });

  it('should show no jobs message.', fakeAsync(inject([], () => {
    component.appId = 'app-with-out-jobs';
    component.refresh();
    tick();
    fixture.detectChanges();
    const compiled = fixture.debugElement.nativeElement;
    expect(compiled.querySelector('#no-jobs h3').innerText).toEqual('You have no jobs for this application');
  })));

  /*it('should show no jobs message.', fakeAsync(inject([], () => {
    tick(150000);
    tick();
    fixture.detectChanges();
    tick(150000);
    fixture.detectChanges();
    const compiled = fixture.debugElement.nativeElement;
    expect(compiled.querySelector('h4 span').innerText).toEqual('Activity');
  })));*/

  it('should open modal',  inject([MdDialog], (dialog) => {
    spyOn(dialog, 'open');
    component.startJob();
    expect(dialog.open).toHaveBeenCalled();
  }));
});
