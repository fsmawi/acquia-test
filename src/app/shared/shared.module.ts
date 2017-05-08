import {CommonModule} from '@angular/common';
import {MdProgressSpinnerModule, MdDialogModule, MdTooltipModule} from '@angular/material';
import {NgModule} from '@angular/core';
import {FlexLayoutModule} from '@angular/flex-layout';
import {FormsModule} from '@angular/forms';

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
import { EncryptCredentialsComponent } from './encrypt-credentials/encrypt-credentials.component';
import { DragAndDropDirective } from './directives/drag-and-drop.directive';

@NgModule({
  imports: [
    CommonModule,
    MdProgressSpinnerModule,
    MdTooltipModule,
    MdDialogModule,
    ElementalModule,
    FlexLayoutModule,
    FormsModule
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
    ContextLinkDirective,
    EncryptCredentialsComponent,
    DragAndDropDirective
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
    TopLevelNavigationComponent,
    EncryptCredentialsComponent,
    DragAndDropDirective,
    MdProgressSpinnerModule,
    MdTooltipModule,
    MdDialogModule
  ],
  entryComponents: [EncryptCredentialsComponent]
})
export class SharedModule {
}
