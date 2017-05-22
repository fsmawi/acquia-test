import {PipelinesService} from '../services/pipelines.service';
import {ErrorService} from '../services/error.service';
import {Application} from '../models/application';

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
   * @param  errorHandler
   * @param  pipelines
   */
  constructor(
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
   * Refresh application information
   */
  refresh() {
    return this.pipelines.getApplicationInfo(BaseApplication._appId)
      .then((data) => {
        BaseApplication.info = data;
        return Promise.resolve(BaseApplication.info);
      });
  }
}
