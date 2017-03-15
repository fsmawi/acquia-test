import {Component, OnInit, Input} from '@angular/core';
import {MdDialogRef} from '@angular/material';
import {PipelinesService} from '../../core/services/pipelines.service';
import {RepositoryFilterPipe} from './repository-filter.pipe';
import {ErrorService} from '../../core/services/error.service';
import {FlashMessageService} from '../../core/services/flash-message.service';


@Component({
  selector: 'app-github-dialog-repositories',
  templateUrl: './github-dialog-repositories.component.html',
  styleUrls: ['./github-dialog-repositories.component.scss']
})
export class GithubDialogRepositoriesComponent implements OnInit {

  /**
   * Array of repositories
   * @type {Array}
   */
  repositories = [];

  /**
   * Selected repository
   * @type {Object}
   */
  repository = {};

  /**
   * Filter repositories
   * @type {string}
   */
  repoInfo: string;

  /**
   * Loading repositories
   * @type {Boolean}
   */
  loading = false;

  /**
   * App Id to send with github requests.
   */
  @Input()
  appId: string;

  /**
   * Builds the component
   * @param dialogRef
   * @param pipelinesService
   * @param flashMessageService
   * @param errorHandler
   */
  constructor(public dialogRef: MdDialogRef<GithubDialogRepositoriesComponent>,
              public pipelinesService: PipelinesService,
              public flashMessageService: FlashMessageService,
              private errorHandler: ErrorService) {
  }

  /**
   * Get All repositories recursively
   * @param page = 1
   */
  getAllRepositories(page = 1) {
    this.pipelinesService.getRepositoriesByPage(page, this.appId)
      .then(result => {
        this.repositories = this.repositories.concat(result);
        if (result.length < 100) {
          this.loading = false;
        } else {
          this.getAllRepositories(++page);
        }
      })
      .catch(e => {
        this.errorHandler.apiError(e);
        this.dialogRef.close();
        this.flashMessageService.showError(e.status + ' : ' + e._body);
      });
  }

  /**
   * On component initialize, Get all repositories from github
   */
  ngOnInit() {
    this.loading = true;
    if (this.appId) {
      this.start();
    }
  }

  /**
   * Gets all the repositories. Can be delayed if an appId is not injected on creation.
   */
  start() {
    this.getAllRepositories();
  }

  /**
   * Select an option
   * @param option
   */
  toggleOption(option) {
    this.repository = option;
  }
}
