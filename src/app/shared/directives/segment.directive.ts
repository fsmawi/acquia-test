import {Directive, Input, AfterContentInit, ElementRef, Injectable} from '@angular/core';
import {EventManager} from '@angular/platform-browser';
import {SegmentService} from '../../core/services/segment.service';

@Directive({
  selector: '[appSegmentOn]'
})
export class SegmentDirective implements AfterContentInit {

  /**
   * Identifies the event to be tracked eg. click, swipe
   */
  @Input()
  appSegmentOn: string;

  /**
   * Event identifier eg. JobList-Details, JobList-StopJob
   */
  @Input()
  segmentEventIdentifier: string;

  /**
   * Data to be collected for the event
   */
  @Input()
  segmentEventData: any;

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
              private segmentService: SegmentService,
              private eventManager: EventManager) {

    this.element = elementRef.nativeElement;
  }

  /**
   * Binds the analytics to the element's event
   */
  ngAfterContentInit() {
    this.eventManager.addEventListener(this.element, this.appSegmentOn || 'click', (event: any) => this.trackEvent(event));
  }

  /**
   * Tracks the event, calls the SegmentService.trackEvent
   * @param event
   */
  trackEvent(event: Event) {
    this.segmentService.trackEvent(this.segmentEventIdentifier, this.segmentEventData);
  }

}
