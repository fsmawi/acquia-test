import {NgModule} from '@angular/core';
import {Routes, RouterModule} from '@angular/router';
import {AuthGuard} from './core/services/auth-guard.service';

const routes: Routes = [
  {path: '', redirectTo: '/auth/tokens', pathMatch: 'full'},
  {path: 'jobs/:app', loadChildren: 'app/jobs/jobs.module#JobsModule', canActivate: [AuthGuard]},
  {path: 'applications/:app/info', loadChildren: 'app/application/application.module#ApplicationModule', canActivate: [AuthGuard]},
  {path: 'applications/:app', loadChildren: 'app/jobs/jobs.module#JobsModule', canActivate: [AuthGuard]},
  {path: 'auth/tokens', loadChildren: 'app/auth-tokens/auth-tokens.module#AuthTokensModule'},
  {path: 'auth/github', loadChildren: 'app/auth-github/auth-github.module#AuthGithubModule', canActivate: [AuthGuard]},
  {path: 'auth/acquia', loadChildren: 'app/auth-acquia/auth-acquia.module#AuthAcquiaModule', canActivate: [AuthGuard]},
  {path: 'error', loadChildren: 'app/status-code/status-code.module#StatusCodeModule'},
  {path: '404', loadChildren: 'app/status-code/status-code.module#StatusCodeModule'},
  {path: 'mock/header', loadChildren: 'app/mock-api/mock-api.module#MockApiModule'},
  {path: 'landing', loadChildren: 'app/landing-page/landing-page.module#LandingPageModule'},
  {path: '**', redirectTo: '404'}
];

@NgModule({
  imports: [RouterModule.forRoot(routes)],
  exports: [RouterModule],
  providers: []
})
export class AppRoutingModule {
}
