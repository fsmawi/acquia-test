import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
import { MockApiComponent } from './mock-api.component';

const routes: Routes = [
  {path: '', component: MockApiComponent}
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
  providers: []
})
export class MockApiRoutingModule { }
