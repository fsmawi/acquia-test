/* tslint:disable:no-unused-variable */
import {async, ComponentFixture, TestBed} from '@angular/core/testing';
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


class MockPipelinesService {

  getBranches(appId: string) {
    return Promise.resolve(['branch1', 'branch2', 'branch3']);
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
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
