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
      text: 'Manage your application\'s source code using non-Acquia Git services (currently ' +
      'GitHub and Bitbucket), which allows seamless deployment to Acquia Cloud.'
    },
    {
      index: 1,
      name: 'prepare',
      img: '/assets/landing-images/viewing.jpg',
      alt: 'Prepare your code in Acquia Cloud.',
      text: 'Leverage build tools (such as Composer or Drush Make) to assemble your application\'s ' +
      'components and dependencies.'
    },
    {
      index: 2,
      name: 'compile',
      img: '/assets/landing-images/viewing.jpg',
      alt: 'Compile your code in Acquia Cloud.',
      text: 'Compile application assets (minified and concatenated files), which can resolve problems ' +
      'normally associated with such files being included in an application\'s version control system.'
    },
    {
      index: 3,
      name: 'control',
      img: '/assets/landing-images/viewing.jpg',
      alt: 'Control access to your code in Acquia Cloud.',
      text: 'Utilize Teams and Permissions, separating development concerns by pulling in work ' +
      'completed by non-team members as a dependency.'
      /**
       * @todo MS-2575
       *
       * Add "Learn More" link that points to product docs that clarify the above feature statement.
       * "For instance: another team is responsible for developing your Drupal site’s theme;
       * that work can be included as part of the Pipelines configuration which removes the need to
       * add those developers to your Cloud Subscription’s team."
       */
    }
  ];

  /**
   * Array of requirements to list
   * @type {object}
   */
  requirements: Array<{index: number, name: string, alt: string, text: string}> = [
    {
      index: 0,
      name: 'existing',
      alt: 'Should be an existing Acquia cloud customer.',
      text: 'You should be an existing Acquia cloud customer.'
    },
    {
      index: 1,
      name: 'agreement',
      alt: 'You need to have agreed to a beta agreement.',
      text: 'You need to have agreed to a beta agreement by signing a standard Acquia MSA or ' +
      'have signed an agreement directly.'
    },
    {
      index: 2,
      name: 'zendesk',
      alt: 'You need to have requested access to the beta program by filing a Zendesk ticket.',
      text: 'You need to have requested access to the beta program by filing a Zendesk ticket ' +
      'with the subject line: Pipelines Beta Access.'
    }
  ];

  /**
   * Array of know more items with links to resources
   * @type {object}
   */
  knowMore: Array<{index: number, name: string, alt: string, text: string,
                   resources: [{title: string, url: string}]}> = [
    {
      index: 0,
      name: 'documentation',
      alt: 'Documentation',
      text: 'Documentation',
      resources: [{title : 'Pipelines - Docs', url : 'https://docs.acquia.com/pipelines'}]
    },
    {
      index: 1,
      name: 'tutorials',
      alt: 'Tutorials',
      text: 'Tutorials',
      resources: [{ title: 'Training' , url: 'https://training.acquia.com'}]
    },
    {
      index: 2,
      name: 'videos',
      alt: 'Videos',
      text: 'Videos',
      resources: [
        { title : 'Interview with Barry Jaspan part 1', url: 'https://player.vimeo.com/video/176784983'},
        { title : 'Interview with Barry Jaspan part 2', url: 'https://player.vimeo.com/video/176784984'},
        { title : 'Pipelines Technical Demo', url: 'https://player.vimeo.com/video/176784984'},
        { title : 'Pipelines 101 A', url: 'https://player.vimeo.com/video/184398691'},
        { title : 'Pipelines 101 B', url: 'https://player.vimeo.com/video/184398694'},
        { title : 'Pipelines 201', url: 'https://player.vimeo.com/video/184398693'},
        { title : 'Pipelines 301', url: 'https://player.vimeo.com/video/184398695'},
        { title : 'Pipelines 401', url: 'https://player.vimeo.com/video/184398697'},
        { title : 'Pipelines 501', url: 'https://player.vimeo.com/video/184399322'},
        { title : 'Pipelines 601', url: 'https://player.vimeo.com/video/184398701'}
      ]
    }
  ];

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

    if (!this.appId) {
      // TODO logic to redirect when no app id is specified: Standalone feature
      this.router.navigateByUrl('/404');
    }
  }

  /**
   * Navigate to the corresponding page
   */
  go() {
    this.loading = true;
    this.pipelines.getPipelineByAppId(this.appId)
        .then((pipelines: Pipeline[]) => {
          // NOTE: logic to say whether or not connected, is by github connection status
          this.isEnabled = true; // if no error, they have the app enabled for pipelines
          pipelines.forEach(pipeline => {
            if (!this.isConnected) {
              this.isConnected = pipeline.repo_data && pipeline.repo_data.repos.find(r => r.type === 'github');
            }
          });
        })
        .then(() => {
          if (this.isConnected !== undefined || this.isEnabled !== undefined) {
            // Based on status, navigate to the right page
            if (this.isConnected) {
              this.router.navigateByUrl('/jobs/' + this.appId || '');
            } else if (this.isEnabled) {
              this.router.navigateByUrl('/auth/github/' + this.appId || '');
            }
          }
        })
        .catch(e => {
          // If pipelines is not enabled for that app, it returns 403 with not enabled
          if (e.status === 403) {
            this.isEnabled = false;
            window.top.location.href = 'https://cloud.acquia.com/app/profile/agreements';
          } else {
            this.errorHandler.apiError(e);
            this.errorHandler.reportError(e, 'CheckAppStatus', {component: 'landing-page', appId: this.appId}, 'error');
            this.flash.showError('Error checking your app', e);
          }
        });
  }
}
