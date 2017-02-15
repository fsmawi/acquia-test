import {Component, OnInit, Input} from '@angular/core';
import {Job} from '../../core/models/job';

@Component({
  selector: 'app-job-summary',
  templateUrl: 'job-summary.component.html',
  styleUrls: ['job-summary.component.scss']
})
export class JobSummaryComponent implements OnInit {
  /**
   * Job whose details to be shown
   */
  @Input()
  job: Job;

  /**
   * AppId, required for navigation
   */
  @Input()
  appId: string;

  /**
   * Flag to show progress bar at the bottom of the meta/summary container eg: true for job details
   * @type {boolean}
   */
  @Input()
  isProgressBarRequired = false;

  /**
   * Flag to enable/show the navigation link on the status bar eg: false for job details
   * @type {boolean}
   */
  @Input()
  isJobNavigationRequired = true;

  /**
   * Builds the component and injects services if needed
   */
  constructor() { }

  /**
   * Initialize
   */
  ngOnInit() {
  }

}
