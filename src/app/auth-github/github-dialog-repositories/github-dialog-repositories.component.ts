import { Component, OnInit } from '@angular/core';
import { MdDialogRef } from '@angular/material';
import { GithubService } from '../../core/services/github.service';
import { RepositoryFilterPipe } from './repository-filter.pipe';
import { ErrorService } from '../../core/services/error.service';

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
   * Github Access token
   * @type {string}
   */
  accessToken: string;

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
   * Builds the component
   * @param dialogRef
   * @param auth
   * @param errorHandler
   */
  constructor(public dialogRef: MdDialogRef<GithubDialogRepositoriesComponent>,
              private auth: GithubService,
              private errorHandler: ErrorService) {
  }

  /**
   * On component initialize, Get all repositories from github
   */
  ngOnInit() {
    this.loading = false;
    this.auth.getRepositories(this.accessToken)
      .then(result => this.repositories = result)
      .catch(e => this.errorHandler.apiError(e))
      .then(() => this.loading = true);
  }

  /**
   * Select an option
   * @param option
   */
  toggleOption(option) {
    this.repository = option;
  }
}
