import {ActivatedRoute} from '@angular/router';
import {Component, OnInit} from '@angular/core';
import {MdDialog, MdDialogRef} from '@angular/material';

import {BaseApplication} from '../../core/classes/base-application';
import {ErrorService} from '../../core/services/error.service';
import {FlashMessageService} from '../../core/services/flash-message.service';
import {PipelinesService} from '../../core/services/pipelines.service';
import {StartJobComponent} from '../../jobs/start-job/start-job.component';

@Component({
  selector: 'app-configure',
  templateUrl: './configure.component.html',
  styleUrls: ['./configure.component.scss']
})
export class ConfigureComponent extends BaseApplication implements OnInit {

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
   * VCS type eg. git, acquia-git
   * @type {String}
   */
  vcsType = 'acquia-git';

  /**
   * Repo full name
   */
  repoFullName: string;

  /**
   * Build the component
   * @param route
   * @param errorHandler
   * @param flashMessage
   * @param pipelines
   * @param dialog
   */
  constructor(
    private route: ActivatedRoute,
    protected errorHandler: ErrorService,
    private flashMessage: FlashMessageService,
    protected pipelines: PipelinesService,
    private dialog: MdDialog) {
    super(flashMessage, errorHandler, pipelines);
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
        this.vcsType = info.repo_type;
        this.repoFullName = info.repo_name;
      })
      .catch(e => {
        this.errorHandler.apiError(e).reportError(e, 'FailedToGetApplicationInfo',
            {component: 'application-configure', appId: this.appId}, 'error');
        this.flashMessage.showError(e.status + ' : ' + e._body);
      })
      .then(() => this.appLoading = false);
  }

  /**
   *  Initialize, and get application information
   */
  ngOnInit() {
    this.route.params.subscribe((params) => {
      BaseApplication._appId = this.appId = params['app'];
      this.getConfigurationInfo();
    });
  }
}
