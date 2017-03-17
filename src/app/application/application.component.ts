import {Component, OnInit} from '@angular/core';
import {ActivatedRoute, Router} from '@angular/router';

import {PipelinesService} from '../core/services/pipelines.service';
import {ErrorService} from '../core/services/error.service';
import {FlashMessageService} from '../core/services/flash-message.service';
import {GithubStatus} from '../core/models/github-status';

@Component({
  selector: 'app-application',
  templateUrl: './application.component.html',
  styleUrls: ['./application.component.scss']
})
export class ApplicationComponent implements OnInit {

  /**
   * Application ID
   * @type {string}
   */
  appId: string;

  /**
   * Git URL
   * @type {string}
   */
  gitUrl: string;

  /**
   * Git clone
   * @type {string}
   */
  gitClone: string;

  /**
   * Git Add
   * @type {string}
   */
  gitAdd = 'git add .';

  /**
   * Git commit
   * @type {string}
   */
  gitCommit = 'git commit -m "[commit message]"';

  /**
   * Git push
   * @type {string}
   */
  gitPush = 'git push origin [branch]';

  /**
   * Loading Indicator
   * @type {Boolean}
   */
  appLoading = false;

  /**
   * Build the component
   * @param route
   * @param router
   * @param pipelines
   * @param errorHandler
   * @param flashMessage
   */
  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private pipelines: PipelinesService,
    private errorHandler: ErrorService,
    private flashMessage: FlashMessageService) {
  }

  /**
   * Get Configuration Information
   */
  getConfigurationInfo() {
    this.pipelines.getGithubStatus(this.appId)
      .then((status: GithubStatus) => {
        if (!status.connected) {
          this.flashMessage.showInfo('You are not connected yet');
        } else {
          this.gitUrl = status.repo_url;
          this.gitClone = `git clone --branch [branch] ${this.gitUrl} [destination]`;
        }
      })
      .catch(e => {
        this.errorHandler.apiError(e);
        this.errorHandler.reportError(e, 'FailedToGetGithubStatus', {component: 'application', appId: this.appId}, 'error');
        this.flashMessage.showError('Unable to get your pipeline information for this application at this time.', e);
      })
      .then(() => this.appLoading = false);
  }

  /**
   *  Initialize, and get pipeline information
   */
  ngOnInit() {
    this.appLoading = true;
    this.route.params.subscribe((params) => {
      this.appId = params['app-id'];
      this.getConfigurationInfo();
    });
  }
}
