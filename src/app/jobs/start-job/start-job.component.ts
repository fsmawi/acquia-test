import {Router} from '@angular/router';
import {Component, OnInit, Input, ViewChild, ElementRef} from '@angular/core';
import {MdDialogRef} from '@angular/material';

import {PipelinesService} from '../../core/services/pipelines.service';
import {ErrorService} from '../../core/services/error.service';
import {FlashMessageService} from '../../core/services/flash-message.service';
import {features} from '../../core/features';


@Component({
  selector: 'app-start-job',
  templateUrl: './start-job.component.html',
  styleUrls: ['./start-job.component.scss']
})
export class StartJobComponent implements OnInit {

  /**
   * App Id segment tracking.
   */
  @Input()
  appId: string;

  /**
   * Redirect to job list after start.
   */
  @Input()
  redirect = false;

  /**
   * Branches available for the application
   */
  branches: Array<string>;

  /**
   * Branch selected or typed
   */
  branch: string;

  /**
   * Flag to show/hide the direct start feature
   */
  isDirectStartAvailable = true;

  /**
   * Flag to check if the job is running
   */
  didJobStart: boolean;

  /**
   * Flag to check if the job started
   * @type {boolean}
   */
  jobStarted: boolean;

  /**
   * Flag to show/hide how to start job modal
   * @type {Boolean}
   */
  startJobHelp = false;

  /**
   * Builds the component
   * @param dialogRef
   * @param pipelineService
   * @param flashMessageService
   * @param errorHandler
   * @param router
   */
  constructor(
    public dialogRef: MdDialogRef<StartJobComponent>,
    private pipelineService: PipelinesService,
    private flashMessageService: FlashMessageService,
    private errorHandler: ErrorService,
    private router: Router) {
  }

  /**
   * Initialize component,
   * and get all repository branches for the current application
   */
  ngOnInit() {
    this.pipelineService.getBranches(this.appId)
      .then(branches => {
        if (branches.length) {
          this.branches = branches;
        } else {
          this.branches = ['master'];
        }
      })
      .catch(e => this.errorHandler.apiError(e));

    this.isDirectStartAvailable = features.directStart;
  }

  /**
   * Selects the branch and holds it to start
   * @param branch
   */
  setBranch(branch) {
    this.branch = branch;
  }

  /**
   * Direct start the job for the selected branch
   */
  start() {
    if (this.branch && this.branch !== '') {
      this.didJobStart = true;
      this.jobStarted = false;
      this.pipelineService.directStartJob(this.appId, this.branch)
        .then((res) => {
          this.flashMessageService.showSuccess('Your job has started.');
          this.jobStarted = true;
        })
        .catch(e => {
          this.flashMessageService.showError(e.status + ' : ' + e._body);
          this.errorHandler.apiError(e)
            .reportError(e, 'FailedToDirectStartJob', {component: 'start-job', appId : this.appId}, 'error');
        })
        .then(() => {
          this.dialogRef.close();
          this.didJobStart = false;
          if (this.jobStarted && this.redirect) {
            this.router.navigateByUrl(`/applications/${this.appId}`);
          }
        });
    }
  }

  /**
   * Show how to start job modal
   */
  showHelp() {
    this.startJobHelp = true;
  }

  /**
   * Hide how to start job modal
   */
  hideHelp() {
    this.startJobHelp = false;
  }
}
