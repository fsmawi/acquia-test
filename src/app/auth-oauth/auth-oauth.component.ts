import {Component, OnInit} from '@angular/core';
import {ActivatedRoute, Router, Params} from '@angular/router';
import {MdDialog, MdDialogRef} from '@angular/material';

import {Alert} from '../core/models/alert';
import {BaseApplication} from '../core/classes/base-application';
import {PipelinesService} from '../core/services/pipelines.service';
import {ErrorService} from '../core/services/error.service';
import {OauthDialogRepositoriesComponent} from './oauth-dialog-repositories/oauth-dialog-repositories.component';
import {SegmentService} from '../core/services/segment.service';
import {environment} from '../../environments/environment';
import {repoType} from '../core/repository-types';
import {Repository} from '../core/models/repository';
import {FlashMessageService} from '../core/services/flash-message.service';

@Component({
  selector: 'app-auth-oauth',
  templateUrl: './auth-oauth.component.html',
  styleUrls: ['./auth-oauth.component.scss']
})
export class AuthOauthComponent extends BaseApplication implements OnInit {

  /**
   * Repository type
   * @type {string}
   */
  repoType: string;

  /**
   * Repository type Label
   * @type {string}
   */
  typeLabel: string;

  /**
   * ApplicationID
   * @type {string}
   */
  appId: string;

  /**
   * Oauth authorized Indicator
   * @type {boolean}
   */
  authorized = false;

  /**
   * URL to redirect after Oauth authorization
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
   * Alert for oauth connection status
   * @type {Alert}
   */
  connectionAlert = new Alert();

  /**
   * Alert for attach repository status
   * @type {[type]}
   */
  attachRepoAlert = new Alert();

  /**
   * Holds repo full name of the repo
   */
  repoFullName: string;

  /**
   * Holds current Repository type
   */
  currentRepoType: string;

  /**
   * Holds current Repository type Label
   * @type {string}
   */
  currentTypeLabel: string;

  /**
   * Builds the component
   * @param route
   * @param router
   * @param errorHandler
   * @param pipelines
   * @param flashMessage
   * @param segment
   * @param dialog
   */
  constructor(
    private route: ActivatedRoute,
    private router: Router,
    protected errorHandler: ErrorService,
    protected pipelines: PipelinesService,
    protected flashMessage: FlashMessageService,
    private segment: SegmentService,
    private dialog: MdDialog) {
    super(flashMessage, errorHandler, pipelines);
  }

  /**
   * Open Dialog to choose a oauth directory
   */
  selectRepository() {

    if (!this.appAttached) {
      let dialogRef: MdDialogRef<OauthDialogRepositoriesComponent>;

      dialogRef = this.dialog.open(OauthDialogRepositoriesComponent);

      // pass the app id and repoType
      dialogRef.componentInstance.appId = this.appId;
      dialogRef.componentInstance.repoType = this.repoType;

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
  attachRepository(repository: Repository) {
    this.loading = true;
    this.pipelines.attachOauthGitRepository(repository.full_name, this.appId, this.repoType)
      .then(() => this.segment.trackEvent(`Successful${this.typeLabel}Connection`, {appId: this.appId}))
      .then((r) => {
        this.appAttached = true;
        this.displayApplication();
      })
      .catch(e => {
        this.errorHandler.apiError(e)
          .reportError(e, `FailedToAttach${this.typeLabel}ReposioryToPipelines`,
            {component: `auth-${this.repoType}`, repository: repository.full_name, appId: this.appId}, 'error');
        this.showAttachRepoAlert('danger', e.status + ' : ' + e._body);
      })
      .then(() => this.loading = false);
  }

  /**
   * Navigate to application page
   */
  displayApplication() {
    this.router.navigateByUrl(`/applications/${this.appId}/info`);
  }

  /**
   * Authenticate
   */
  authenticate() {
    if (!this.authorized && !this.formSubmited) {
      if (this.currentRepoType !== 'acquia-git') {
        this.loading = true;
        this.pipelines.removeOauthGitAuth(this.repoFullName, this.appId, this.currentRepoType)
          .catch(e => {
            this.errorHandler.apiError(e)
              .reportError(e, `FailedRemove${this.currentTypeLabel}Auth`, {component: 'auth-acquia', appId: this.appId}, 'error');
            this.showConnectionAlert('danger', e.status + ' : ' + e._body);
          })
          .then(() => {
            this.submitOauth();
            this.loading = false;
          });
      } else {
        this.submitOauth();
      }
    }
  }

  /**
   * Submit oauth form
   */
  submitOauth () {
    this.formSubmited = true;
    const form = <HTMLFormElement>document.getElementById('auth-form');
    form.submit();
  }

  /**
   * Verify that user authorized by checking success query param
   * @param params
   */
  checkAuthorization(params: Params) {
    if (params['success'] === 'true') {
      this.showConnectionAlert('success', `You are successfully connected to ${this.typeLabel}.`);
      this.authorized = true;
    } else if (params['reason'] !== undefined && params['reason'] !== '') {
      this.showConnectionAlert('danger', decodeURIComponent(params['reason']));
      this.errorHandler.reportError(new Error(params['reason']), `FailedToCheck${this.typeLabel}Authorization`,
        {component: `auth-${this.repoType}`, appId: this.appId}, 'error');
    } else {
      this.showConnectionAlert('danger', `Sorry, we could not connect to ${this.typeLabel} at this time.`);
    }
  }

  /**
   * Show repoType connection status
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
    this.n3Endpoint = environment.headers['X-ACQUIA-PIPELINES-N3-ENDPOINT'];
    this.n3ApiFile = environment.headers['X-ACQUIA-PIPELINES-N3-APIFILE'];
    this.route.params.subscribe((params) => {
      this.appId = params['app-id'];
      BaseApplication._appId = params['app-id'];
      this.repoType = params['repo-type'];
      this.typeLabel = repoType[this.repoType].name;
      this.oauthUrl = environment.apiEndpoint + `/api/v1/ci/oauth/${this.repoType}`;

      if (!environment.standalone) {
        // store appId in session storage
        sessionStorage.setItem('pipelines.standalone.application.id', this.appId);

        // redirect to cloud site
        this.finishUrl = environment.authCloudRedirect + `/app/develop/applications/${this.appId}/pipelines/${this.repoType}`;
      } else {
        this.finishUrl = environment.URL + `/auth/oauth/${this.repoType}/${this.appId}`;
      }

      this.route.queryParams.subscribe((queryParams) => {
        if (queryParams['success'] !== undefined && queryParams['success'] !== 'undefined') {
          this.checkAuthorization(queryParams);
        }
      });

      // Get Repo Full Name
      this.getInfo().then(info => {
        this.repoFullName = info.repo_name;
        this.currentRepoType = info.repo_type;
        this.currentTypeLabel = repoType[this.currentRepoType].name;
      }).catch(e => this.errorHandler.apiError(e));
    });
  }
}
