import {CommonModule} from '@angular/common';
import {FlexLayoutModule} from '@angular/flex-layout';
import {FormsModule} from '@angular/forms';
import {NgModule} from '@angular/core';

import {ApplicationComponent} from './application.component';
import {ApplicationRoutingModule} from './application-routing.module';
import {ElementalModule} from '../elemental/elemental.module';
import {SharedModule} from '../shared/shared.module';
import {ConfigureComponent} from './configure/configure.component';
import {JobsModule} from '../jobs/jobs.module';

@NgModule({
  imports: [
    CommonModule,
    ApplicationRoutingModule,
    ElementalModule,
    FlexLayoutModule,
    FormsModule,
    JobsModule,
    SharedModule
  ],
  declarations: [
    ApplicationComponent,
    ConfigureComponent
  ]
})
export class ApplicationModule {
}
