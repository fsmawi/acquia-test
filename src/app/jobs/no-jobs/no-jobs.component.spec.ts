/* tslint:disable:no-unused-variable */
import {async, ComponentFixture, TestBed, inject} from '@angular/core/testing';
import {By} from '@angular/platform-browser';
import {DebugElement} from '@angular/core';
import {MdDialog} from '@angular/material';
import {RouterTestingModule} from '@angular/router/testing';

import {NoJobsComponent} from './no-jobs.component';
import {SegmentService} from '../../core/services/segment.service';
import {ElementalModule} from '../../elemental/elemental.module';
import {SharedModule} from '../../shared/shared.module';
import {LiftService} from '../../core/services/lift.service';

class MockLiftService {
  captureEvent(eventName: string, eventData: Object) {
    return true;
  }
}

class MockMdDialog {
  open(component: any) {
    return true;
  }
}

describe('NoJobsComponent', () => {
  let component: NoJobsComponent;
  let fixture: ComponentFixture<NoJobsComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      imports: [
        ElementalModule,
        RouterTestingModule,
        SharedModule
      ],
      declarations: [ NoJobsComponent ],
      providers: [
        SegmentService,
        { provide: LiftService, useClass: MockLiftService },
        { provide: MdDialog, useClass: MockMdDialog }
      ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(NoJobsComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should open modal',  inject([MdDialog], (dialog) => {
    spyOn(dialog, 'open');
    component.startJob();
    expect(dialog.open).toHaveBeenCalled();
  }));
});
