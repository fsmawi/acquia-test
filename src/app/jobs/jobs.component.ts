import {Component, OnInit, OnDestroy} from '@angular/core';
import {PipelinesService} from '../core/services/pipelines.service';
import {ErrorService} from '../core/services/error.service';
import {Job} from '../core/models/job';
import {ActivatedRoute} from '@angular/router';

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
   * Build the component and inject services if needed
   * @param pipelines
   * @param errorHandler
   * @param route
   */
  constructor(private pipelines: PipelinesService, private errorHandler: ErrorService, private route: ActivatedRoute) {
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
    this.pipelines.getJobsByAppId(this.appId)
      .then(jobs => this.jobs = jobs)
      .then(() => this.lastJob = this.jobs[0])
      .catch(e => this.errorHandler.apiError(e))
      .then(() => this.loadingJobs = false);
  }
}
