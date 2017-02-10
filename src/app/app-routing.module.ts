import {NgModule} from '@angular/core';
import {Routes, RouterModule} from '@angular/router';
import {AuthGuard} from './core/services/auth-guard.service';

const routes: Routes = [
  // TODO: Temporary, remove when actual auth is in place, or landing page with ability go to auth is in place
  {path: '', redirectTo: '/auth/tokens', pathMatch: 'full'},
  // End
  {path: 'jobs', loadChildren: 'app/jobs/jobs.module#JobsModule', canActivate: [AuthGuard]},
  {path: 'auth/tokens', loadChildren: 'app/auth-tokens/auth-tokens.module#AuthTokensModule'},
  {path: 'auth/github', loadChildren: 'app/auth-github/auth-github.module#AuthGithubModule'},
  {path: 'error', loadChildren: 'app/status-code/status-code.module#StatusCodeModule'},
  {path: '404', loadChildren: 'app/status-code/status-code.module#StatusCodeModule'},
  {path: '**', redirectTo: '404'}
];

@NgModule({
  imports: [RouterModule.forRoot(routes)],
  exports: [RouterModule],
  providers: []
})
export class AppRoutingModule {
}
