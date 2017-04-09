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


@Component({
  selector: 'app-action-header',
  templateUrl: './action-header.component.html',
  styleUrls: ['./action-header.component.scss']
})
export class ActionHeaderComponent implements OnInit {

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
   * Builds the component
   * @param pipelineService
   * @param errorHandler
   * @param segment
   * @param flash
   * @param confirmationModalService
   * @param dialog
   * @param media
   */
  constructor(private pipelineService: PipelinesService,
              private errorHandler: ErrorService,
              private segment: SegmentService,
              private flash: FlashMessageService,
              private confirmationModalService: ConfirmationModalService,
              private dialog: MdDialog,
              public media: ObservableMedia) {
  }

  ngOnInit() {
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

}
