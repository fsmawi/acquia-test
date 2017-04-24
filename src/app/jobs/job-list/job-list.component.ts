import {Component, OnInit, Input} from '@angular/core';

import {ConfirmationModalService} from '../../core/services/confirmation-modal.service';
import {environment} from '../../../environments/environment';
import {ErrorService} from '../../core/services/error.service';
import {FlashMessageService} from '../../core/services/flash-message.service';
import {Job} from '../../core/models/job';
import {PipelinesService} from '../../core/services/pipelines.service';
import {SegmentService} from '../../core/services/segment.service';

@Component({
  selector: 'app-job-list',
  templateUrl: './job-list.component.html',
  styleUrls: ['./job-list.component.scss']
})
export class JobListComponent implements OnInit {

  /**
   * Pipelines cloud url
   * @type {string}
   */
  cloudUrl: string;

  /**
   * List of Jobs to Display
   */
  @Input()
  jobs: Array<Job>;

  /**
   * App ID, used to make back links
   */
  @Input()
  appId: string;

  /**
   * Builds the component and injects services if needed
   */
  constructor(private pipelines: PipelinesService,
              private confirmationModalService: ConfirmationModalService,
              private flashMessageService: FlashMessageService,
              private segment: SegmentService,
              private errorHandler: ErrorService) {
  }

  /**
   * Initialize
   */
  ngOnInit() {
    // In the production environment, all job links should specify the cloud url,
    // which will allow multiple windows/tabs to be open
    if (environment.production && environment.name === 'prod') {
      this.cloudUrl = `${environment.authRedirect}/app/develop/applications/${this.appId}/pipelines/jobs`;
    } else {
      this.cloudUrl = `/applications/${this.appId}`;
    }

    // Track page view
    this.segment.page('JobListView');
  }

  /**
   * Restarts a Job (Launches new with same params)
   * @param job
   */
  restartJob(job: Job) {
    this.pipelines.startJob(this.appId, job.pipeline_id, {
      commit: job.commit || undefined,
      branch: !job.commit ? job.branch : undefined
    }).then(result => {
      console.log(result);
    }).catch(e => {
      this.flashMessageService.showError(e.status + ' : ' + e._body);
      this.errorHandler.apiError(e)
        .reportError(e, 'FailedToRestartJob', {component: 'job-list', appId: this.appId}, 'error');
    });
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
          this.pipelines.stopJob(this.appId, job.job_id)
            .then((res) => {
              this.flashMessageService.showSuccess('Your job is terminating');
              this.segment.trackEvent('TerminateJobFromUI', {appId: this.appId, jobId: job.job_id});
            })
            .catch(e => {
              this.flashMessageService.showError(e.status + ' : ' + e._body);
              this.errorHandler.apiError(e)
                .reportError(e, 'FailedToStopJob', {component: 'job-list', appId: this.appId}, 'error');
            });
        }
      });
  }
}
