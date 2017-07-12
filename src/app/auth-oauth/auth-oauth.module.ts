import {NgModule} from '@angular/core';
import {FormsModule} from '@angular/forms';
import {CommonModule} from '@angular/common';

import {AuthOauthRoutingModule} from './auth-oauth-routing.module';
import {AuthOauthComponent} from './auth-oauth.component';
import {ElementalModule} from '../elemental/elemental.module';
import {OauthDialogRepositoriesComponent} from './oauth-dialog-repositories/oauth-dialog-repositories.component';
import {SharedModule} from '../shared/shared.module';

@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    AuthOauthRoutingModule,
    ElementalModule,
    SharedModule
  ],
  declarations: [AuthOauthComponent, OauthDialogRepositoriesComponent],
  entryComponents: [OauthDialogRepositoriesComponent]
})
export class AuthOauthModule { }
