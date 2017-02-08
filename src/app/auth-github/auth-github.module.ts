import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { AuthGithubRoutingModule } from './auth-github-routing.module';
import { AuthGithubComponent } from './auth-github.component';
import { MaterialModule } from '@angular/material';
import { GithubDialogRepositoriesComponent } from './github-dialog-repositories/github-dialog-repositories.component';
import { FormsModule } from '@angular/forms';
import { RepositoryFilterPipe} from './github-dialog-repositories/repository-filter.pipe';
import { ElementalModule } from '../elemental/elemental.module';

@NgModule({
  imports: [
    CommonModule,
    AuthGithubRoutingModule,
    MaterialModule.forRoot(),
    FormsModule,
    ElementalModule
  ],
  declarations: [AuthGithubComponent, GithubDialogRepositoriesComponent, RepositoryFilterPipe],
  entryComponents: [GithubDialogRepositoriesComponent]
})
export class AuthGithubModule { }
