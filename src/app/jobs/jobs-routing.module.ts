import {NgModule} from '@angular/core';
import {Routes, RouterModule} from '@angular/router';

import {AuthGuard} from '../core/services/auth-guard.service';
import {JobsComponent} from './jobs.component';
import {JobsDetailComponent} from './jobs-detail/jobs-detail.component';

const routes: Routes = [
  {
    path: '', component: JobsComponent
  },
  {
    path: ':app', component: JobsComponent
  },
  {
    path: ':app/:id', component: JobsDetailComponent
  }];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
  providers: []
})
export class JobsRoutingModule {
}
