import {Component, OnInit, trigger, transition, style, animate} from '@angular/core';
import {MdDialogRef, MdDialog} from '@angular/material';
import {ConfirmationModalService} from '../../services/confirmation-modal.service';
import {EventEmitter} from '@angular/core';

@Component({
  selector: 'app-confirmation-modal',
  templateUrl: './confirmation-modal.component.html',
  styleUrls: ['./confirmation-modal.component.scss']
})
export class ConfirmationModalComponent implements OnInit {
  /**
   * Title of the confirmation dialog
   */
  title: String;
  /**
   * Confirmation message to be shown
   */
  message: String;
  /**
   * Primary action text eg. Yes, Proceed
   */
  primaryActionText: String;
  /**
   * Secondary action text eg: No, Cancel
   */
  secondaryActionText: String;
  /**
   * Event emitter to emit user response
   */
  emitter: EventEmitter<boolean>;
  /**
   * flag to show/close the modal
   */
  modalOpen: boolean;

  /**
   * Builds the component and injects the dependencies
   * @param confirmationModalService
   */
  constructor(private confirmationModalService: ConfirmationModalService) {
      this.confirmationModalService.show = this.show.bind(this);
  }

  /**
   * Component initialize
   */
  ngOnInit() {
  }

  /**
   * Show confirmation modal
   * @param type
   * @param text
   * @param primaryActionText
   * @param secondaryActionText
   */
  show(title: string, message: string, primaryActionText: string, secondaryActionText: string) {

    this.title = title;
    this.message = message;
    this.primaryActionText = primaryActionText;
    this.secondaryActionText = secondaryActionText;
    this.emitter = new EventEmitter<boolean>();
    this.modalOpen = true;

    setTimeout(() => {
      const d = document.getElementById('confirmation-modal');
      d.style.opacity = '1';
    }, 10);

    return this.emitter;
  }

  /**
   * Primary Action
   */
  confirmed() {
    this.emitter.emit(true);
    this.modalOpen = false;
  }

  /**
   * Secondary Action
   */
  cancelled() {
    this.emitter.emit(false);
    this.modalOpen = false;
  }

}
