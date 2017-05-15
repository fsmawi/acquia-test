import {NgModule} from '@angular/core';
import {CommonModule} from '@angular/common';

import {PipelinesNotEnabledRoutingModule} from './pipelines-not-enabled-routing.module';
import {PipelinesNotEnabledComponent} from './pipelines-not-enabled.component';
import {ElementalModule} from '../elemental/elemental.module';
import {SharedModule} from '../shared/shared.module';

@NgModule({
  imports: [
    CommonModule,
    SharedModule,
    PipelinesNotEnabledRoutingModule,
    ElementalModule
  ],
  declarations: [PipelinesNotEnabledComponent]
})
export class PipelinesNotEnabledModule {
}
