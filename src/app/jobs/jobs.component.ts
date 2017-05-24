import {ActivatedRoute, Router} from '@angular/router';
import {Component, OnInit, OnDestroy} from '@angular/core';
import {MdDialog, MdDialogRef} from '@angular/material';

import {Subject} from 'rxjs/Subject';
import {Observable} from 'rxjs/Rx';
import 'rxjs/add/operator/map';
import 'rxjs/add/operator/debounceTime';

import {ErrorService} from '../core/services/error.service';
import {Job} from '../core/models/job';
import {PipelinesService} from '../core/services/pipelines.service';
import {SegmentService} from '../core/services/segment.service';
import {StartJobComponent} from './start-job/start-job.component';
import {BaseApplication} from '../core/classes/base-application';
import {animations} from '../core/animations';
import {environment} from '../../environments/environment';

@Component({
  selector: 'app-jobs',
  templateUrl: './jobs.component.html',
  styleUrls: ['./jobs.component.scss'],
  animations: animations
})
export class JobsComponent extends BaseApplication implements OnInit, OnDestroy {

  /**
   * List of jobs to pass to the job list
   * @type {Array}
   */
  jobs: Array<Job> = [];

  /**
   * List of jobs filtered from the shown jobs
   */
  filteredJobs: Array<Job> = [];

  /**
   * Holds the filter text (input)
   */
  filterText = '';

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
   * Holds repo full name of the app
   */
  repoFullName: string;

  /**
   * Holds vcs type
   */
  vcsType: string;

  /**
   * Holds the subject, used to debounce
   */
  filterSubject = new Subject<string>();

  /**
   * Flag to check if the pipelines enabled for the app
   * @type {boolean}
   */
  pipelinesEnabled = true;

  /**
   * Flag to check if the Show More button can be shown;
   * do not display when the returned jobs array length is less the default jobs count
   * @type {boolean}
   */
  showMoreJobsLink = false;

  /**
   * Flag to check if the loading indicator for show more jobs can be shown
   * Enable when the API call is started i.e., show more jobs is clicked
   * @type {boolean}
   */
  showMoreJobsLoading = false;

  /**
   * Number of pages
   * @type {number}
   */
  page = 1;


  /**
   * Build the component and inject services if needed
   * @param pipelines
   * @param errorHandler
   * @param router
   * @param route
   */
  constructor(
    protected pipelines: PipelinesService,
    protected errorHandler: ErrorService,
    private router: Router,
    private route: ActivatedRoute) {
    super(errorHandler, pipelines);
  }

  /**
   * Initialize, and set up the refresh
   */
  ngOnInit() {
    this.route.params.subscribe(params => {
      this.loadingJobs = true;
      if (this.interval) {
        this.interval.unsubscribe();
      }
      this.appId = params['app'];

      // Catch the root route loading in standalone
      if (!this.appId) {
        return; // the router will trigger the route change with the right parameter
      }

      this.isInitialized = false;
      this.jobs = [];

      // store appId in session storage
      if (!environment.standalone) {
        sessionStorage.setItem('pipelines.standalone.application.id', this.appId);
      }

      this.interval = Observable.timer(1, 10000).subscribe(() => this.getJobs());

      // run right away
      this.getJobs();

      // avoid doing extra api calls when we are in the same application context
      let forceGetInfo = false;
      if (BaseApplication._appId !== params['app']) {
        forceGetInfo = true;
        BaseApplication._appId = params['app'];
      }

      this.getInfo(forceGetInfo).then(info => {
        this.repoFullName = info.repo_name;
        this.vcsType = info.repo_type;
      }).catch(e => this.errorHandler.apiError(e));
    });

    // Setup filter subscription
    this.filterSubject
      .debounceTime(400)
      .distinctUntilChanged()
      .subscribe(filterText => {
        this.filterText = filterText;
        this.filter();
      });
  }

  /**
   * Clear the refresh if needed on destroy
   */
  ngOnDestroy() {
    if (this.interval) {
      this.interval.unsubscribe();
    }
  }

  /**
   * Clears the filter text applied
   */
  clearFilter() {
    this.filterText = '';
    this.filter();
  }

