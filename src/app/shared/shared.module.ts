import {CommonModule} from '@angular/common';
import {MaterialModule} from '@angular/material';
import {NgModule} from '@angular/core';
import {FlexLayoutModule} from '@angular/flex-layout';

import {ElementalModule} from '../elemental/elemental.module';
import {IframeLinkDirective} from './directives/iframe-link.directive';
import {JobStatusComponent} from './job-status/job-status.component';
import {SafePipe} from './pipes/safe.pipe';
import {SegmentDirective} from './directives/segment.directive';
import {TrackDirective} from './directives/track.directive';
import {LogChunksPipe} from './pipes/log-chunks.pipe';
import {LiftDirective} from './directives/lift.directive';
import {ActionHeaderComponent} from './action-header/action-header.component';
import {TopLevelNavigationComponent} from './top-level-navigation/top-level-navigation.component';
import {ContextLinkDirective} from './directives/context-link.directive';

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
    TrackDirective,
    LogChunksPipe,
    IframeLinkDirective,
    LiftDirective,
    ActionHeaderComponent,
    TopLevelNavigationComponent,
    ContextLinkDirective
  ],
  exports: [
    JobStatusComponent,
    SafePipe,
    SegmentDirective,
    TrackDirective,
    LogChunksPipe,
    IframeLinkDirective,
    ContextLinkDirective,
    LiftDirective,
    ActionHeaderComponent,
    TopLevelNavigationComponent
  ]
})
export class SharedModule {
}
