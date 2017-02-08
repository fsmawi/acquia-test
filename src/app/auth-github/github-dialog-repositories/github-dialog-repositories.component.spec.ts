/* tslint:disable:no-unused-variable */
import { async, ComponentFixture, TestBed } from '@angular/core/testing';
import { By } from '@angular/platform-browser';
import { DebugElement } from '@angular/core';

import { GithubDialogRepositoriesComponent } from './github-dialog-repositories.component';
import { MaterialModule } from '@angular/material';
import { MdDialogRef, MdDialogModule } from '@angular/material';
import { GithubService } from '../../core/services/github.service';
import { RepositoryFilterPipe } from './repository-filter.pipe';
import {ErrorService} from '../../core/services/error.service';

describe('GithubDialogRepositoriesComponent', () => {
  let component: GithubDialogRepositoriesComponent;
  let fixture: ComponentFixture<GithubDialogRepositoriesComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ GithubDialogRepositoriesComponent ],
      providers: [GithubService, ErrorService, MdDialogRef, RepositoryFilterPipe],
      imports: [MaterialModule.forRoot(), MdDialogModule.forRoot()]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(GithubDialogRepositoriesComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  // it('should create', () => {
  //   expect(component).toBeTruthy();
  // });
});
