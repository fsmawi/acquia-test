import {Job} from './job';
import {Environment} from './environment';

/**
 * Application Model
 */
export class Application {
  /**
   * Repository URL
   */
  repo_url: string;

  /**
   * Repository Name
   */
  repo_name: string;

  /**
   * Repository type
   * @type {string}
   */
  repo_type: string;

  /**
   * UUID of the application
   */
  uuid: string;

  /**
   * Name of the application
   */
  name: string;

  /**
   * Latest job of the application
   */
  latest_job: Job;

  /**
   * Repository branches
   * @type {string[]}
   */
  branches: string[];

  /**
   * Environment used to sync Dbs.
   */
  db_sync_source_env: string;

  /**
   * Environment list.
   * @type {Environment[]}
   */
  environments: Environment[];

  /**
   * Creates a Repository object
   * @param obj {any} Base object to use
   */
  constructor(obj: any = {}) {
    if (obj.latest_job) {
      obj.latest_job = new Job(obj.latest_job);
    }
    Object.assign(this, obj);
  }
}
