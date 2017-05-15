import {Component, OnInit, HostListener, ElementRef, HostBinding, OnChanges} from '@angular/core';
import {trigger, state, style, transition, animate} from '@angular/animations';

import {TooltipService} from '../../services/tooltip.service';
import {animations} from '../../animations';

@Component({
  selector: 'app-tooltip',
  templateUrl: './tooltip.component.html',
  styleUrls: ['./tooltip.component.scss'],
  animations: animations
})
export class TooltipComponent implements OnInit {

  /**
   * Tooltip text to be shown
   * @type {string}
   */
  tooltip = '';

  /**
   * Flag to check if the tooltip to be shown
   * @type {boolean}
   */
  showTooltip = false;

  /**
   * Position of the tooltip (CSS)
   * @type {{top: number; left: number; transform-origin: string}}
   */
  tooltipPosition = {top: 0, left: 0, 'transform-origin': 'top'};

  /**
   * Element for which the tooltip is shown
   */
  element: ElementRef;

  /**
   * Position of the tooltip e.g: top, above, below, bottom, left, before, right, after
   * @type {string}
   */
  position = 'below';

  /**
   * Constructs the component
   * @param tooltipService
   * @param toolTipContainer
   */
  constructor(public tooltipService: TooltipService, public toolTipContainer: ElementRef) {
    this.tooltipService.show = this.show.bind(this);
    this.tooltipService.hide = this.hide.bind(this);
  }

  /**
   * Builds the component
   */
  ngOnInit() {
  }

  /**
   * Shows the tooltip
   * @param text
   * @param position
   * @param el
   */
  show(text = '', position = 'below', el: ElementRef) {
    if (text !== '') {
      this.tooltip = text;
      this.showTooltip = true;
      this.element = el;
      this.position = position;

      // To ensure that Angular (change detection) runs before setting position
      // required : wait until the *ngIf element is shown/rendered
      setTimeout(() => {
        this.setPosition();
      }, 0);
    }
  }

  /**
   * Hides the tooltip
   */
  hide() {
    this.showTooltip = false;
  }

  /**
   * Updates the tooltip position on window resize
   */
  @HostListener('window:resize')
  onWindowResize(): void {
    // update position based on `ref`
    if (this.showTooltip) {
      this.setPosition();
    }
  }

  /**
   * Updates the tooltip position on window scroll
   */
  @HostListener('window:scroll')
  onWindowScroll(): void {
    // update position based on `ref`
    if (this.showTooltip) {
      this.setPosition();
    }
  }


  /**
   * Sets the tooltip position
   */
  setPosition() {
    // defaults to tooltip position: bottom
    let transformOrigin = 'top';
    // get the element bounds for which tooltip to be shown
    const elementBounds = this.element.nativeElement.getBoundingClientRect();
    let xPos = elementBounds.left;
    let yPos = elementBounds.top;
    // get the tooltip text element; required for width/height calculations
    const tooltipTextElement = this.toolTipContainer.nativeElement.querySelector('.tooltip');

    // calculate the x,y positions of the tooltip to be shown
    switch (this.position) {
      case 'right':
      case 'after':
        xPos += (elementBounds.width + 16);
        yPos += ((elementBounds.height / 2) - (tooltipTextElement.offsetHeight / 2));
        transformOrigin = 'left';
        break;
      case 'left':
      case 'before':
        xPos -= (tooltipTextElement.offsetWidth + 16);
        yPos += ((elementBounds.height / 2) - (tooltipTextElement.offsetHeight / 2));
        transformOrigin = 'right';
        break;
      case 'above':
      case 'top':
        yPos -= (tooltipTextElement.offsetHeight + 16);
        xPos += ((elementBounds.width / 2) - (tooltipTextElement.offsetWidth / 2));
        transformOrigin = 'bottom';
        break;
      case 'below':
      case 'bottom':
        yPos += elementBounds.height + 16;
        xPos += ((elementBounds.width / 2) - (tooltipTextElement.offsetWidth / 2));
        transformOrigin = 'top';
        break;
    }

    this.tooltipPosition = { top: yPos, left: xPos, 'transform-origin': transformOrigin};
  }
}
