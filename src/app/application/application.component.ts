import { Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { PipelinesService } from '../core/services/pipelines.service';
import { ErrorService } from '../core/services/error.service';
import { FlashMessageService } from '../core/services/flash-message.service';

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
   * Build the components
   * @param route
   * @param pipelines
   * @param errorHandler
   * @param flashMessage
   */
  constructor(
    private route: ActivatedRoute,
    private pipelines: PipelinesService,
    private errorHandler: ErrorService,
    private flashMessage: FlashMessageService) {
  }

  /**
   * Get Configuration Information
   */
  getConfigurationInfo() {
    this.pipelines.getPipelineByAppId(this.appId)
      .then((res) => {
        if (res.length === 0) {
          this.flashMessage.showError('Unable to find pipeline information for this application.');
        } else {
          this.gitUrl = res[0].repo_data.repos[0].link;
          this.gitClone = 'git clone --branch [branch] ' + this.gitUrl + ' [destination]';
        }
      })
      .catch((e) => {
        this.errorHandler.apiError(e);
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
