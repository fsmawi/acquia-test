import {Component, OnInit, OnDestroy, ElementRef, ViewChild} from '@angular/core';
import {ActivatedRoute, Params} from '@angular/router';

import {Observable, Subscription} from 'rxjs/Rx';

import {Job} from '../../core/models/job';
import {PipelinesService} from '../../core/services/pipelines.service';
import {ErrorService} from '../../core/services/error.service';
import {JobLog} from '../../core/models/job-log';
import {AnsiService} from '../../core/services/ansi.service';
import {SegmentService} from '../../core/services/segment.service';
import {FlashMessageService} from '../../core/services/flash-message.service';
import {WebSocketService} from '../../core/services/web-socket.service';
import {WebSocketHandler} from '../../core/models/web-socket-handler';
import {features} from '../../core/features';
import {animations} from '../../core/animations';
import {BaseApplication} from '../../core/classes/base-application';
import {environment} from '../../../environments/environment';

@Component({
  selector: 'app-jobs-detail',
  templateUrl: './jobs-detail.component.html',
  styleUrls: ['./jobs-detail.component.scss'],
  animations: animations
})
export class JobsDetailComponent extends BaseApplication implements OnInit, OnDestroy {

  /**
   * The current job
   */
  job: Job;

  /**
   * The App ID of the current job from the route
   */
  appId: string;

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
   * Subscription for the timer
   */
  sub: Subscription;

  /**
   * Loading Indicator
   * @type {boolean}
   */
  loadingJob = false;

  /**
   * Loading Indicator
   * @type {boolean}
   */
  loadingLogs = false;

  /**
   * Indicates if the job needed a refreshJob before it was in a terminal state
   * @type {boolean}
   */
  firstLoad = true;

  /**
   * Streaming indicator
   */
  streaming: boolean = null;

  /**
   * Holds the event stream
   */
  socket: WebSocketHandler;

  /**
   * Holds repo full name of the repo
   */
  repoFullName = 'Job list';

  /**
   * Hold the reference for the logs div
   */
  @ViewChild('logsContainer')
  logsElement: ElementRef;

  /**
   * Builds the component
   * @param pipelineService
   * @param route
   * @param ansiService
   * @param errorHandler
   * @param segment
   * @param flash
   * @param webSocketService
   */
  constructor(
    protected pipelineService: PipelinesService,
    private route: ActivatedRoute,
    private ansiService: AnsiService,
    protected errorHandler: ErrorService,
    private segment: SegmentService,
    private flash: FlashMessageService,
    private webSocketService: WebSocketService) {
    super(flash, errorHandler, pipelineService);
  }

  /**
   * On component initialize, start the refreshJob interval
   */
  ngOnInit() {
    this.route.params.subscribe(
      (params: Params) => {
        this.appId = params['app'];
        this.jobId = params['id'];

        // avoid doing extra api calls when we are in the same application context
        let forceGetInfo = false;
        if (BaseApplication._appId !== params['app']) {
          forceGetInfo = true;
          BaseApplication._appId = params['app'];
        }
        // Get Repo Full Name
        this.getInfo(forceGetInfo).then(info => {
          this.repoFullName = info.repo_name;
        }).catch(e => this.errorHandler.apiError(e));

        // reset the page state
        this.streaming = null;
        this.loadingLogs = false;
        this.loadingJob = false;
        this.logs = null;
        this.job = null;

        // store appId in session storage
        if (!environment.standalone) {
          sessionStorage.setItem('pipelines.standalone.application.id', this.appId);
        }

        // clear refreshJob if needed
        if (this.timer && this.sub) {
          this.sub.unsubscribe();
        }

        // set up refreshJob interval
        this.timer = Observable.timer(1, 5000);
        this.sub = this.timer.subscribe(() => this.refreshJob.call(this));

        // initial refreshJob
        this.refreshJob();
      }
    );

    // Track page view
    this.segment.page('JobDetailView');
  }

  /**
   * When navigating or destroying the component, stop the refreshJob interval
   */
  ngOnDestroy() {
    if (this.timer) {
      this.sub.unsubscribe();
      this.timer = null;
    }
  }

