/**
 * Holds all feature flags can use static properties or getters for enabling
 * or showing features
 */
import {environment} from '../../environments/environment';

export const features = {

  /** VCS type Icon flag.
   * @returns {boolean}
   */
  vcsTypeIcon: true,

  /**
   * Log streaming flag. Waiting on MS-2590 and related tickets
   * @returns {boolean}
   */
  logStreaming: true,

  /**
   * Direct Start flag. Waiting on MS-2623 and related tickets
   * @returns {boolean}
   */
  get directStart() {
    // switch to return true, or remove embedded flags for enabling
    return true;
  }
};
