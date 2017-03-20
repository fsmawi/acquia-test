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
   * Branches available for the application
   */
  branches: Array<string>;

  /**
   * Branch selected or typed
   */
  branch: string;

  /**
   * Branch suggestions filtered with the input provided
   */
  branchSuggestions: Array<string>;

  /**
   * Flag to show/hide the direct start feature
   */
  isDirectStartAvailable = true;

  /**
   * Flag to check if the job is running
   */
  didJobStart: boolean;

  /**
   * Builds the component
   * @param dialogRef
   * @param pipelineService
   * @param flashMessageService
   * @param errorHandler
   */
  constructor(
    public dialogRef: MdDialogRef<StartJobComponent>,
    private pipelineService: PipelinesService,
    private flashMessageService: FlashMessageService,
    private errorHandler: ErrorService) {
  }

  /**
   * Initialize component
   */
  ngOnInit() {
    this.branches = ['master'];
    this.pipelineService.getBranches(this.appId)
      .then(branches => {
        if (branches.indexOf('master') > -1) {
          this.branches = branches;
        } else {
          this.branches = ['master'].concat(branches);
        }
      })
      .catch(e => this.errorHandler.apiError(e));

    this.isDirectStartAvailable = features.directStart;
  }

  /**
   * Filters the available branches by the typed input
   */
  filter() {
    if (this.branch === '' || !this.branch) {
      this.branchSuggestions = [];
    } else {
      this.branchSuggestions = this.branches ? this.branches.filter(branch =>
        branch.toLowerCase().indexOf(this.branch.toLowerCase()) > -1) : [];
    }
  }

  /**
   * Selects the branch and holds it to start
   * @param branch
   */
  select(branch) {
    this.branch = branch;
    this.branchSuggestions = [];
  }

  /**
   * Direct start the job for the selected branch
   */
  start() {
    if (this.branch && this.branch !== '') {
      this.didJobStart = true;
      this.pipelineService.directStartJob(this.appId, this.branch)
        .then((res) => {
          this.flashMessageService.showSuccess('Your job has started.');
        })
        .catch(e => {
          this.flashMessageService.showError(e.status + ' : ' + e._body);
          this.errorHandler.apiError(e)
            .reportError(e, 'FailedToDirectStartJob', {component: 'start-job', appId : this.appId}, 'error');
        })
        .then(() => {
          this.dialogRef.close();
          this.didJobStart = false;
        });
    }
  }

}