  /**
   * Filter the jobs with the text entered
   */
  filter() {
    if (this.filterText === '' || !this.filterText) {
      this.filteredJobs = this.jobs;
    } else {
      this.filteredJobs = this.jobs ? this.jobs.filter(job => {
          const filterTextLowerCase = this.filterText.toLowerCase();
          const trigger = this.getTriggerTypeFromFilterText(filterTextLowerCase);
          const status = this.getStatusFromFilterText(filterTextLowerCase);
          let filterByStatusMatch = false;
          let filterByTriggerMatch = false;
          let filterByPRNameMatch = false;
          // Construct the PR #Nmuber string if PR Number is available
          const pullRequest = (job.metadata && job.metadata.pull_request) ?
            `pr ${job.metadata.pull_request.toLowerCase()}` : '';

          // Case 1 : Check if a pull request matches with the text shown i.e, PR #Number
          if (job.isPullRequest) {
            if (pullRequest !== '' && pullRequest.indexOf(filterTextLowerCase) > -1) {
              filterByPRNameMatch = true;
            }
          }

          // Case 2 : Check if the user is searching for 'success' or 'failed' jobs
          if (status !== '') {
            if (((status === 'success' && job.isSucceeded) || (status === 'failure' && job.isFailed))) {
              filterByStatusMatch = true;
            }
          }

          // Case 3 : Check if the user is searching for any trigger type
          if (trigger !== '') {
            if (job.trigger && job.trigger.toLowerCase().indexOf(trigger) > -1) {
              filterByTriggerMatch = true;
            }
          }

          // Case 1 : Check if the input matches with any branch name or if a pull request check if it matches with
          //          the text shown i.e, PR #Number
          // Case 2 : Check if the user is searching for 'success' or 'failed' jobs
          // Case 3 : Check if the user is searching for any trigger type
          return ((job.branch.toLowerCase().indexOf(filterTextLowerCase) > -1 || filterByPRNameMatch) || // Case 1
          (job.status.toLowerCase().indexOf(filterTextLowerCase) > -1 || filterByStatusMatch) || // Case 2
          filterByTriggerMatch); // Case 3
        }) : [];
    }
  }

  /**
   * Get the trigger type by the input text
   * @param filterText
   * @returns {any}
   */
  getTriggerTypeFromFilterText(filterText) {
    switch (filterText) {
      case 'pr':
      case 'pull request':
      case 'pull':
        return 'pull_request';
      case 'branch':
      case 'push':
        return 'push';
      case 'manual':
        return 'manual';
      default:
        return '';
    }
  }

  /**
   * Get the status by the input text
   * @param filterText
   * @returns {any}
   */
  getStatusFromFilterText(filterText) {
    switch (filterText) {
      case 'pass':
      case 'passed':
      case 'success':
      case 'succeeded':
        return 'success';
      case 'fail':
      case 'failed':
      case 'error':
      case 'errored':
        return 'failure';
      default:
        return '';
    }
  }

  /**
   * Loads the job list
   * @param page
   */
  getJobs(page = 1) {
    this.loadingJobs = true;
    const appId = this.appId;
    const jobsCount = 25;
    const params = { page: page };
    // Get Jobs to be listed
    this.pipelines.getJobsByAppId(appId)
      .then(jobs => {
        // catch changes to the router, and prevent a slow request from repopulating the view:
        if (appId !== this.appId) {
          return;
        }

        // One time binding for tracking display initialization of card that
        // contains job data
        if (!this.isInitialized) {
          this.isInitialized = true;
        }

        // Display 'Show more' link if the returned jobs length is greater than or equal to jobsCount
        if (jobs.length >= jobsCount && page === this.page) {
          this.showMoreJobsLink = true;
        } else {
          this.showMoreJobsLink = false;
        }

        this.pipelinesEnabled = true;
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
              if (page === 1) {
                // Append/insert the new record at the top of the list.
                this.jobs.unshift(newJob);
              } else {
                // if clicked on show more, append the jobs
                this.jobs.push(newJob);
              }
            }
          });
        }
      })
      .then(() => this.lastJob = this.jobs[0])
      .catch(e => {
        if (e.status === 403 && e._body &&
          e._body.includes(`Error authorizing request: site doesn't have pipelines enabled`)) {
          // this flag is required to avoid the flicker
          // else no-jobs component shows before the pipelines-not-enabled-component
          this.pipelinesEnabled = false;
          this.router.navigate(['disabled', this.appId]);
        } else {
          this.errorHandler
            .apiError(e)
            .reportError(e, 'FailedToGetJobs', {component: 'jobs', appId: this.appId}, 'error')
            .showError('Homepage', '/');
        }
      })
      .then(() => {
          this.showMoreJobsLoading = false;
          this.loadingJobs = false;
          this.filter();
        }
      );
  }

  /**
   * Load more jobs; get the next page of jobs
   */
  showMoreJobs() {
    this.showMoreJobsLoading = true;
    this.page++;
    this.getJobs(this.page);
  }
}
