/* tslint:disable:no-unused-variable */
import {async, ComponentFixture, TestBed} from '@angular/core/testing';
import {By} from '@angular/platform-browser';
import {DebugElement} from '@angular/core';
import {ElementalModule} from '../../elemental/elemental.module';
import {JobSummaryComponent} from './job-summary.component';
import {MomentModule} from 'angular2-moment';
import {RouterTestingModule} from '@angular/router/testing';
import {Job} from '../../core/models/job';
import {SharedModule} from '../../shared/shared.module';

describe('JobSummaryComponent', () => {
  let component: JobSummaryComponent;
  let fixture: ComponentFixture<JobSummaryComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [JobSummaryComponent],
      imports: [
        MomentModule,
        ElementalModule,
        RouterTestingModule,
        SharedModule
      ]
    })
      .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(JobSummaryComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should calculate the duration of a running job', () => {
    component.job = new Job({requested_at: 1456263980});
    component.calculateDuration();
    expect(component.calculatedDuration).toMatch(/..:../);
  });
});
