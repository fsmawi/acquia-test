import {Component, OnInit} from '@angular/core';
import {ActivatedRoute, Router, Params} from '@angular/router';
import {MdDialog, MdDialogRef} from '@angular/material';

import {Alert} from '../core/models/alert';
import {PipelinesService} from '../core/services/pipelines.service';
import {ErrorService} from '../core/services/error.service';
import {SegmentService} from '../core/services/segment.service';
import {environment} from '../../environments/environment';
import {GithubDialogRepositoriesComponent} from './github-dialog-repositories/github-dialog-repositories.component';

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
   * N3 Api File
   * @type {string}
   */
  n3ApiFile: string;

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
  connectionAlert = new Alert();

  /**
   * Alert for attach repository status
   * @type {[type]}
   */
  attachRepoAlert = new Alert();


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
          // closed with API error
          if (result.status !== undefined) {
            this.showAttachRepoAlert('danger', result.status + ' : ' + result._body);
          } else {
            this.attachRepository(result);
          }
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
        this.errorHandler.apiError(e)
          .reportError(e, 'FailedToAttachGithubReposioryToPipelines',
          {component: 'auth-github', repository: repository.full_name, appId: this.appId}, 'error');
        this.showAttachRepoAlert('danger', e.status + ' : ' + e._body);
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
      this.errorHandler.reportError(new Error(params['reason']), 'FailedToCheckGithubAuthorization',
        {component: 'auth-github', appId: this.appId}, 'error');
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
   */
  showAttachRepoAlert(type: string, message: string) {
    this.attachRepoAlert.display = true;
    this.attachRepoAlert.message = message;
    this.attachRepoAlert.type = type;
  }

  /**
   * On component initialize
   */
  ngOnInit() {
    this.authorized = false;
    this.formSubmited = false;
    this.appAttached = false;
    this.oauthUrl = environment.apiEndpoint + '/api/v1/ci/github/oauth';
    this.n3Endpoint = environment.headers['X-ACQUIA-PIPELINES-N3-ENDPOINT'];
    this.n3ApiFile = environment.headers['X-ACQUIA-PIPELINES-N3-APIFILE'];
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
