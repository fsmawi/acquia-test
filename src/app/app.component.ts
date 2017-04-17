import {Component, OnInit, HostBinding} from '@angular/core';
import {AmplitudeService} from './core/services/amplitude.service';
import {LiftService} from './core/services/lift.service';
import {BugsnagService} from './core/services/bugsnag.service';
import {SegmentService} from './core/services/segment.service';

// Global Scope, Window
// or mocked by scope vars in tests
declare const window;

@Component({
  selector: 'app-root',
  templateUrl: './app.component.html',
  styleUrls: ['./app.component.scss']
})
export class AppComponent implements OnInit {

  /**
   * Flag to add standalone class
   * @type standaloneClass
   */
  @HostBinding('class.standalone') standaloneClass = false;

  /**
   * Flag to check if the application is standalone
   * @type {boolean}
   */
  isStandalone = false;

  constructor(
    private amp: AmplitudeService,
    private segmentService: SegmentService,
    private liftService: LiftService,
    private bugsnag: BugsnagService) {
  }

  ngOnInit() {
    // Check if window.self is window.top
    this.isStandalone = this.standaloneClass = (window.self === window.top);
  }
}
