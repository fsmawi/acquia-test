import {Component, OnInit, OnDestroy, Input} from '@angular/core';
import {Router} from '@angular/router';

import {Observable} from 'rxjs/Rx';

import {PipelinesService} from '../../services/pipelines.service';
import {Application} from '../../models/application';
import {ErrorService} from '../../services/error.service';

@Component({
  selector: 'app-applications-list',
  templateUrl: './applications-list.component.html',
  styleUrls: ['./applications-list.component.scss']
})
export class ApplicationsListComponent implements OnInit, OnDestroy {

  /**
   * Hold the list of applications
   * @type {Array}
   */
  applications: Array<Application> = [];

  /**
   * Flag to check the if the applications are being loaded
   * @type {boolean}
   */
  loadingApplications = false;

  /**
   * Flag to check if the component has been initialized
   * @type {boolean}
   */
  isInitialized = false;

  /**
   * Hold the interval observable
   */
  interval: any;

  /**
   * Flag to show the default application in case of /applications route
   * @type {boolean}
   */
  @Input()
  showDefaultApplication = false;

  /**
   * Builds the component
   * @param piplelinesService
   * @param router
   * @param errorHandler
   */
  constructor(
    public piplelinesService: PipelinesService,
    public router: Router,
    public errorHandler: ErrorService) {
  }

  /**
   * Initialize the component
   */
  ngOnInit() {
    if (this.interval) {
      this.interval.unsubscribe();
    }
    this.interval = Observable.timer(1, 20000).subscribe(() => this.getApplications());
  }

  /**
   * Clear the refresh if needed on destroy
   */
  ngOnDestroy() {
    if (this.interval) {
      this.interval.unsubscribe();
    }
  }

  /**
   * Refresh the component with the latest available list of applications
   */
  getApplications() {
    this.loadingApplications = true;
    return this.piplelinesService.getApplications()
      .then(applications => {
        // Using for instead of forEach to have the capability of breaking the loop
        for (let i = 0; i < applications.length; i++) {
          const newApplication = applications[i];
          const oldApplication = this.applications.find(app => app.uuid === newApplication.uuid);
          if (oldApplication) {
            // If the application exists, assign the new properties available
            Object.assign(oldApplication, newApplication);
          } else {
            // If there is a new application in the list replace the old list with the new sorted applications
            // And break the loop
            const sortedAlphabetically = applications.sort((a, b) => a.name.localeCompare(b.name));
            this.applications = sortedAlphabetically.filter(app => app.latest_job)
              .concat(sortedAlphabetically.filter(app => !app.latest_job));
            break;
          }
        }
        return this.applications;
      })
      .then(applications => {
        // If default application has to be shown redirect to the first application's job list
        if (this.showDefaultApplication) {
          this.router.navigate(['applications', applications[0].uuid]);
        }
      })
      .catch(e =>
        this.errorHandler
          .apiError(e)
          .reportError(e, 'FailedToGetApplications', {component: 'applications'}, 'error')
      )
      .then(() => {
        this.loadingApplications = false;
        this.isInitialized = true;
      });
  }
}
