import {Component, OnInit, Input, OnDestroy} from '@angular/core';
import {MdDialogRef, MdDialog} from '@angular/material';
import {ObservableMedia} from '@angular/flex-layout';

import {Subscription} from 'rxjs/Subscription';

import {Job} from '../../core/models/job';
import {PipelinesService} from '../../core/services/pipelines.service';
import {ErrorService} from '../../core/services/error.service';
import {FlashMessageService} from '../../core/services/flash-message.service';
import {ConfirmationModalService} from '../../core/services/confirmation-modal.service';
import {SegmentService} from '../../core/services/segment.service';
import {StartJobComponent} from '../../jobs/start-job/start-job.component';
import {HelpCenterService} from '../../core/services/help-center.service';
import {EncryptCredentialsComponent} from '../encrypt-credentials/encrypt-credentials.component';

// Global Scope, Window
declare const window;

@Component({
  selector: 'app-action-header',
  templateUrl: './action-header.component.html',
  styleUrls: ['./action-header.component.scss']
})
export class ActionHeaderComponent implements OnInit {

  /**
   * Holds the title of the page
   */
  @Input()
  title: string;

  /**
   * Holds the name of the repo
   */
  @Input()
  repoName = 'Pipelines';

  /**
   * Holds the application Id
   */
  @Input()
  appId: string;

  /**
   * Holds the job information for job details
   */
  @Input()
  job: Job;

  /**
   * Flag to show application info action
   * @type {boolean}
   */
  @Input()
  showViewInfo = false;

  /**
   * Flag to show start job action
   * @type {boolean}
   */
  @Input()
  showStartJob = false;

  /**
   * Flag to show stop job action
   * @type {boolean}
   */
  @Input()
  showStopJob = false;

  /**
   * Flag to show open environment
   * @type {boolean}
   */
  @Input()
  showOpenEnvironment = false;

  /**
   * Type of current page (used to display bread crumb)
   * @type {string}
   */
  @Input()
  pageType: string;

  /**
   * Flag to show help center
   * @type {boolean}
   */
  @Input()
  showHelpCenter = true;

  /**
   * Flag to show more menu
   * @type {boolean}
   */
  @Input()
  showMoreMenu = true;

  /**
   * Builds the component
   * @param pipelineService
   * @param errorHandler
   * @param segment
   * @param flash
   * @param confirmationModalService
   * @param helpCenterService
   * @param dialog
   * @param media
   */
  constructor(private pipelineService: PipelinesService,
              private errorHandler: ErrorService,
              private segment: SegmentService,
              private flash: FlashMessageService,
              private confirmationModalService: ConfirmationModalService,
              private helpCenterService: HelpCenterService,
              private dialog: MdDialog,
              public media: ObservableMedia) {
  }

  /**
   * Initialize
   */
  ngOnInit() {
    window.onclick = function(event) {
      if (!event.target.classList.contains('menu-title')
        && !event.target.classList.contains('hover-menu')) {
        const menu = document.getElementById('dropdown-links');
        if (menu && menu.classList.contains('show')) {
          menu.classList.remove('show');
        }
      }
    };
  }

  /**
   * Stops a running job
   * @param job
   */
  stopJob(job: Job) {
    this.confirmationModalService
      .openDialog('Terminate Job', 'Are you sure you want to terminate your job?', 'Yes', 'Cancel')
      .then(result => {
        if (result) {
          this.pipelineService.stopJob(this.appId, job.job_id)
            .then((res) => {
              this.flash.showSuccess('Your job is terminating');
              this.segment.trackEvent('TerminateJobFromUI', {appId: this.appId, jobId: job.job_id});
            })
            .catch(e => {
              this.flash.showError(e.status + ' : ' + e._body);
              this.errorHandler.apiError(e)
                .reportError(e, 'FailedToStopJob', {component: 'job-list', appId: this.appId}, 'error');
            });
        }
      });
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
    }
    // Track button click
    this.segment.trackEvent('ClickStartJobButton', {appId: this.appId});
  }

  /**
   * Show the Encrypt Credentials Dialog
   */
  showEncryptCredentials() {
    let dialogRef: MdDialogRef<EncryptCredentialsComponent>;
    dialogRef = this.dialog.open(EncryptCredentialsComponent);
    if (dialogRef) {
      dialogRef.componentInstance.appId = this.appId;
    }
  }

  /**
   * Toggle menu
   */
  toggleMenu() {
    const menu = document.getElementById('dropdown-links');
    if (menu.classList.contains('show')) {
      menu.classList.remove('show');
    } else {
      menu.classList.add('show');
    }
  }

  /**
   * Open the help center
   */
  showHelpCenterDrawer() {
    this.helpCenterService.show();
  }
}
