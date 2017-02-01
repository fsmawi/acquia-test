import {NgModule} from '@angular/core';
import {CommonModule} from '@angular/common';
import {AuthTokensRoutingModule} from './auth-tokens-routing.module';
import {AuthTokensComponent} from './auth-tokens.component';
import {MaterialModule} from '@angular/material';
import {FormsModule} from '@angular/forms';

@NgModule({
  imports: [
    CommonModule,
    AuthTokensRoutingModule,
    MaterialModule.forRoot(),
    FormsModule
  ],
  declarations: [AuthTokensComponent]
})
export class AuthTokensModule {
}
