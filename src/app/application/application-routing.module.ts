import {NgModule} from '@angular/core';
import {Routes, RouterModule} from '@angular/router';

import {ApplicationComponent} from './application.component';
import {ConfigureComponent} from './configure/configure.component';

const routes: Routes = [
  {path: ':app-id', component: ApplicationComponent},
  {path: ':app-id/configure', component: ConfigureComponent}
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
  providers: []
})
export class ApplicationRoutingModule { }
