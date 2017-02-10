import {NgModule} from '@angular/core';
import {Routes, RouterModule} from '@angular/router';
import {StatusCodeComponent} from './status-code.component';

const routes: Routes = [
    {path: '', component: StatusCodeComponent}
];

@NgModule({
    imports: [RouterModule.forChild(routes)],
    exports: [RouterModule],
    providers: []
})
export class StatusCodeRoutingModule {
}
