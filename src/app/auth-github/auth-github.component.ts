import {Component, OnInit} from '@angular/core';
import {ActivatedRoute, Router, Params} from '@angular/router';
import {MdDialog, MdDialogRef} from '@angular/material';

import {PipelinesService} from '../core/services/pipelines.service';
import {ErrorService} from '../core/services/error.service';
import {SegmentService} from '../core/services/segment.service';
import {environment} from '../../environments/environment';
import {GithubDialogRepositoriesComponent} from './github-dialog-repositories/github-dialog-repositories.component';

/**
 * Alert Model
 */
export class Alert {
  display = false;
  type: string;
  message: string;
  details: string;
  showDetails = false;
}

@Component({
  selector: 'app-auth-github',
  templateUrl: './auth-github.component.html',
  styleUrls: ['./auth-github.component.scss']
})
export class AuthGithubComponent implements OnInit {

  /**
   * ApplicationID
   * @type {string}
   */
  appId: string;

  /**
   * Gihub authorized Indicator
   * @type {boolean}
   */
  authorized = false;

  /**
   * URL to redirect after Github authorization
   * @type {string}
   */
  finishUrl: string;

  /**
   * Oauth URL
   * @type {string}
   */
  oauthUrl: string;

  /**
   * N3 Endpoint URL for oauth
   * @type {string}
   */
  n3Endpoint: string;

  /**
   * Loading indicator
   */
  loading: boolean;

  /**
   * Form is submited
   * @type {Boolean}
   */
  formSubmited = false;

  /**
   * Application attached
   * @type {Boolean}
   */
  appAttached = false;

  /**
   * Alert for github connection status
   * @type {Alert}
   */
  connectionAlert: Alert;

  /**
   * Alert for attach repository status
   * @type {[type]}
   */
  attachRepoAlert: Alert;


  /**
   * Builds the component
   * @param route
   * @param router
   * @param errorHandler
   * @param pipelines
   * @param segment
   * @param dialog
   */
  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private errorHandler: ErrorService,
    private pipelines: PipelinesService,
    private segment: SegmentService,
    private dialog: MdDialog) {
  }

  /**
   * Open Dialog to choose a github directory
   */
  selectRepository() {

    if (!this.appAttached) {
      let dialogRef: MdDialogRef<GithubDialogRepositoriesComponent>;

      dialogRef = this.dialog.open(GithubDialogRepositoriesComponent);

      // pass the app id and start the repo listing
      dialogRef.componentInstance.appId = this.appId;

      dialogRef.afterClosed().subscribe(result => {
        if (result !== undefined && Object.keys(result).length !== 0) {
          this.attachRepository(result);
        }
      });
    }
  }

  /**
   * Attach repository to the current application
   * @param repository
   */
  attachRepository(repository) {
    this.loading = true;
    this.pipelines.attachGithubRepository(repository.full_name, this.appId)
      .then(() => this.segment.trackEvent('SuccessfulGithubConnection', {appId: this.appId}))
      .then((r) => {
        this.appAttached = true;
        this.displayApplication();
      })
      .catch(e => {
        this.errorHandler.apiError(e);
        this.errorHandler.reportError(e, 'AttachGithubFailed', {appId: this.appId}, 'error');
        this.showAttachRepoAlert('danger', 'Unable to attach repository to this application.', e);
      })
      .then(() => this.loading = false);
  }

  /**
   * Navigate to application page
   */
  displayApplication() {
    this.router.navigate(['application', this.appId]);
  }

  /**
   * Authenticate on github
   */
  authenticate() {
    if (!this.authorized && !this.formSubmited) {
      this.formSubmited = true;
      const form = <HTMLFormElement>document.getElementById('auth-form');
      form.submit();
    }
  }

  /**
   * Verify that user authorized by checking success query param
   * @param params
   */
  checkAuthorization(params: Params) {
    if (params['success'] === 'true') {
      this.showConnectionAlert('success', 'You are successfully connected to Github.');
      this.authorized = true;
    } else if (params['reason'] !== undefined && params['reason'] !== '') {
      this.showConnectionAlert('danger', decodeURIComponent(params['reason']));
      this.errorHandler.reportError(new Error(params['reason']), 'AuthGithubAPIFailed', {appId: this.appId}, 'error');
    } else {
      this.showConnectionAlert('danger', 'Sorry, we could not connect to github at this time.');
    }
  }

  /**
   * Show github connection status
   * @param type
   * @param message
   */
  showConnectionAlert(type: string, message: string) {
    this.connectionAlert.display = true;
    this.connectionAlert.message = message;
    this.connectionAlert.type = type;
  }

  /**
   * Show attach repository status
   * @param type
   * @param message
   * @param details
   */
  showAttachRepoAlert(type: string, message: string, details: any) {
    this.attachRepoAlert.display = true;
    this.attachRepoAlert.message = message;
    this.attachRepoAlert.type = type;
    this.attachRepoAlert.details = details.status + ':' + details._body;
  }

  /**
   * Show more details
   */
  showMoreDetails() {
    this.attachRepoAlert.showDetails = true;
  }

  /**
   * Initialize Alerts
   */
  initAlerts() {
     this.connectionAlert = new Alert();
     this.attachRepoAlert = new Alert();
  }

  /**
   * On component initialize
   */
  ngOnInit() {

    // initialize alerts
    this.initAlerts();

    this.authorized = false;
    this.formSubmited = false;
    this.appAttached = false;
    this.oauthUrl = environment.apiEndpoint + '/api/v1/ci/github/oauth';
    this.n3Endpoint = environment.headers['X-ACQUIA-PIPELINES-N3-ENDPOINT'];
    this.route.params.subscribe((params) => {
      this.appId = params['app-id'];
      this.finishUrl = environment.authRedirect + '/app/develop/applications/' + this.appId + '/pipelines/github';

      this.route.queryParams.subscribe((queryParams) => {
        if (queryParams['success'] !== undefined && queryParams['success'] !== 'undefined') {
          this.checkAuthorization(queryParams);
        }
      });
    });
  }
}
