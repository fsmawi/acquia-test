import {NgModule} from '@angular/core';
import {CommonModule} from '@angular/common';
import {JobsRoutingModule} from './jobs-routing.module';
import {JobsComponent} from './jobs.component';
import {JobsDetailComponent} from './jobs-detail/jobs-detail.component';
import {MaterialModule} from '@angular/material';
import {MomentModule} from 'angular2-moment';
import {JobListComponent} from './job-list/job-list.component';
import {SharedModule} from '../shared/shared.module';
import {FlexLayoutModule} from '@angular/flex-layout';
import {ElementalModule} from '../elemental/elemental.module';
import {JobSummaryComponent} from './job-summary/job-summary.component';

@NgModule({
  imports: [
    CommonModule,
    JobsRoutingModule,
    MaterialModule.forRoot(),
    FlexLayoutModule,
    MomentModule,
    SharedModule,
    ElementalModule
  ],
  declarations: [JobsComponent, JobsDetailComponent, JobListComponent, JobSummaryComponent]
})
export class JobsModule {
}
