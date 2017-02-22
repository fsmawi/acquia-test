import {NgModule} from '@angular/core';
import {CommonModule} from '@angular/common';
import {JobStatusComponent} from './job-status/job-status.component';
import {MaterialModule} from '@angular/material';
import {ElementalModule} from '../elemental/elemental.module';
import {SafePipe} from './pipes/safe.pipe';

@NgModule({
  imports: [
    CommonModule,
    MaterialModule.forRoot(),
    ElementalModule
  ],
  declarations: [JobStatusComponent, SafePipe],
  exports: [JobStatusComponent, SafePipe]
})
export class SharedModule {
}
