import {Component, OnInit} from '@angular/core';
import {FlashMessageService} from '../../services/flash-message.service';

@Component({
  selector: 'app-flash-message',
  templateUrl: './flash-message.component.html',
  styleUrls: ['./flash-message.component.scss']
})
export class FlashMessageComponent implements OnInit {

  /**
   * Message
   * @type {String}
   */
  message = '';

  /**
   * Type
   * @type {String}
   */
  type = '';

  /**
   * Icon
   * @type {String}
   */
  icon = '';

  /**
   * Timer
   * @type {Number}
   */
  timer = 10000;

  /**
   * Details
   * @type {String}
   */
  details = '';

  /**
   * falsh opened
   * @type {Boolean}
   */
  flashOpen = false;

  /**
   * details opened
   * @type {Boolean}
   */
  detailsOpen = false;

  /**
   * Builds the component
   * @param flashMessageService
   */
  constructor(private flashMessageService: FlashMessageService) {
    this.flashMessageService.show = this.show.bind(this);
  }

  /**
   * Initiate Component
   */
  ngOnInit() {
  }

  /**
   * Show flash message
   * @param type
   * @param text
   * @param details
   */
  show(type: string, text: string, details: any = undefined) {

    this.type = type;
    this.icon = type === 'error' ? 'alert--circle' : type;
    this.message = text;
    this.flashOpen = true;
    this.details = details;

    // handle annimation
    setTimeout(() => {
      const d = document.getElementById('flash-message');
      d.className += ' show';
      setTimeout(() => {
        this.closeFlash();
      }, this.timer);
    }, 10);
  }

  /**
   * Open dialog to show more details
   */
  moreDetails() {
    this.flashOpen = false;
    this.detailsOpen = true;
  }

  /**
   * Close flash message
   */
  closeFlash() {
    this.flashOpen = false;
  }

  /**
   * Close details dialog
   */
  closeDetails() {
    this.detailsOpen = false;
  }

  /**
   * Initiate flash message attributes
   */
  initMessage() {
    this.message = this.type = this.details = '';
  }

  /**
   * Is the flash message is shown
   */
  isFlashOpen() {
    return this.flashOpen;
  }

  /**
   * si the details dialog is opened
   */
  isDetailsOpen() {
    return this.detailsOpen;
  }
}
