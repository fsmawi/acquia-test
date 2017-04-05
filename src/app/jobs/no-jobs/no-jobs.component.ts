import {Component, OnInit, Input} from '@angular/core';
import {SegmentService} from '../../core/services/segment.service';
import {MdDialogRef, MdDialog} from '@angular/material';
import {StartJobComponent} from '../start-job/start-job.component';

@Component({
  selector: 'app-no-jobs',
  templateUrl: './no-jobs.component.html',
  styleUrls: ['./no-jobs.component.scss']
})
export class NoJobsComponent implements OnInit {

  /**
   * VCS Type to shown eg., git, acquia-git
   */
  @Input()
  vcsType: string;

  /**
   * App Id for segment tracking and Navigation
   */
  @Input()
  appId: string;

  /**
   * Build the component
   * @param segment
   * @param dialog
   */
  constructor(private segment: SegmentService,
              private dialog: MdDialog) {
  }

  /**
   * Initialize component
   */
  ngOnInit() {
    // Track page view and leaving page event
    this.segment.page('NoJobView');
    window.onbeforeunload = () => this.segment.trackEvent('NoJobsLeave', {appId: this.appId});
  }

  /**
   * Open Dialog to start job and inform the user about the different
   * ways how to start a Pipelines job
   */
  startJob() {
    let dialogRef: MdDialogRef<StartJobComponent>;
    dialogRef = this.dialog.open(StartJobComponent);
    if (dialogRef) {
      dialogRef.componentInstance.appId = this.appId;
    }
  }

}
