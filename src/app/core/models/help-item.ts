/**
 * HelpItem Model
 */
export class HelpItem {

  /**
   * id for the item, used for instrumentation
   * @type {Boolean}
   */
  id: string;

  /**
   * Help item type (DOCUMENT, VIDEO)
   * @type {string}
   */
  type: string;

  /**
   * Help item category (PERSONALISED or GENERAL)
   * @type {string}
   */
  category: string;

  /**
   * External link to redirect
   * @type {string}
   */
  externalLink: string;

  /**
   * Title of the help item
   * @type {string}
   */
  title: string;

  /**
   * Description of the help item
   * @type {string}
   */
  description: string;


  /**
   * Creates a HelpItem object
   * @param obj {any} Base object to use
   */
  constructor(obj: any = {}) {
    Object.assign(this, obj);
  }
}
