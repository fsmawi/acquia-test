import {Component, OnInit, HostBinding} from '@angular/core';
import {ActivatedRoute, Router} from '@angular/router';
import {Location} from '@angular/common';

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

  /**
   * Flag to check if the application list can be shown
   * @type {boolean}
   */
  showApplications = false;

  /**
   * Flag to check if the first/default application to bs shown
   * @type {boolean}
   */
  showDefaultApplication = false;

  /**
   * Builds the component
   * @param amp
   * @param segmentService
   * @param liftService
   * @param router
   * @param location
   * @param bugsnag
   */
  constructor(
    private amp: AmplitudeService,
    private segmentService: SegmentService,
    private liftService: LiftService,
    public router: Router,
    public location: Location,
    private bugsnag: BugsnagService) {
  }

  /**
   * Initialize the component
   */
  ngOnInit() {
    // Check if window.self is window.top
    this.isStandalone = this.standaloneClass = (window.self === window.top);

    if (this.isStandalone) {
      this.router.events.subscribe((val) => {
        // By default do not show the default/first application, but show when route is /applications i.e., handled below
        this.showDefaultApplication = false;
        // By default show the applications list, exceptions are handled below
        this.showApplications = true;
        if (this.location.path() === '/auth/tokens'
          || this.location.path() === '/404'
          || this.location.path() === '/mock/header'
          || this.location.path().indexOf('/error') > -1) {
          this.showApplications = false;
        } else if (this.location.path() === '/applications' || this.location.path() === '/jobs') {
          this.showDefaultApplication = true;
        }
      });
    }
  }
}
