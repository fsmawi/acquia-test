import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { MockApiRoutingModule } from './mock-api-routing.module';
import { MockApiComponent } from './mock-api.component';
import {MaterialModule} from '@angular/material';
import {FormsModule} from '@angular/forms';

@NgModule({
  imports: [
    CommonModule,
    MockApiRoutingModule,
    MaterialModule,
    FormsModule
  ],
  declarations: [MockApiComponent]
})
export class MockApiModule { }
