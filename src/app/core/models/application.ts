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
   * Creates a Repository object
   * @param obj {any} Base object to use
   */
  constructor(obj: any = {}) {
    Object.assign(this, obj);
  }
}
