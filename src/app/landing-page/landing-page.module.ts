import {NgModule} from '@angular/core';
import {CommonModule} from '@angular/common';
import {LandingPageRoutingModule} from './landing-page-routing.module';
import {LandingPageComponent} from './landing-page.component';
import {ElementalModule} from '../elemental/elemental.module';
import {MdProgressSpinnerModule, MdDialogModule, MdTooltipModule} from '@angular/material';

@NgModule({
  imports: [
    CommonModule,
    LandingPageRoutingModule,
    ElementalModule,
    MdProgressSpinnerModule,
    MdDialogModule,
    MdTooltipModule
  ],
  declarations: [LandingPageComponent]
})
export class LandingPageModule {
}
