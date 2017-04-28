import {Directive, Input, ElementRef, AfterContentInit, Injectable} from '@angular/core';
import {EventManager} from '@angular/platform-browser';

import {LiftService} from '../../core/services/lift.service';
import {SegmentService} from '../../core/services/segment.service';

@Directive({
  selector: '[appTrackOn]'
})
export class TrackDirective implements AfterContentInit {

  /**
   * Identifies the event to be tracked eg. click, swipe
   */
  @Input()
  appTrackOn: string;

  /**
   * Event Name eg. JobList-Details, JobList-StopJob
   */
  @Input()
  trackEventName: string;

  /**
   * Data to be collected for the event
   */
  @Input()
  trackEventData: any;

  /**
   * Reference to the native element
   */
  private element: any;

  /**
   * Builds the directive and injects the dependencies
   * @param elementRef
   * @param liftService
   * @param segmentService
   * @param eventManager
   */
  constructor(private elementRef: ElementRef,
    private liftService: LiftService,
    private segmentService: SegmentService,
    private eventManager: EventManager) {

    this.element = elementRef.nativeElement;
  }

  /**
   * Binds the lift capture and segment track to the element's event
   */
  ngAfterContentInit() {
    this.eventManager.addEventListener(this.element, this.appTrackOn || 'click', (event: any) => {
      this.liftService.captureEvent(this.trackEventName, this.trackEventData);
      this.segmentService.trackEvent(this.trackEventName, this.trackEventData);
    });
  }
}
