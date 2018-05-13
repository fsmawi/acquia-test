import {ActivatedRoute, Router} from '@angular/router';
import {Component, OnInit} from '@angular/core';

import {BaseApplication} from '../core/classes/base-application';
import {ConfirmationModalService} from '../core/services/confirmation-modal.service';
import {ErrorService} from '../core/services/error.service';
import {features} from '../core/features';
import {FlashMessageService} from '../core/services/flash-message.service';
import {PipelinesService} from '../core/services/pipelines.service';
import {animations} from '../core/animations';
import {repoType} from '../core/repository-types';
import {SegmentService} from '../core/services/segment.service';
import {LiftService} from '../core/services/lift.service';

import {Environment} from '../core/models/environment';

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
   * Repository type eg. Github, Acquia, Bitbucket
   */
  repositoryType: string;

  /**
   * Webhooks status enabled/disabled
   */
  webhook = 'disabled';

  /**
   * Environment list.
   * @type {Environment[]}
   */
  environments: Environment[];

  /**
   * Db sync environment.
   */
  environmentDbSync: string;

  /**
   * Build the component
   * @param route
   * @param router
   * @param pipelines
   * @param errorHandler
   * @param confirmationModalService
   * @param flashMessage
   * @param lift
   * @param segment
   */
  constructor(
    private route: ActivatedRoute,
    private router: Router,
    protected pipelines: PipelinesService,
    protected errorHandler: ErrorService,
    private confirmationModalService: ConfirmationModalService,
    private flashMessage: FlashMessageService,
    private lift: LiftService,
    private segment: SegmentService) {
    super(flashMessage, errorHandler, pipelines, confirmationModalService);
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
        this.webhook = info.hasOwnProperty('webhook') ? info.webhook ? 'enabled' : 'disabled' : 'not-reachable';

        this.environments = info.hasOwnProperty('environments') ? info.environments : [];
        for (const env of this.environments) {
          if (info.db_sync_source_env === env.id) {
            this.environmentDbSync = info.db_sync_source_env;
            break;
          }
        }

        this.setRepositoryType();
      })
      .catch(e => {
        this.errorHandler.apiError(e).reportError(e, 'FailedToGetApplicationInfo',
          {component: 'application', appId: this.appId}, 'error');
        if (this.errorHandler.isForbiddenPipelinesError()) {
          this.errorHandler.showError('Job list', '/applications/' + this.appId);
        } else {
          this.flashMessage.showError(e.status + ' : ' + e._body);
        }
      })
      .then(() => this.appLoading = false);
  }

  /**
   * Set Repository type
   */
  setRepositoryType() {
    if (repoType[this.vcsType]) {
      this.repositoryType = repoType[this.vcsType].name;
    } else {
      this.repositoryType = 'Acquia Git';
    }
  }

  /**
   *  Initialize, and get pipeline information
   */
  ngOnInit() {
    this.route.params.subscribe((params) => {
      BaseApplication._appId = this.appId = params['app'];
      this.getConfigurationInfo(true);
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

  /**
   * Enables/disables the webhooks for the application
   */
  updateWebhooks() {
    this.lift.captureEvent(`${this.webhook}Webhooks`, {appId: this.appId});
    this.segment.trackEvent(`${this.webhook}Webhooks`, {appId: this.appId});
    this.pipelines.updateWebhooks(this.appId, this.webhook === 'enabled')
      .then(res => {
        this.appLoading = true;
        if (res.success) {
          this.flashMessage.showSuccess('Update successful. Webhooks ' + this.webhook + '.');
        } else {
          this.flashMessage.showError('Error while updating webhooks.');
        }
      })
      .catch(e => {
        this.errorHandler.apiError(e).reportError(e, 'FailedToUpdateWebhooks',
          {component: 'application', appId: this.appId}, 'error');
        this.flashMessage.showError(e.status + ' : ' + e._body);
      })
      .then(() => this.getConfigurationInfo(true));
  }

  /**
   * Sets the DB Sync source environment parameter.
   */
  updateDbSyncParam() {
    this.pipelines.updateDbSyncParam(this.appId, this.environmentDbSync)
      .then(res => {
        this.appLoading = true;
        if (res.success) {
          this.flashMessage.showSuccess('Database sync environment has been updated successfully.');
        } else {
          this.flashMessage.showError('Error while updating DB sync Environment.');
        }
      })
      .catch(e => {
        this.errorHandler.apiError(e).reportError(e, 'FailedToUpdateDbSyncParam',
          {component: 'application', appId: this.appId}, 'error');
        this.flashMessage.showError(e.status + ' : ' + e._body);
      })
      .then(() => this.getConfigurationInfo(true));
  }
}