  /**
   * Load the job and available logs by polling or switch to streaming if available
   */
  refreshJob() {
    if (this.loadingJob) {
      return; // Already refreshJobing
    }
    this.loadingJob = true;
    this.pipelineService.getJobByJobId(this.appId, this.jobId)
      .then((j: Job) => this.job = new Job(j))
      .then(() => {
        this.loadingLogs = true;
        // if job is in terminal state, get logs
        // if job is in progress, see if sockets are available to stream
        // if job is in progress, and no socket is available, perform traditional polling
        const metadata = this.job.metadata;
        if (this.job.isFinished) {
          // alert the user that the job is completed if not on load
          if (!this.firstLoad) {
            this.flash.showInfo('The job has finished');
          }

          // if never streamed, get logs
          if (this.streaming === null) {
            return this.getJobLogs();
          } else {
            // no need to load any more logs, we streamed them all
            this.loadingLogs = false;
          }
          // always set to false to flag non streaming components
          this.streaming = false;
          // FEATURE FLAG for enabling log streaming. Remove after MS-2590 is complete
        } else if (this.streaming === null && metadata.log_stream_url && metadata.log_stream_secret && features.logStreaming) {
          this.loadingLogs = false;
          return this.streamLogs();
        } else if (!this.timer) {
          // If there is a timer, do nothing, if not, try again in 5
          setTimeout(() => this.refreshJob(), 5000);
        }
      })
      .then(() => {
        // if streaming or complete, stop polling
        if (this.streaming || this.job.isFinished && this.timer) {
          this.sub.unsubscribe();
          this.timer = null;
        }
      })
      .catch(e =>
        this.errorHandler
          .apiError(e)
          .reportError(e, 'FailedToGetJobDetail', {
            component: 'jobs-detail',
            appId: this.appId,
            jobId: this.jobId
          }, 'error')
          .showError('Job list', '/applications/' + this.appId))
      .then(() => {
        this.loadingJob = false;
        this.firstLoad = false;
      });
  }

  /**
   * Gets the full log file event
   * @returns {Promise}
   */
  getJobLogs() {
    return this.pipelineService.getLogFile(this.appId, this.jobId)
      .then((logs: Array<JobLog>) => {
        this.logs = logs.map(log => {
          // Converting the ansi values in the log message to valid HTML values
          log.message = this.ansiService.convert(log.message);
          return log;
        });

        this.loadingLogs = false;
      })
      .catch(e =>
        this.errorHandler.reportError(e, 'FailedToGetJobLogs', {
          component: 'jobs-detail',
          appId: this.appId,
          jobId: this.jobId
        }, 'error')
      );
  }

  /**
   * Streams the logs from a web socket
   * @returns {Promise}
   */
  streamLogs() {
    this.socket = this.webSocketService.connect(this.job.metadata.log_stream_url);
    // if no socket support, revert to long poll
    if (!this.socket) {
      this.streaming = false;
      return Promise.resolve(); // Polling is already running
    }

    // connect, auth, append logs, and close
    this.streaming = true;
    this.logs = [];
    this.socket.subscribe(event => {
      switch (event.name) {

        // auth to the web socket after connecting
        case 'connected':
          this.socket.send({
            cmd: 'authenticate',
            secret: this.job.metadata.log_stream_secret
          });
          break;

        // when available items comes back, enable the logs
        case 'available':
          this.socket.send({
            cmd: 'enable',
            type: event.argument.type,
            from: 'start'
          });
          break;

        // On each line item append
        case 'line':
          let text;
          if (event.argument.raw_text_base64) {
            // HACK: The browser doesn't like base64->utf8, so the following is necessary
            text = decodeURIComponent(
              Array.prototype.map.call(
                atob(event.argument.raw_text_base64), function (c) {
                  return '%' + ('00' + c.charCodeAt(0).toString(16)
                    ).slice(-2);
                }).join('')
            );
          } else {
            text = event.argument.text;
          }
          text += '\n';
          this.logs.push(new JobLog({
            timestamp: event.unix_time,
            level: 'info',
            message: this.ansiService.convert(text)
          }));
          break;

        // When the socket closes and is done, refreshJob the job info
        case 'close':
          this.streaming = null;
          this.refreshJob();
          break;

        // If an error occurs, report, and revert to long polling
        case 'error':
          this.streaming = false;
          this.flash.showInfo(
            'Unable to stream logs, will show the logs when available', event.argument
          );
          break;

        // Unknown event, log for debugging
        default:
          return console.log('Unknown stream event', event);
      }
    });
  }

  /**
   * Toggle log chunk visibility
   */
  showChunk(chunk: any) {
    chunk.visible = !chunk.visible;
  }

  /**
   * Scroll the logs pre section to the latest logs available
   */
  scrollLogsToBottom() {
    this.logsElement.nativeElement.scrollIntoView(false);
  }
}
