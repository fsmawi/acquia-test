import {NgModule} from '@angular/core';
import {CommonModule} from '@angular/common';
import {LandingPageRoutingModule} from './landing-page-routing.module';
import {LandingPageComponent} from './landing-page.component';
import {ElementalModule} from '../elemental/elemental.module';
import {MaterialModule} from '@angular/material';

@NgModule({
  imports: [
    CommonModule,
    LandingPageRoutingModule,
    ElementalModule,
    MaterialModule.forRoot()
  ],
  declarations: [LandingPageComponent]
})
export class LandingPageModule {
}
