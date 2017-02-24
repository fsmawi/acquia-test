import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
import { AuthGithubComponent } from './auth-github.component';

const routes: Routes = [
  {path: ':app-id', component: AuthGithubComponent},
  {path: '', redirectTo: '/404', pathMatch: 'full'}
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
  providers: []
})
export class AuthGithubRoutingModule { }
