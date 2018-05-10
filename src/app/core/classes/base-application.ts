import {PipelinesService} from '../services/pipelines.service';
import {ErrorService} from '../services/error.service';
import {Application} from '../models/application';
import {FlashMessageService} from '../services/flash-message.service';
import {ConfirmationModalService} from '../services/confirmation-modal.service';

export class BaseApplication {

  /**
   * application information
   * @type {Object}
   */
  static info: Application;

  /**
   * Application ID
   * @type {string}
   */
  static _appId: string;

  /**
   * Flag indicating if N3 creds popup shown
   * @type {boolean}
   */
  static n3PopupShown = false;

  /**
   * Flag indicating if N3 creds are attached
   * @type {boolean}
   */
  static n3CredentialsAttached = false;

  /**
   * Build the base component
   * @param flashMessage
   * @param errorHandler
   * @param pipelines
   * @param confirmationModal
   */
  constructor(
    protected flashmessage: FlashMessageService,
    protected errorHandler: ErrorService,
    protected pipelines: PipelinesService,
    protected confirmationModal: ConfirmationModalService) {
  }

  /**
   * Get application information
   * @param force
   */
  getInfo(force = false) {
    // Get Token Info
    if (!BaseApplication.n3PopupShown) {
      BaseApplication.n3PopupShown = true;
      this.pipelines.getN3TokenInfo(BaseApplication._appId)
        .then(res => {
          const isValidTokenAttached = res.token_attached && res.is_token_valid;
          BaseApplication.n3CredentialsAttached = isValidTokenAttached;
          if (!isValidTokenAttached) {
            if (!res.can_execute_pipelines) {
              this.flashmessage.showError('You do not have permissions to execute Pipelines. Please contact ' +
                'your system administrator.');
            } else {
              this.showN3CredentialsPopup();
            }
          }
        });
    }

    // use force parameter to force the refresh execution and get the last application info
    if (!BaseApplication.info || force) {
      return this.refresh();
    } else {
      return Promise.resolve(BaseApplication.info);
    }
  }

  /**
   * Shows the N3 credentials confirmation dialog
   */
  showN3CredentialsPopup() {
    return this.confirmationModal
      .openDialog('API Token', 'Pipelines requires a valid API Token to be associated with the application ' +
        'in order to have all features enabled. Without an API Token associated, Pipelines will not be able ' +
        'to start jobs triggered through webhooks nor will it be able to deploy build results to CDEs. ' +
        'Pipelines can automatically create an API Token linked with your user account and associate it to ' +
        'the application. This will mean that all webhook triggered jobs and CDE related jobs will appear ' +
        'in the UI and logs as having been started by your user account. <br> <br>' +
        'Do you agree to allow Pipelines to create an API Token linked with your user account and ' +
        'associate it with the application?', 'Yes', 'No')
      .then(result => {
        if (result) {
          this.setN3Credentials();
        } else {
          BaseApplication.n3CredentialsAttached = false;
          this.flashmessage.showError('By choosing to not create an API Token linked with your user account and associate it with ' +
            'your application, you will not be able to run automated jobs triggered through webhooks or deploy ' +
            'build results to CDEs.');
        }
      });
  }

  /**
   * Sets the N3 Credentials to the application
   * @returns {Promise}
   */
  setN3Credentials() {
    return this.pipelines.setN3Credentials(BaseApplication._appId)
      .then((res) => {
        BaseApplication.n3CredentialsAttached = true;
        this.flashmessage.showSuccess('An API Token was created and linked with your user account and associated ' +
          'successfully with the application. All Pipelines features have been enabled.');
        this.refresh();
      })
      .catch(e => {
        this.flashmessage.showError('The creation of an API Token or associating the API Token with the ' +
          'application has failed. Due to this failure, some functionality has not been enabled. ' +
          'Please contact support.');
        this.errorHandler.apiError(e)
          .reportError(e, 'FailedToAttachN3Credentials', {component: 'job-list', appId: BaseApplication._appId}, 'error');
      });
  }

  /**
   * Get the static BaseApplication.info
   * @returns {Application}
   */
  get staticInfo() {
    return BaseApplication.info;
  }

  /**
   * Get the static BaseApplication.n3CredentialsAttached
   * @returns {boolean}
   */
  get staticN3CredentialsAttached() {
    return BaseApplication.n3CredentialsAttached;
  }

  /**
   * Refresh application information
   */
  refresh() {
    return this.pipelines.getApplicationInfo(BaseApplication._appId)
      .then((data) => {
        BaseApplication.info = data;
        return Promise.resolve(BaseApplication.info);
      })
      .catch(e => {
        this.errorHandler.apiError(e);
        if (this.errorHandler.isForbiddenPipelinesError()) {
          this.flashmessage.showError(`You are unauthorized to execute pipelines.
            Some of the functionality might not work as expected. Reach out to your manager or Acquia to request access.`);
        }
        return Promise.reject(e);
      });
  }
}
