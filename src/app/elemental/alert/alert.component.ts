import {Component, OnInit, Input} from '@angular/core';

@Component({
  selector: 'e-alert',
  templateUrl: './alert.component.html',
  styleUrls: ['./alert.component.scss']
})
export class AlertComponent implements OnInit {

  /**
   * Use Icon
   * @type {Boolean}
   */
  @Input()
  icon = true;


  /**
   * Alert type
   * @type {String}
   */
  @Input()
  type = 'success';

  /**
   * Icon type
   * @type {string}
   */
  iconType: string;

  /**
   * Builds the component
   */
  constructor() {
  }

  /**
   * Initiate Component
   */
  ngOnInit() {
    switch (this.type) {
      case 'warning':
        this.iconType = 'alert';
        break;
      case 'danger':
        this.iconType = 'alert--circle';
        break;
      default:
        this.iconType = this.type;
        break;
    }
  }
}
