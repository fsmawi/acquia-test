import {ActivatedRoute, Router} from '@angular/router';
import {Component, OnInit} from '@angular/core';

import {BaseApplication} from '../core/classes/base-application';
import {ConfirmationModalService} from '../core/services/confirmation-modal.service';
import {ErrorService} from '../core/services/error.service';
import {features} from '../core/features';
import {FlashMessageService} from '../core/services/flash-message.service';
import {GithubStatus} from '../core/models/github-status';
import {PipelinesService} from '../core/services/pipelines.service';
import {animations} from '../core/animations';

@Component({
  selector: 'app-application',
  templateUrl: './application.component.html',
  styleUrls: ['./application.component.scss'],
  animations: animations
})
export class ApplicationComponent extends BaseApplication implements OnInit {

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
   * Repository type eg. Github, Acquia, BitBucket
   */
  repositoryType: string;

  /**
   * Build the component
   * @param route
   * @param pipelines
   * @param errorHandler
   * @param flashMessage
   * @param router
   * @param confirmationModalService
   */
  constructor(
    private route: ActivatedRoute,
    private router: Router,
    protected pipelines: PipelinesService,
    protected errorHandler: ErrorService,
    private confirmationModalService: ConfirmationModalService,
    private flashMessage: FlashMessageService) {
    super(errorHandler, pipelines);
  }

  /**
   * Get Configuration Information
   * @param force
   */
  getConfigurationInfo(force = false) {
    this.appLoading = true;
    this.getInfo(force)
      .then((info) => {
        this.repoFullName = info.repo_name;
        this.vcsType = info.repo_type;
        this.gitUrl = info.repo_url;
        this.gitClone = this.gitUrl ? `git clone --branch [branch] ${this.gitUrl} [destination]` : '';
        this.setRepositoryType();
      })
      .catch(e => {
        this.errorHandler.apiError(e).reportError(e, 'FailedToGetApplicationInfo',
          {component: 'application', appId: this.appId}, 'error');
        this.flashMessage.showError(e.status + ' : ' + e._body);
      })
      .then(() => this.appLoading = false);
  }

  /**
   * Set Repository type
   */
  setRepositoryType() {
    switch (this.vcsType) {
      case 'github':
        this.repositoryType = 'Github';
        break;
      default:
        this.repositoryType = 'Acquia Git';
        break;
    }
  }

  /**
   *  Initialize, and get pipeline information
   */
  ngOnInit() {
    this.route.params.subscribe((params) => {
      this._appId = this.appId = params['app-id'];
      this.getConfigurationInfo();
    });
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
          this.getConfigurationInfo(true);
        }
      });
  }
}
