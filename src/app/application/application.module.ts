import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ApplicationRoutingModule } from './application-routing.module';
import { ApplicationComponent } from './application.component';
import { MaterialModule } from '@angular/material';
import { ElementalModule } from '../elemental/elemental.module';



@NgModule({
  imports: [
    CommonModule,
    ApplicationRoutingModule,
    MaterialModule.forRoot(),
    ElementalModule
  ],
  declarations: [ApplicationComponent]
})
export class ApplicationModule { }
