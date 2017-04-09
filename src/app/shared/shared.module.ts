import {CommonModule} from '@angular/common';
import {MaterialModule} from '@angular/material';
import {NgModule} from '@angular/core';
import {FlexLayoutModule} from '@angular/flex-layout';

import {ElementalModule} from '../elemental/elemental.module';
import {IframeLinkDirective} from './directives/iframe-link.directive';
import {JobStatusComponent} from './job-status/job-status.component';
import {SafePipe} from './pipes/safe.pipe';
import {SegmentDirective} from './directives/segment.directive';
import {LogChunksPipe} from './pipes/log-chunks.pipe';
import {LiftDirective} from './directives/lift.directive';
import {ActionHeaderComponent} from './action-header/action-header.component';

@NgModule({
  imports: [
    CommonModule,
    MaterialModule.forRoot(),
    ElementalModule,
    FlexLayoutModule
  ],
  declarations: [
    JobStatusComponent,
    SafePipe,
    SegmentDirective,
    LogChunksPipe,
    IframeLinkDirective,
    LiftDirective,
    ActionHeaderComponent
  ],
  exports: [
    JobStatusComponent,
    SafePipe,
    SegmentDirective,
    LogChunksPipe,
    IframeLinkDirective,
    LiftDirective,
    ActionHeaderComponent
  ]
})
export class SharedModule {
}
