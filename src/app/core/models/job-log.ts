/**
 * Log event from the API
 */
export class JobLog {
  /**
   * Datetime String
   */
  timeline: string;

  /**
   * Level of the log event: info, error, warn, etc
   */
  level: string;

  /**
   * log event message
   */
  message: string;

  /**
   * Build the object from the provided log item.
   * @param obj
   */
  constructor(obj: any = {}) {
    Object.assign(this, obj);
  }
}
