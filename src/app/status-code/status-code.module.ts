import {NgModule} from '@angular/core';
import {CommonModule} from '@angular/common';
import {StatusCodeRoutingModule} from './status-code-routing.module';
import {StatusCodeComponent} from './status-code.component';
import {MdProgressSpinnerModule, MdTooltipModule} from '@angular/material';
import {FlexLayoutModule} from '@angular/flex-layout';


@NgModule({
  imports: [
    CommonModule,
    StatusCodeRoutingModule,
    MdProgressSpinnerModule,
    MdTooltipModule,
    FlexLayoutModule
  ],
  declarations: [StatusCodeComponent]
})
export class StatusCodeModule {
}
