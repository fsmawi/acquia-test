import {Injectable, EventEmitter} from '@angular/core';

@Injectable()
export class ConfirmationModalService {
  /**
   * Show confirmation modal
   * @param type
   * @param text
   * @param primaryActionText
   * @param secondaryActionText
   */
  show: (title: string, message: string, primaryActionText: string, secondaryActionText: string) => EventEmitter<boolean>;

  /**
   * Initiate the service
   */
  constructor() { }

  /**
   * Opens the dialog with the given strings
   * @param title
   * @param message
   * @param primaryActionText
   * @param secondaryActionText
   * @returns {Promise<T>}
   */
  openDialog(title: string, message: string, primaryActionText: string, secondaryActionText: string) {
    return new Promise(resolve => {
      this.show(title, message, primaryActionText, secondaryActionText)
        .subscribe(result => resolve(result));
    });
  }
}
