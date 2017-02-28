import {Component, OnInit} from '@angular/core';
import {LocalStorageService} from '../core/services/local-storage.service';
import {PipelinesService} from '../core/services/pipelines.service';
import {ActivatedRoute, Router} from '@angular/router';
import {Pipeline} from '../core/models/pipeline';
import {ErrorService} from '../core/services/error.service';
import {FlashMessageService} from '../core/services/flash-message.service';

@Component({
  selector: 'app-landing-page',
  templateUrl: './landing-page.component.html',
  styleUrls: ['./landing-page.component.scss']
})
export class LandingPageComponent implements OnInit {

  /**
   * The app id to use if specified
   */
  appId: string;

  /**
   * Interval holder for checking status of the supplied application
   */
  check: any;

  /**
   * Background loading indicator
   */
  loading: boolean;

  /**
   * Array of feature items to list TODO: includes image attributes for possible future visuals
   * @type {object}
   */
  features: Array<{index: number, name: string, img: string, alt: string, text: string}> = [
    {
      index: 0,
      name: 'deploy',
      img: '/assets/landing-images/viewing.jpg',
      alt: 'Deploy your code in Acquia Cloud.',
      text: 'Manage your application\'s source code on GitHub, or other third-party Git servers, ' +
      'and seamlessly deploy to Acquia Cloud.'
    },
    {
      index: 1,
      name: 'prepare',
      img: '/assets/landing-images/viewing.jpg',
      alt: 'Prepare your code in Acquia Cloud.',
      text: 'Use tools like Composer or Drush Make to assemble your application\'s components and ' +
      'dependencies automatically.'
    },
    {
      index: 2,
      name: 'compile',
      img: '/assets/landing-images/viewing.jpg',
      alt: 'Compile your code in Acquia Cloud.',
      text: 'Use technologies like Sass and Typescript to compile application source code.'
    },
    {
      index: 3,
      name: 'control',
      img: '/assets/landing-images/viewing.jpg',
      alt: 'Control access to your code in Acquia Cloud.',
      text: 'Control which developers or teams have access to change different parts of your ' +
      'application code base.'
    }
  ];

  /**
   * View flag to determine to show the list or not on load
   * @type {boolean}
   */
  firstTime = true;

  /**
   * Flag to see if the app has pipelines enabled
   */
  isEnabled: boolean;

  /**
   * Flag to see if the app has a github repo connected
   */
  isConnected: boolean;

  /**
   * Build the component
   * @param localStorage
   * @param pipelines
   * @param router
   * @param route
   * @param errorHandler
   * @param flash
   */
  constructor(public localStorage: LocalStorageService,
              private pipelines: PipelinesService,
              private router: Router,
              private route: ActivatedRoute,
              private errorHandler: ErrorService,
              private flash: FlashMessageService) {
  }

  /**
   * Initial steps, grabs the app id and checks the app status
   */
  ngOnInit() {

    // get the appId if specified
    this.appId = this.route.snapshot.params['app-id'];

    // check if they have seen it already
    if (this.localStorage.get('landing-intro')) {
      this.firstTime = false;
      this.end();
    } else {
      this.firstTime = true;
    }

    // Checks if the user is enabled and connected
    // NOTE: This makes the calls in the background on component load.
    // This way, while they read the text, the component finds out what state they are in.
    // That way when they click "Go" it will just go, instead of waiting.
    // If they click go right away, or have already watched the intro text,
    // it just shows the loading indicator while it figures out where to send them
    this.checkSetupStatus();
  }

  /**
   * Checks the apps status with pipelines
   */
  checkSetupStatus() {
    this.loading = true;

    if (!this.appId) {
      // TODO logic to redirect when no app id is specified: Standalone feature
      this.router.navigateByUrl('/404');
    } else {

      // Use the pipelines API to determine
      return this.pipelines.getPipelineByAppId(this.appId)
        .then((pipelines: Pipeline[]) => {
          // NOTE: logic to say whether or not connected, is by github connection status
          this.isEnabled = true; // if no error, they have the app enabled for pipelines
          pipelines.forEach(pipeline => {
            if (!this.isConnected) {
              this.isConnected = pipeline.repo_data && pipeline.repo_data.repos.find(r => r.type === 'github');
            }
          });
        })
        .catch(e => {
          // If pipelines is not enabled for that app, it returns 403 with not enabled
          if (e.status === 403) {
            this.isEnabled = false;
          } else {
            this.errorHandler.apiError(e);
            this.errorHandler.reportError(e, 'CheckAppStatus', {module: 'landing-page'}, 'Error');
            this.flash.showError('Error checking your app', e);
          }
        });
    }
  }

  /**
   * Completes
   */
  end() {
    // set flag to not show again
    this.firstTime = false;
    this.localStorage.set('landing-intro', 'viewed');

    // Wait for original calls to be done if needed
    this.check = setInterval(() => this.checkUpdate(), 1500);
    this.checkUpdate(); // initial call
  }

  /**
   * Checks for updates in status polling
   */
  checkUpdate() {
    if (this.isConnected !== undefined || this.isEnabled !== undefined) {
      clearInterval(this.check);
      this.loading = false;

      // Based on status, navigate to the right page
      if (this.isConnected) {
        this.router.navigateByUrl('/jobs/' + this.appId || '');
      } else if (this.isEnabled) {
        this.router.navigateByUrl('/auth/github/' + this.appId || '');
      } else {
        this.router.navigateByUrl('/upsell');
      }
    }
  }
}
