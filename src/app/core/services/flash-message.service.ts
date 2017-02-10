import { Injectable } from '@angular/core';

@Injectable()
export class FlashMessageService {

  /**
   * Show flash message
   * @param type
   * @param text
   * @param details
   */
  show: (type: string, text: string, details: any) => void;

  /**
   * Initiate the service
   */
  constructor() { }

  /**
   * Show an info flash message
   * @param text
   * @param details
   */
  showInfo(text: string, details: any = undefined) {
    this.show('info', text, details);
  }

  /**
   * Show a success flash message
   * @param text
   * @param details
   */
  showSuccess(text: string, details: any = undefined) {
    this.show('success', text, details);
  }

  /**
   * Show an error flash message
   * @param text
   * @param details
   */
  showError(text: string, details: any = undefined) {
    this.show('error', text, details);
  }

  /**
   * Show a warning flash message
   * @param text
   * @param details
   */
  showWarning(text: string, details: any = undefined) {
    this.show('warning', text, details);
  }
}
