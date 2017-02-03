import {NgModule} from '@angular/core';
import {Routes, RouterModule} from '@angular/router';
import {AuthGuard} from './core/services/auth-guard.service';

const routes: Routes = [
  {path: 'jobs', loadChildren: 'app/jobs/jobs.module#JobsModule', canActivate: [AuthGuard]},
  {path: 'auth/tokens', loadChildren: 'app/auth-tokens/auth-tokens.module#AuthTokensModule'}
];

@NgModule({
  imports: [RouterModule.forRoot(routes)],
  exports: [RouterModule],
  providers: []
})
export class AppRoutingModule {
}
