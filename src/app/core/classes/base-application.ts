import {PipelinesService} from '../services/pipelines.service';
import {ErrorService} from '../services/error.service';
import {Application} from '../models/application';
import {FlashMessageService} from '../services/flash-message.service';

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
   * Build the base component
   * @param flashMessage
   * @param errorHandler
   * @param pipelines
   */
  constructor(
    protected flashmessage: FlashMessageService,
    protected errorHandler: ErrorService,
    protected pipelines: PipelinesService) {
  }

  /**
   * Get application information
   * @param force
   */
  getInfo(force = false) {
    // use force parameter to force the refresh execution and get the last application info
    if (!BaseApplication.info || force) {
      return this.refresh();
    } else {
      return Promise.resolve(BaseApplication.info);
    }
  }

  /**
   * Get the static BaseApplication.info
   * @returns {Application}
   */
  get staticInfo() {
    return BaseApplication.info;
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
