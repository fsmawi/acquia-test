import {CommonModule} from '@angular/common';
import {FormsModule} from '@angular/forms';
import {MaterialModule} from '@angular/material';
import {NgModule} from '@angular/core';

import {AuthGithubComponent} from './auth-github.component';
import {AuthGithubRoutingModule} from './auth-github-routing.module';
import {ElementalModule} from '../elemental/elemental.module';
import {GithubDialogRepositoriesComponent} from './github-dialog-repositories/github-dialog-repositories.component';
import {RepositoryFilterPipe} from './github-dialog-repositories/repository-filter.pipe';
import {SharedModule} from '../shared/shared.module';

@NgModule({
  imports: [
    CommonModule,
    AuthGithubRoutingModule,
    MaterialModule.forRoot(),
    FormsModule,
    ElementalModule,
    SharedModule
  ],
  declarations: [AuthGithubComponent, GithubDialogRepositoriesComponent, RepositoryFilterPipe],
  entryComponents: [GithubDialogRepositoriesComponent]
})
export class AuthGithubModule { }
