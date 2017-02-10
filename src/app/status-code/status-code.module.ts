import {NgModule} from '@angular/core';
import {CommonModule} from '@angular/common';
import {StatusCodeRoutingModule} from './status-code-routing.module';
import {StatusCodeComponent} from './status-code.component';
import {MaterialModule} from '@angular/material';
import {FlexLayoutModule} from '@angular/flex-layout';


@NgModule({
  imports: [
    CommonModule,
    StatusCodeRoutingModule,
    MaterialModule.forRoot(),
    FlexLayoutModule
  ],
  declarations: [StatusCodeComponent]
})
export class StatusCodeModule {
}
