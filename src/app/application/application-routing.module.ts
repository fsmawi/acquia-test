import {NgModule} from '@angular/core';
import {Routes, RouterModule} from '@angular/router';

import {ApplicationComponent} from './application.component';
import {ConfigureComponent} from './configure/configure.component';

const routes: Routes = [
  {path: '', component: ApplicationComponent},
  {path: 'configure', component: ConfigureComponent}
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
  providers: []
})
export class ApplicationRoutingModule { }
