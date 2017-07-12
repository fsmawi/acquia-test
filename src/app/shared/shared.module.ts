import {CommonModule} from '@angular/common';
import {MdProgressSpinnerModule, MdDialogModule} from '@angular/material';
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
import {EncryptCredentialsComponent} from './encrypt-credentials/encrypt-credentials.component';
import {DragAndDropDirective} from './directives/drag-and-drop.directive';
import {TooltipDirective} from './directives/tooltip.directive';
import {RepoTypeIconComponent} from './repo-type-icon/repo-type-icon.component';

@NgModule({
  imports: [
    CommonModule,
    MdProgressSpinnerModule,
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
    DragAndDropDirective,
    TooltipDirective,
    RepoTypeIconComponent
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
    TooltipDirective,
    MdProgressSpinnerModule,
    MdDialogModule,
    RepoTypeIconComponent
  ],
  entryComponents: [EncryptCredentialsComponent]
})
export class SharedModule {
}
