import {Injectable, ElementRef} from '@angular/core';

@Injectable()
export class TooltipService {

  /**
   * Shows the tooltips
   * @param text
   * @param position
   * @param el
   */
  show: (text: string, position: string, el: ElementRef) => void;

  /**
   * Hides the tooltip
   */
  hide: () => void;

  /**
   * Constructs the Service
   */
  constructor() {}
}
