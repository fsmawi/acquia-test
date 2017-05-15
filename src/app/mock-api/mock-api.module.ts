import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { MockApiRoutingModule } from './mock-api-routing.module';
import { MockApiComponent } from './mock-api.component';
import {MdProgressSpinnerModule, MdTooltipModule} from '@angular/material';
import {FormsModule} from '@angular/forms';
import {ElementalModule} from '../elemental/elemental.module';

@NgModule({
  imports: [
    CommonModule,
    MockApiRoutingModule,
    MdProgressSpinnerModule,
    MdTooltipModule,
    FormsModule,
    ElementalModule
  ],
  declarations: [MockApiComponent]
})
export class MockApiModule { }
