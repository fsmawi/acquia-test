import {ActivatedRoute, Router} from '@angular/router';
import {Component, OnInit} from '@angular/core';
import {MdDialog, MdDialogRef} from '@angular/material';

import {Alert} from '../core/models/alert';
import {BaseApplication} from '../core/classes/base-application';
import {ErrorService} from '../core/services/error.service';
import {PipelinesService} from '../core/services/pipelines.service';
import {StartJobComponent} from '../jobs/start-job/start-job.component';
import {repoType} from '../core/repository-types';
import {FlashMessageService} from '../core/services/flash-message.service';
import {ConfirmationModalService} from '../core/services/confirmation-modal.service';

@Component({
  selector: 'app-auth-acquia',
  templateUrl: './auth-acquia.component.html',
  styleUrls: ['./auth-acquia.component.scss']
})
export class AuthAcquiaComponent extends BaseApplication implements OnInit {

  /**
   * Application ID
   * @type {string}
   */
  appId: string;

  /**
   * Loading Indicator
   * @type {Boolean}
   */
  appLoading = false;

  /**
   * Connection Loading Indicator
   * @type {Boolean}
   */
  connectionLoading = false;

  /**
   * Flag to see if the app is connected to Acquia Git
   */
  isConnected: boolean;

  /**
   * Attached repository (Github)
   * @type {string}
   */
  repository: string;

  /**
   * Alert for git service connection status
   * @type {Alert}
   */
  connectionAlert = new Alert();

  /**
   * Repository type
   * @type {string}
   */
  repoType: string;

  /**
   * Holds current Repository type Label
   * @type {string}
   */
  currentTypeLabel: string;

  /**
   * Build the component
   * @param route
   * @param router
   * @param errorHandler
   * @param pipelines
   * @param flashMessage
   * @param dialog
   * @param confirmationModalService
   */
  constructor(
    private route: ActivatedRoute,
    private router: Router,
    protected errorHandler: ErrorService,
    protected pipelines: PipelinesService,
    protected flashMessage: FlashMessageService,
    private dialog: MdDialog,
    private confirmationModalService: ConfirmationModalService) {
    super(flashMessage, errorHandler, pipelines, confirmationModalService);
  }

  /**
   * Open Dialog to informs the user about the different
   * ways how to start a Pipelines job
   */
  startJob() {
    let dialogRef: MdDialogRef<StartJobComponent>;
    dialogRef = this.dialog.open(StartJobComponent);
    if (dialogRef) {
      dialogRef.componentInstance.appId = this.appId;
      dialogRef.componentInstance.redirect = true;
    }
  }

  /**
   * Get Configuration Information
   * @param force
   */
  getConfigurationInfo(force = false) {
    this.appLoading = true;
    this.getInfo(force)
      .then((info: any) => {
        this.isConnected = (info.repo_type === 'acquia-git');
        this.repository = info.repo_name;
        this.repoType = info.repo_type;
        this.currentTypeLabel = repoType[this.repoType].name;
      })
      .catch(e => {
        this.errorHandler.apiError(e).reportError(e, 'FailedToGetApplicationInfo',
            {component: 'auth-acquia', appId: this.appId}, 'error');
        this.showConnectionAlert('danger', e.status + ' : ' + e._body);
      })
      .then(() => this.appLoading = false);
  }

  /**
   * Enable Acquia Git (remove other conections)
   */
  enableAcquiaGit() {
    this.connectionLoading = true;
    this.pipelines.removeOauthGitAuth(this.repository, this.appId, this.repoType)
      .then(res => {
        return this.refresh()
          .then((info) => {
            this.router.navigateByUrl(`/applications/${this.appId}/info`);
          });
      })
      .catch(e => {
        this.errorHandler.apiError(e)
          .reportError(e, `FailedRemove${this.currentTypeLabel}Auth`, {component: 'auth-acquia', appId: this.appId}, 'error');
        this.showConnectionAlert('danger', e.status + ' : ' + e._body);
      })
      .then(() => this.connectionLoading = false);
  }

  /**
   * Show git service connection status
   * @param type
   * @param message
   */
  showConnectionAlert(type: string, message: string) {
    this.connectionAlert.display = true;
    this.connectionAlert.message = message;
    this.connectionAlert.type = type;
  }

  /**
   *  Initialize, and get application information
   */
  ngOnInit() {
    this.isConnected = false;
    this.route.params.subscribe((params) => {
      BaseApplication._appId = this.appId = params['app-id'];
      this.getConfigurationInfo();
    });
  }
}
