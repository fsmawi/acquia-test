import {Directive, Input, HostListener, ElementRef, Renderer} from '@angular/core';
import {Router} from '@angular/router';


@Directive({
  selector: '[appIframeLink]',
})
export class IframeLinkDirective {

  /**
   * URL to be navigated to
   */
  @Input('appIframeLink')
  appIframeLink: string;

  /**
   * Prevent click events and navigate to the provided URL
   * @param event
   */
  @HostListener('click', ['$event'])
  public onClick(event: any): void {

    if (event.target.outerHTML.indexOf('app-context-link') == -1) {
      event.stopPropagation();
    }

    if (this.appIframeLink) {
      this.router.navigateByUrl(this.appIframeLink);
    }
  }

  /**
   * uilds the directive and injects the dependencies
   * @param el
   * @param renderer
   * @param router
   */
  constructor(
    private el: ElementRef,
    private renderer: Renderer,
    private router: Router) {
    renderer.setElementStyle(el.nativeElement, 'cursor', 'pointer');
  }
}
