/**
 * Alert Model
 */
export class Alert {

  /**
   * Display alert
   * @type {Boolean}
   */
  display = false;

  /**
   * Alert type (success, danger, info, warning)
   * @type {string}
   */
  type: string;

  /**
   * Alert message
   * @type {string}
   */
  message: string;

  /**
   * Creates a Repository object
   * @param obj {any} Base object to use
   */
  constructor(obj: any = {}) {
    Object.assign(this, obj);
  }
}
