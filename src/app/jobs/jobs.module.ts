import {CommonModule} from '@angular/common';
import {FlexLayoutModule} from '@angular/flex-layout';
import {NgModule} from '@angular/core';
import {FormsModule} from '@angular/forms';

import {ElementalModule} from '../elemental/elemental.module';
import {JobListComponent} from './job-list/job-list.component';
import {JobsComponent} from './jobs.component';
import {JobsDetailComponent} from './jobs-detail/jobs-detail.component';
import {JobSummaryComponent} from './job-summary/job-summary.component';
import {JobsRoutingModule} from './jobs-routing.module';
import {MomentModule} from 'angular2-moment';
import {SharedModule} from '../shared/shared.module';
import {StartJobComponent} from './start-job/start-job.component';
import {NoJobsComponent} from './no-jobs/no-jobs.component';
import {LogChunkComponent} from './jobs-detail/log-chunk/log-chunk.component';

@NgModule({
  imports: [
    CommonModule,
    JobsRoutingModule,
    FlexLayoutModule,
    MomentModule,
    SharedModule,
    ElementalModule,
    FormsModule
  ],
  declarations: [
    JobsComponent,
    JobsDetailComponent,
    JobListComponent,
    JobSummaryComponent,
    StartJobComponent,
    NoJobsComponent,
    LogChunkComponent
  ],
  entryComponents: [StartJobComponent],
  exports: [StartJobComponent]
})
export class JobsModule {
}
