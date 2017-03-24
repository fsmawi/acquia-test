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
  _appId: string;

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
    return this.pipelines.getApplicationInfo(this._appId)
      .then((data) => {
        BaseApplication.info = data;
        return Promise.resolve(BaseApplication.info);
      });
  }
}
