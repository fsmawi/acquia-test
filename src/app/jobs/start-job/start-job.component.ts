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
  isDirectStartAvailable: boolean;


  /**
   * Builds the component
   * @param dialogRef
   * @param pipelineService
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
    this.pipelineService.getBranches(this.appId)
      .then(branches => this.branches = branches)
      .catch(e => this.errorHandler.apiError(e));

    this.isDirectStartAvailable = features.directStart;

  }

  isValidBranch() {
    if (this.branch && this.branch !== '' && this.branches.indexOf(this.branch) > -1) {
      return true;
    }
    return false;
  }

  filter() {
    if (this.branch === '' || !this.branch) {
      this.branchSuggestions = [];
    } else {
      this.branchSuggestions = this.branches ? this.branches.filter(branch =>
        branch.toLowerCase().indexOf(this.branch.toLowerCase()) > -1) : [];
    }
  }

  select(branch) {
    this.branch = branch;
    this.branchSuggestions = [];
  }

  start() {
    this.pipelineService.directStartJob(this.appId, this.branch)
      .then((res) => {
        this.flashMessageService.showSuccess('Your job has started.');
      })
      .catch(e => {
        this.flashMessageService.showError('Error while starting your job.', e);
        this.errorHandler.apiError(e);
      })
      .then(() => this.dialogRef.close());
  }

}
