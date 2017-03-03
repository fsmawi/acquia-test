import {CommonModule} from '@angular/common';
import {NgModule} from '@angular/core';
import {MaterialModule} from '@angular/material';

import {ApplicationComponent} from './application.component';
import {ApplicationRoutingModule} from './application-routing.module';
import {ElementalModule} from '../elemental/elemental.module';
import {SharedModule} from '../shared/shared.module';


@NgModule({
  imports: [
    CommonModule,
    ApplicationRoutingModule,
    MaterialModule.forRoot(),
    ElementalModule,
    SharedModule
  ],
  declarations: [ApplicationComponent]
})
export class ApplicationModule {
}
