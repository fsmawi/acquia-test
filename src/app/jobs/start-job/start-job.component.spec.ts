/* tslint:disable:no-unused-variable */
import {async, ComponentFixture, TestBed, inject, tick, fakeAsync, discardPeriodicTasks} from '@angular/core/testing';
import {By} from '@angular/platform-browser';
import {CommonModule} from '@angular/common';
import {DebugElement, NgModule} from '@angular/core';
import {MdDialogModule, MdDialog} from '@angular/material';
import {FormsModule} from '@angular/forms';
import {RouterTestingModule} from '@angular/router/testing';

import {ElementalModule} from '../../elemental/elemental.module';
import {StartJobComponent} from './start-job.component';
import {SegmentService} from '../../core/services/segment.service';
import {SharedModule} from '../../shared/shared.module';
import {PipelinesService} from '../../core/services/pipelines.service';
import {ErrorService} from '../../core/services/error.service';
import {FlashMessageService} from '../../core/services/flash-message.service';
import {LiftService} from '../../core/services/lift.service';

class MockLiftService {
  captureEvent(eventName: string, eventData: Object) {
    return true;
  }
}

class MockPipelinesService {

  getBranches(appId: string) {
    return Promise.resolve(['branch1', 'branch2', 'branch3']);
  }

  directStartJob(appId: string, branch: string, options = {}) {
    return Promise.resolve({});
  }
}

class MockFlashMessage {
  showError(message: string, e: any) {
    return true;
  }

  showSuccess(message: string, e: any) {
    return true;
  }

  showInfo(message: string, e: any = {}) {
    return true;
  }
}

@NgModule({
  declarations: [StartJobComponent],
  exports: [StartJobComponent],
  entryComponents: [StartJobComponent],
  imports: [
    MdDialogModule.forRoot(),
    ElementalModule,
    CommonModule,
    SharedModule,
    FormsModule
  ]
})
class DialogTestModule { }

describe('StartJobComponent', () => {
  let component: StartJobComponent;
  let dialog: MdDialog;

  beforeEach(async(() => {
    global['analyticsMock'] = true;
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
      imports: [DialogTestModule, RouterTestingModule],
      providers: [
        SegmentService,
        { provide: LiftService, useClass: MockLiftService },
        { provide: PipelinesService, useClass: MockPipelinesService },
        { provide: FlashMessageService, useClass: MockFlashMessage },
        ErrorService
      ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    dialog = TestBed.get(MdDialog);
    const dialogRef = dialog.open(StartJobComponent);

    component = dialogRef.componentInstance;
    component.branches = ['branch1', 'branch2', 'branch3'];
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should filter the available branches by the branch name typed', () => {
    expect(component).toBeTruthy();

    component.branch = 'branch';
    component.filter();
    expect(component.branchSuggestions.length).toEqual(3);

    component.branch = '1';
    component.filter();
    expect(component.branchSuggestions.length).toEqual(1);
    expect(component.branchSuggestions[0]).toEqual('branch1');

    component.branch = '';
    component.filter();
    expect(component.branchSuggestions.length).toEqual(0);
  });

  it('should hold the branch selected to start and clear the branch suggestions', () => {
    expect(component).toBeTruthy();

    component.select('branch1');
    expect(component.branch).toEqual('branch1');
    expect(component.branchSuggestions.length).toEqual(0);
  });

  it('should start the job and show success message', fakeAsync(inject([FlashMessageService], (flashMessage) => {
    expect(component).toBeTruthy();

    component.branch = 'branch1';
    component.appId = 'appId';

    spyOn(flashMessage, 'showSuccess');

    component.start();
    tick(1000);
    expect(flashMessage.showSuccess).toHaveBeenCalledWith('Your job has started.');
  })));


});
