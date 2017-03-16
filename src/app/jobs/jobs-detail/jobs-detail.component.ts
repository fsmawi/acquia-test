import {Component, OnInit, OnDestroy, ElementRef, ViewChild} from '@angular/core';
import {ActivatedRoute, Params} from '@angular/router';

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
   * Indicates if the job needed a refresh before it was in a terminal state
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
    private pipelineService: PipelinesService,
    private route: ActivatedRoute,
    private ansiService: AnsiService,
    private errorHandler: ErrorService,
    private segment: SegmentService,
    private flash: FlashMessageService,
    private webSocketService: WebSocketService) {
  }

  /**
   * On component initialize, start the refresh interval
   */
  ngOnInit() {
    this.route.params.subscribe(
      (params: Params) => {
        this.appId = params['app'];
        this.jobId = params['id'];

        // clear refresh if needed
        if (this.timer) {
          clearInterval(this.timer);
        }

        // set up refresh interval
        this.timer = setInterval(() => {
          this.refresh.call(this);
        }, 5000);

        // initial refresh
        this.refresh();
      }
    );

    // Track page view
    this.segment.page('JobDetailView');
  }

  /**
   * When navigating or destroying the component, stop the refresh interval
   */
  ngOnDestroy() {
    if (this.timer) {
      clearInterval(this.timer);
    }
  }

  /**
   * Load the job and available logs by polling or switch to streaming if available
   */
  refresh() {
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
        } else if (this.streaming === null && metadata.log_stream_websocket && metadata.log_stream_secret && features.logStreaming) {
          this.loadingLogs = false;
          return this.streamLogs();
        } else if (!this.timer) {
          // If there is a timer, do nothing, if not, try again in 5
          setTimeout(() => this.refresh(), 5000);
        }
      })
      .then(() => {
        // if streaming or complete, stop polling
        if (this.streaming || this.job.isFinished && this.timer) {
          clearInterval(this.timer);
          this.timer = null;
        }
      })
      .catch(e =>
        this.errorHandler
          .apiError(e)
          .showError('Job list', '/jobs/' + this.appId))
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
      });
  }

  /**
   * Streams the logs from a web socket
   * @returns {Promise}
   */
  streamLogs() {
    this.socket = this.webSocketService.connect(this.job.metadata.log_stream_websocket);
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
          // list available items
          this.socket.send({
            cmd: 'list-available'
          });
          break;

        // when available items comes back, enable the logs
        case 'list-available':
          event.argument.items.map(i => this.socket.send({
            cmd: 'enable',
            type: i.type,
            from: 'start'
          }));
          break;

        // On each line item append
        case 'line':
          const lineObj = event.argument;
          this.logs.push(new JobLog({
            timestamp: lineObj.unix_time,
            level: 'info',
            message: this.ansiService.convert(lineObj.text)
          }));
          break;

        // When the socket closes and is done, refresh the job info
        case 'close':
          this.streaming = false;
          this.refresh();
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
   * Scroll the logs pre section to the latest logs available
   */
  scrollLogsToBottom() {
    this.logsElement.nativeElement.scrollIntoView(false);
  }
}
