import {Directive, Input, HostListener, ElementRef, Renderer} from '@angular/core';
import {Router} from '@angular/router';

import {ContextMenuService} from '../../core/services/context-menu.service';
import {MenuItem} from '../../core/models/menu-item';

// Global Scope, Window
declare const window;

@Directive({
  selector: '[appContextLink]'
})
export class ContextLinkDirective {

  /**
   * URL to be navigated to
   */
  @Input('appContextLink')
  appContextLink: MenuItem[];


  @HostListener('contextmenu', ['$event'])
  public onContextMenu(event: any): void {
    // if (window.self !== window.top) {
    if (true) {
      event.stopPropagation();
      event.preventDefault();
      this.contextMenuService.show(this.appContextLink);
    }
  }

  /**
   * Builds the directive and injects the dependencies
   * @param el
   * @param renderer
   * @param router
   * @param contextMenuService
   */
  constructor(
    private el: ElementRef,
    private renderer: Renderer,
    private router: Router,
    private contextMenuService: ContextMenuService) {
    renderer.setElementStyle(el.nativeElement, 'cursor', 'pointer');
  }

}
