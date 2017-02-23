/**
 * Pipeline model
 */
export class Pipeline {

  /**
   * Pipeline ID
   * @type {string}
   */
  pipeline_id: string;

  /**
   * Site name
   * @type {string}
   */
  sitename: string;

  /**
   * Pipeline name
   * @type {string}
   */
  name: string;

  /**
   * Last job ID
   * @type {string}
   */
  last_job_id: string;

  /**
   * Last branch
   * @type {string}
   */
  last_branch: string;

  /**
   * Last requested
   * @type {string}
   */
  last_requested: string;

  /**
   * Last finished
   * @type {string}
   */
  last_finished: string;

  /**
   * Last status
   * @type {string}
   */
  last_status: string;

  /**
   * Last duration
   * @type {string}
   */
  last_duration: string;

  /**
   * Applications ID
   * @type {string[]}
   */
  applications: string[];

  /**
   * Repository data
   * @type {any}
   */
  repo_data: any;

  /**
   * Create a Pipeline object
   * @param obj
   */
  constructor(obj: any = {}) {
    Object.assign(this, obj);
  }
}
