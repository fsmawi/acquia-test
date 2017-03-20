import {ActivatedRoute} from '@angular/router';
import {Component, OnInit, OnDestroy} from '@angular/core';
import {MdDialog, MdDialogRef} from '@angular/material';

import {ErrorService} from '../core/services/error.service';
import {Job} from '../core/models/job';
import {PipelinesService} from '../core/services/pipelines.service';
import {SegmentService} from '../core/services/segment.service';
import {StartJobComponent} from './start-job/start-job.component';
import {GithubStatus} from '../core/models/github-status';
import {N3Service} from '../core/services/n3.service';
import {features} from '../core/features';


@Component({
  selector: 'app-jobs',
  templateUrl: './jobs.component.html',
  styleUrls: ['./jobs.component.scss']
})
export class JobsComponent implements OnInit, OnDestroy {

  /**
   * List of jobs to pass to the job list
   * @type {Array}
   */
  jobs: Array<Job> = [];

  /**
   * Latest job used in the header display
   */
  lastJob: Job;

  /**
   * Loading indicator
   * @type {boolean}
   */
  loadingJobs = false;

  /**
   * App id of jobs to see
   */
  appId: string;

  /**
   * Refresh Interval
   */
  interval: any;

  /**
   * Indicator for data loaded and display initialized
   */
  isInitialized = false;

  /**
   * Holds repo full name
   */
  repoFullName: string;

  /**
   * Holds vcs type
   */
  vcsType: string;

  /**
   * Flag to toggle vcs type icon feature
   */
  vcsTypeIconFeature: boolean;

  /**
   * Build the component and inject services if needed
   * @param pipelines
   * @param errorHandler
   * @param route
   * @param segment
   * @param dialog
   */
  constructor(
    private pipelines: PipelinesService,
    private n3Service: N3Service,
    private errorHandler: ErrorService,
    private route: ActivatedRoute,
    private segment: SegmentService,
    private dialog: MdDialog) {
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
   * Initialize, and set up the refresh
   */
  ngOnInit() {
    this.route.params.subscribe(params => {
      if (this.interval) {
        clearInterval(this.interval);
      }
      this.appId = params['app'];
      this.interval = setInterval(() => {
        this.refresh();
      }, 10000);

      // run right away
      this.refresh();
    });

    // Track page view
    this.segment.page('JobListView');

    this.vcsTypeIconFeature = features.vcsTypeIcon;

  }

  /**
   * Clear the refresh if needed on destroy
   */
  ngOnDestroy() {
    if (this.interval) {
      clearInterval(this.interval);
    }
  }

  /**
   * Load the job list
   */
  refresh() {
    this.loadingJobs = true;
    // Get GitHub Status and VCS Info
    if (features.vcsTypeIcon) {
      this.pipelines.getGithubStatus(this.appId)
        .then((status: GithubStatus) => {
          let regex;
          let repoInfo;
          if (status.connected) {
            // Extract repo name from url eg. pineapple-pen from https://github.com/raghunat/pineapple-pen.git
            regex = /^((git@[\w\.]+:)|((http|https):\/\/[\w\.]+\/?))([\w\.@\:/\-~]+)(\.git)(\/)?$/;
            repoInfo = status.repo_url.match(regex);
            this.repoFullName = repoInfo[5] ? repoInfo[5].split('/')[1] : '';
            this.vcsType = 'git';
          } else {
            this.n3Service.getEnvironments(this.appId)
              .then(environments => {
                // Extract repo name from url eg. site from site@svn-3.hosted.acquia-sites.com:site.git
                regex = /^([^@]*)@/;
                repoInfo = environments[0].vcs.url.match(regex);
                this.repoFullName = repoInfo[1];
                this.vcsType = environments[0].vcs.type === 'git' ?  'acquia-git' : environments[0].vcs.type;
              })
              .catch(e => this.errorHandler.apiError(e));
          }
        })
        .catch(e => this.errorHandler.apiError(e));
    }

    // Get Jobs to be listed
    this.pipelines.getJobsByAppId(this.appId)
      .then(jobs => {
        // Assign the returned jobs if the initial jobs array is empty.
        // This is to avoid the reversal of the list as
        // every new order is inserted using unshift.
        if (this.jobs.length === 0) {
          this.jobs = jobs;
        } else {
          jobs.forEach(newJob => {
            // Find if the new job is an existing one
            const oldJob = this.jobs.find(job => job.job_id === newJob.job_id);
            if (oldJob) {
              Object.assign(oldJob, newJob);
            } else {
              // Append/insert the new record at the top of the list.
              this.jobs.unshift(newJob);
            }
          });
        }
      })
      .then(() => this.lastJob = this.jobs[0])
      .catch(e =>
        this.errorHandler
          .apiError(e)
          .reportError(e, 'FailedToGetJobs', {component: 'jobs', appId: this.appId}, 'error')
          .showError('Homepage', '/auth/tokens')
      )
      .then(() => {
          this.loadingJobs = false;

          // One time binding for tracking display initialization of card that
          // contains job data
          if (!this.isInitialized) {
            this.isInitialized = true;
          }
        }
      );
  }
}
