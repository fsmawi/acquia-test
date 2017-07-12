import {Component, OnInit, Input} from '@angular/core';
import {MdDialogRef} from '@angular/material';

import {ErrorService} from '../../core/services/error.service';
import {PipelinesService} from '../../core/services/pipelines.service';
import {repoType} from '../../core/repository-types';

@Component({
  selector: 'app-oauth-dialog-repositories',
  templateUrl: './oauth-dialog-repositories.component.html',
  styleUrls: ['./oauth-dialog-repositories.component.scss']
})
export class OauthDialogRepositoriesComponent implements OnInit {

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
   * App Id to send with oauth requests.
   */
  @Input()
  appId: string;

  /**
   * Repository type.
   */
  @Input()
  repoType: string;

  /**
   * Repository type Label
   * @type {string}
   */
  typeLabel: string;

  /**
   * Builds the component
   * @param dialogRef
   * @param pipelinesService
   * @param errorHandler
   */
  constructor(public dialogRef: MdDialogRef<OauthDialogRepositoriesComponent>,
              public pipelinesService: PipelinesService,
              private errorHandler: ErrorService) {
  }

  /**
   * Get All repositories recursively
   * @param {string} type
   * @param page = 1
   */
  getAllRepositories(page = 1) {
    this.pipelinesService.getRepositoriesByPage(page, this.appId, this.repoType)
      .then(result => {

        result = result.map(item => {
          item.name = item.full_name;
          return item;
        });

        this.repositories = this.repositories.concat(result);
        if (result.length < 100) {
          this.loading = false;
        } else {
          this.getAllRepositories(++page);
        }
      })
      .catch(e => {
        this.errorHandler.apiError(e);
        this.dialogRef.close(e);
        this.errorHandler.reportError(e, `FailedToGet${this.typeLabel}Repos`,
          {component: 'oauth-dialog-repositories', appId: this.appId}, 'error');
      });
  }

  /**
   * On component initialize, Get all repositories from oauth
   */
  ngOnInit() {
    this.loading = true;
    if (this.appId && this.repoType) {
      this.typeLabel = repoType[this.repoType].name;
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
