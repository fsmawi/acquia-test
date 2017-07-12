import {NgModule} from '@angular/core';
import {Routes, RouterModule} from '@angular/router';

import {AuthOauthComponent} from './auth-oauth.component';

const routes: Routes = [
  {path: ':repo-type/:app-id', component: AuthOauthComponent},
  {path: ':repo-type', redirectTo: '/applications', pathMatch: 'full'},
  {path: '', redirectTo: '/404', pathMatch: 'full'}
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class AuthOauthRoutingModule { }
