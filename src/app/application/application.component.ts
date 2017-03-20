import {Component, OnInit} from '@angular/core';
import {ActivatedRoute, Router} from '@angular/router';

import {PipelinesService} from '../core/services/pipelines.service';
import {ErrorService} from '../core/services/error.service';
import {FlashMessageService} from '../core/services/flash-message.service';
import {GithubStatus} from '../core/models/github-status';
import {N3Service} from '../core/services/n3.service';
import {ConfirmationModalService} from '../core/services/confirmation-modal.service';
import {features} from '../core/features';


@Component({
  selector: 'app-application',
  templateUrl: './application.component.html',
  styleUrls: ['./application.component.scss']
})
export class ApplicationComponent implements OnInit {

  /**
   * Application ID
   * @type {string}
   */
  appId: string;

  /**
   * Git URL
   * @type {string}
   */
  gitUrl: string;

  /**
   * Git clone
   * @type {string}
   */
  gitClone: string;

  /**
   * Git Add
   * @type {string}
   */
  gitAdd = 'git add .';

  /**
   * Git commit
   * @type {string}
   */
  gitCommit = 'git commit -m "[commit message]"';

  /**
   * Git push
   * @type {string}
   */
  gitPush = 'git push origin [branch]';

  /**
   * Loading Indicator
   * @type {Boolean}
   */
  appLoading = false;

  /**
   * Repo full name
   */
  repoFullName: string;

  /**
   * VCS type eg. git, acquia-git
   */
  vcsType: string;

  /**
   * Flag to toggle vcs type icon feature
   */
  vcsTypeIconFeature: boolean;

  /**
   * Build the component
   * @param route
   * @param router
   * @param pipelines
   * @param n3Service
   * @param errorHandler
   * @param confirmationModalService
   * @param flashMessage
   */
  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private pipelines: PipelinesService,
    private n3Service: N3Service,
    private errorHandler: ErrorService,
    private confirmationModalService: ConfirmationModalService,
    private flashMessage: FlashMessageService) {
  }

  /**
   * Get Configuration Information
   */
    getConfigurationInfo() {
    this.pipelines.getGithubStatus(this.appId)
      .then((status: GithubStatus) => {
        if (!status.connected) {
          this.flashMessage.showInfo('You are not connected yet');
        } else {
          const regex = /^((git@[\w\.]+:)|((http|https):\/\/[\w\.]+\/?))([\w\.@\:/\-~]+)(\.git)(\/)?$/;
          const repoInfo = status.repo_url.match(regex);
          this.repoFullName = repoInfo[5];
          this.gitUrl = status.repo_url;
          this.gitClone = `git clone --branch [branch] ${this.gitUrl} [destination]`;
        }
        return status;
      })
      .then(status => {
        // Get the VCS Info if connected
        if (this.vcsTypeIconFeature && status.connected) {
          this.n3Service.getEnvironments(this.appId)
            .then(environments => this.vcsType = environments[0].vcs.type)
            .catch(e => this.errorHandler.apiError(e));
        }
      })
      .catch(e => {
        this.errorHandler.apiError(e)
          .reportError(e, 'FailedToGetGithubStatus', {component: 'application', appId: this.appId}, 'error');
        this.flashMessage.showError(e.status + ' : ' + e._body);
      })
      .then(() => this.appLoading = false);
  }

  /**
   *  Initialize, and get pipeline information
   */
  ngOnInit() {
    this.appLoading = true;
    this.route.params.subscribe((params) => {
      this.appId = params['app-id'];
      this.getConfigurationInfo();
    });
    this.vcsTypeIconFeature = features.vcsTypeIcon;
  }

  /**
   * Removes GitHub authentication from the app
   */
  removeAuth() {
    this.confirmationModalService
      .openDialog('Remove Authentication',
        'Are you sure you want to remove GitHub authentication from your app?', 'Yes', 'Cancel')
      .then(result => {
        if (result) {
          const regex = /^((git@[\w\.]+:)|((http|https):\/\/[\w\.]+\/?))([\w\.@\:/\-~]+)(\.git)(\/)?$/;
          const repoInfo = this.gitUrl.match(regex);
          this.pipelines.removeGitHubAuth(repoInfo[5], this.appId)
            .then(res => {
              this.flashMessage.showSuccess('GitHub authentication has been removed.');
              // Reload after removing auth
              this.appLoading = true;
              this.getConfigurationInfo();
            })
            .catch(e => {
              this.errorHandler.apiError(e);
              this.errorHandler.reportError(e, 'FailedRemoveGitHubAuth', {}, 'error');
              this.flashMessage.showError('Unable to remove GitHub authentication.', e);
            });
        }
      });
  }
}
