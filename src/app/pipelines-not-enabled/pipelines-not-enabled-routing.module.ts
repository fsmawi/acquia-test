import {NgModule} from '@angular/core';
import {Routes, RouterModule} from '@angular/router';

import {PipelinesNotEnabledComponent} from './pipelines-not-enabled.component';

const routes: Routes = [
  {path: '', component: PipelinesNotEnabledComponent}
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
  providers: []
})
export class PipelinesNotEnabledRoutingModule {
}
