import {NgModule} from '@angular/core';
import {Routes, RouterModule} from '@angular/router';
import {AuthTokensComponent} from './auth-tokens.component';

const routes: Routes = [
  {path: '', component: AuthTokensComponent}
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
  providers: []
})
export class AuthTokensRoutingModule {
}
