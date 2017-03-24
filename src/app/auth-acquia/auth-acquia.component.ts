import {ActivatedRoute, Router} from '@angular/router';
import {Component, OnInit} from '@angular/core';
import {MdDialog, MdDialogRef} from '@angular/material';

import {Alert} from '../core/models/alert';
import {BaseApplication} from '../core/classes/base-application';
import {ErrorService} from '../core/services/error.service';
import {PipelinesService} from '../core/services/pipelines.service';
import {StartJobComponent} from '../jobs/start-job/start-job.component';

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
   * Alert for github connection status
   * @type {Alert}
   */
  connectionAlert = new Alert();

  /**
   * Build the component
   * @param route
   * @param router
   * @param errorHandler
   * @param pipelines
   * @param dialog
   */
  constructor(
    private route: ActivatedRoute,
    private router: Router,
    protected errorHandler: ErrorService,
    protected pipelines: PipelinesService,
    private dialog: MdDialog) {
    super(errorHandler, pipelines);
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
    this.pipelines.removeGitHubAuth(this.repository, this.appId)
      .then(res => {
        return this.refresh()
          .then((info) => {
            this.router.navigate(['application', this.appId]);
          });
      })
      .catch(e => {
        this.errorHandler.apiError(e)
          .reportError(e, 'FailedRemoveGitHubAuth', {component: 'auth-acquia', appId: this.appId}, 'error');
        this.showConnectionAlert('danger', e.status + ' : ' + e._body);
      })
      .then(() => this.connectionLoading = false);
  }

  /**
   * Show github connection status
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
      this._appId = this.appId = params['app-id'];
      this.getConfigurationInfo();
    });
  }
}
