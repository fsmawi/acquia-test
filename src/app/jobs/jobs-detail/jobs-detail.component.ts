import {Component, OnInit, OnDestroy} from '@angular/core';
import {ActivatedRoute, Params} from '@angular/router';
import {Job} from '../../core/models/job';
import {PipelinesService} from '../../core/services/pipelines.service';
import {ErrorService} from '../../core/services/error.service';
import {JobLog} from '../../core/models/job-log';

@Component({
  selector: 'app-jobs-detail',
  templateUrl: './jobs-detail.component.html',
  styleUrls: ['./jobs-detail.component.scss']
})
export class JobsDetailComponent implements OnInit, OnDestroy {

  /**
   * The current job
   */
  job: Job;

  /**
   * The desired Job ID from the route
   */
  jobId: string;

  /**
   * The list of logs from a job
   */
  logs: Array<JobLog>;

  /**
   * Interval Timer for Refresh
   */
  timer: any;

  /**
   * Loading Indicator
   * @type {boolean}
   */
  loadingJob = false;

  /**
   * Builds the component
   * @param pipelineService
   * @param route
   * @param errorHandler
   */
  constructor(private pipelineService: PipelinesService,
              private route: ActivatedRoute,
              private errorHandler: ErrorService) {
  }

  /**
   * On component initialize, start the refresh interval
   */
  ngOnInit() {
    this.route.params.subscribe(
      (params: Params) => {
        this.jobId = params['id'];
        this.refresh();
      }
    );
    this.timer = setInterval(() => this.refresh.call(this), 2000);
  }

  /**
   * When navigating or destroying the component, stop the refresh interval
   */
  ngOnDestroy() {
    clearTimeout(this.timer);
  }

  /**
   * Load the job and available logs
   */
  refresh() {
    this.loadingJob = true;
    let job;
    this.pipelineService.getJobByJobId(this.jobId)
      .then((j: Job) => job = new Job(j))
      .then(() => this.pipelineService.getLogFile(this.jobId))
      .then((logs: Array<JobLog>) => this.logs = logs)
      .then(() => this.job = job)
      .catch(e => this.errorHandler.apiError(e))
      .then(() => this.loadingJob = false);
  }
}
