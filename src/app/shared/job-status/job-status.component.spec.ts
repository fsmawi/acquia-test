/* tslint:disable:no-unused-variable */
import {async, ComponentFixture, TestBed} from '@angular/core/testing';
import {By} from '@angular/platform-browser';
import {DebugElement} from '@angular/core';

import {JobStatusComponent} from './job-status.component';
import {MdProgressSpinnerModule, MdDialogModule, MdTooltipModule} from '@angular/material';
import {ElementalModule} from '../../elemental/elemental.module';

describe('JobStatusComponent', () => {
  let component: JobStatusComponent;
  let fixture: ComponentFixture<JobStatusComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [JobStatusComponent],
      imports: [MdProgressSpinnerModule, MdDialogModule, MdTooltipModule, ElementalModule]
    })
      .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(JobStatusComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
