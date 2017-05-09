import {Directive, Input, HostListener, ElementRef} from '@angular/core';

import {TooltipService} from '../../core/services/tooltip.service';

@Directive({
  selector: '[appTooltip]'
})
export class TooltipDirective {

  /**
   * Tooltip text to be shown
   * @type {string}
   */
  @Input()
  appTooltip = '';

  /**
   * Position of the tooltip e.g: top, above, below, bottom, left, before, right, after
   * @type {string}
   */
  @Input()
  tooltipPosition = '';

  /**
   * Constructs the component
   * @param el
   * @param tooltipService
   */
  constructor(private el: ElementRef,
              private tooltipService: TooltipService) { }

  /**
   * Handles the mouse enter event; shows the tooltip
   */
  @HostListener('mouseenter')
  public onMouseEnter(): void {
    return this.tooltipService.show(this.appTooltip, this.tooltipPosition, this.el);
  }

  /**
   * Handles the mouse leave event; hides the tooltip
   */
  @HostListener('mouseleave')
  public onMouseLeave(): void {
    return this.tooltipService.hide();
  }
}
