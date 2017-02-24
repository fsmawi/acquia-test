/* tslint:disable:no-unused-variable */
import { async, ComponentFixture, TestBed } from '@angular/core/testing';
import { By } from '@angular/platform-browser';
import { DebugElement, NgModule } from '@angular/core';
import { MdDialogModule, MdDialog, OverlayContainer, MaterialModule } from '@angular/material';
import { HttpModule, BaseRequestOptions, Http, ResponseOptions, Response, RequestMethod } from '@angular/http';
import { MockBackend } from '@angular/http/testing';
import { RouterTestingModule } from '@angular/router/testing';
import { ElementalModule } from '../../elemental/elemental.module';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { GithubDialogRepositoriesComponent } from './github-dialog-repositories.component';
import { PipelinesService } from '../../core/services/pipelines.service';
import { RepositoryFilterPipe } from './repository-filter.pipe';
import { ErrorService } from '../../core/services/error.service';
import { FlashMessageService } from '../../core/services/flash-message.service';

@NgModule({
  declarations: [GithubDialogRepositoriesComponent, RepositoryFilterPipe],
  exports: [GithubDialogRepositoriesComponent],
  entryComponents: [GithubDialogRepositoriesComponent],
  imports: [MdDialogModule.forRoot(), ElementalModule, CommonModule, FormsModule, MaterialModule],
})
class DialogTestModule { }

describe('GithubDialogRepositoriesComponent', () => {
  let component: GithubDialogRepositoriesComponent;
  let dialog: MdDialog;
  let overlayContainerElement: HTMLElement;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      imports: [DialogTestModule, RouterTestingModule],
      providers: [
        PipelinesService,
        FlashMessageService,
        ErrorService,
        MockBackend,
        BaseRequestOptions,
        {
          provide: Http,
          useFactory: (mockBackend, options) => {
            return new Http(mockBackend, options);
          },
          deps: [MockBackend, BaseRequestOptions]
        },
        RepositoryFilterPipe,
        {
          provide: OverlayContainer, useFactory: () => {
            overlayContainerElement = document.createElement('div');
            return { getContainerElement: () => overlayContainerElement };
          }
        }
      ],
    })
    .compileComponents();
  }));

  beforeEach(() => {
    dialog = TestBed.get(MdDialog);
    const dialogRef = dialog.open(GithubDialogRepositoriesComponent);

    component = dialogRef.componentInstance;
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should set a repository object', () => {
    const option = {
      full_name: 'repo1',
      url: 'http://test.com'
    };

    component.toggleOption(option);
    expect(component.repository).toEqual(option);
  });
});
