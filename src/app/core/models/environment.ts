/**
 * Environment Model
 */
export class Environment {

  /**
   * Environment ID.
   * @type {string}
   */
  id: string;

  /**
   * Environment label.
   * @type {string}
   */
  label: string;

  /**
   * Creates a Environment object
   * @param obj {any} Base object to use
   */
  constructor(obj: any = {}) {
    Object.assign(this, obj);
  }
}
