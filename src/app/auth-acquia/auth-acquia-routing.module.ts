import {NgModule} from '@angular/core';
import {Routes, RouterModule} from '@angular/router';

import {AuthAcquiaComponent } from './auth-acquia.component';

const routes: Routes = [
  {path: ':app-id', component: AuthAcquiaComponent},
  {path: '', redirectTo: '/404', pathMatch: 'full'}
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
  providers: []
})
export class AuthAcquiaRoutingModule { }
