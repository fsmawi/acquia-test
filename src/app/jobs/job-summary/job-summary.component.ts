import {Component, OnInit, Input, OnDestroy} from '@angular/core';

import * as moment from 'moment';
import {Subscription, Observable} from 'rxjs/Rx';

import {environment} from '../../../environments/environment';
import {Job} from '../../core/models/job';


@Component({
  selector: 'app-job-summary',
  templateUrl: 'job-summary.component.html',
  styleUrls: ['job-summary.component.scss']
})
export class JobSummaryComponent implements OnInit, OnDestroy {

  /**
   * If in production environment
   * @type {boolean}
   */
  envProd: boolean;

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
   * Event handler for calculating the duration
   */
  timer: Subscription;

  /**
   * Human friendly duration string when a job is in progress
   * @type {string}
   */
  calculatedDuration = '00:00';

  /**
   * Pipelines cloud url
   * @type {string}
   */
  cloudUrl: string;

  /**
   * Repo full name to be shown
   */
  @Input()
  repoFullName: string;

  /**
   * VCS type eg. git, acquia-git
   */
  @Input()
  vcsType: string;

  /**
   * Builds the component and injects services if needed
   */
  constructor() {
  }

  /**
   * Initialize
   */
  ngOnInit() {
    this.envProd = false;
    // In the production environment, all job links should specify the cloud url,
    // which will allow multiple windows/tabs to be open
    if (environment.production && environment.name === 'prod') {
      this.envProd = true;
      this.cloudUrl = `${environment.authRedirect}/app/develop/applications/${this.appId}/pipelines/jobs`;
    } else {
      this.cloudUrl = `/jobs/${this.appId}`;
    }

    if (this.job && this.job.isUnfinished && !this.timer) {
      // use a timer for consistent updates
      this.timer = Observable.timer(1, 1000).subscribe(() => this.calculateDuration());
    }
  }

  /**
   * Releases any bindings on destroy
   */
  ngOnDestroy() {
    if (this.timer) {
      this.timer.unsubscribe();
    }
  }

  /**
   * Calculates a human friendly duration string
   */
  calculateDuration() {
    if (this.job.isUnfinished) {
      const duration = moment.duration((+new Date()) - +moment.unix(<number>this.job.requested_at).toDate());
      const minutes = duration.minutes();
      const seconds = duration.seconds();
      const normMinutes = minutes < 10 ? `0${minutes}` : minutes;
      const normSeconds = seconds < 10 ? `0${seconds}` : seconds;
      this.calculatedDuration = `${normMinutes}:${normSeconds}`;
    } else {
      this.timer.unsubscribe();
    }
  }
}
