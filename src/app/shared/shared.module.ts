import {CommonModule} from '@angular/common';
import {MaterialModule} from '@angular/material';
import {NgModule} from '@angular/core';

import {ElementalModule} from '../elemental/elemental.module';
import {IframeLinkDirective} from './directives/iframe-link.directive';
import {JobStatusComponent} from './job-status/job-status.component';
import {SafePipe} from './pipes/safe.pipe';
import {SegmentDirective} from './directives/segment.directive';

@NgModule({
  imports: [
    CommonModule,
    MaterialModule.forRoot(),
    ElementalModule
  ],
  declarations: [JobStatusComponent, SafePipe, SegmentDirective, IframeLinkDirective],
  exports: [JobStatusComponent, SafePipe, SegmentDirective, IframeLinkDirective]
})
export class SharedModule {
}
