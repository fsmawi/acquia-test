/* tslint:disable:no-unused-variable */
import {async, ComponentFixture, TestBed} from '@angular/core/testing';
import {By} from '@angular/platform-browser';
import {CommonModule} from '@angular/common';
import {DebugElement, NgModule} from '@angular/core';
import {MdDialogModule, MdDialog} from '@angular/material';
import {ActivatedRoute} from '@angular/router';

import {ElementalModule} from '../../elemental/elemental.module';
import {StartJobComponent} from './start-job.component';
import {SegmentService} from '../../core/services/segment.service';
import {SharedModule} from '../../shared/shared.module';


class MockActivatedRoute {
  snapshot = {
    params: {
      'app-id': '1234'
    }
  };
}

@NgModule({
  declarations: [StartJobComponent],
  exports: [StartJobComponent],
  entryComponents: [StartJobComponent],
  imports: [MdDialogModule.forRoot(), ElementalModule, CommonModule, SharedModule],
})
class DialogTestModule { }

describe('StartJobComponent', () => {
  let component: StartJobComponent;
  let dialog: MdDialog;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      imports: [DialogTestModule],
      providers: [
        SegmentService,
        { provide: ActivatedRoute, useClass: MockActivatedRoute }
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
