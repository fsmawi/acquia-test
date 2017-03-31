import {Directive, Input, ElementRef, AfterContentInit} from '@angular/core';
import {LiftService} from '../../core/services/lift.service';
import {EventManager} from '@angular/platform-browser';

@Directive({
  selector: '[appLiftOn]'
})
export class LiftDirective implements AfterContentInit {

  /**
   * Identifies the event to be tracked eg. click, swipe
   */
  @Input()
  appLiftOn: string;

  /**
   * Event Name eg. JobList-Details, JobList-StopJob
   */
  @Input()
  liftEventName: string;

  /**
   * Data to be collected for the event
   */
  @Input()
  liftEventData: any;

  /**
   * Reference to the native element
   */
  private element: any;

  /**
   * Builds the directive and injects the dependencies
   * @param elementRef
   * @param segmentService
   * @param eventManager
   */
  constructor(private elementRef: ElementRef,
              private liftService: LiftService,
              private eventManager: EventManager) {

    this.element = elementRef.nativeElement;
  }

  /**
   * Binds the lift capture to the element's event
   */
  ngAfterContentInit() {
    this.eventManager.addEventListener(this.element, this.appLiftOn || 'click', (event: any) => this.trackEvent(event));
  }

  /**
   * Captures the event, calls the LiftService.trackEvent
   * @param event
   */
  trackEvent(event: Event) {
    this.liftService.captureEvent(this.liftEventName, this.liftEventData);
  }
}
