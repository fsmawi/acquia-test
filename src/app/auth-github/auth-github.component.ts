import {Component, OnInit} from '@angular/core';
import {GithubService} from '../core/services/github.service';
import {PipelinesService} from '../core/services/pipelines.service';
import {ErrorService} from '../core/services/error.service';
import {ActivatedRoute, Router, Params} from '@angular/router';
import { MdDialog, MdDialogRef } from '@angular/material';
import { GithubDialogRepositoriesComponent } from './github-dialog-repositories/github-dialog-repositories.component';

@Component({
  selector: 'app-auth-github',
  templateUrl: './auth-github.component.html',
  styleUrls: ['./auth-github.component.scss']
})
export class AuthGithubComponent implements OnInit {

  /**
   * Connect to github
   * @type {Boolean}
   */
  connect = false;

  /**
   * ApplicationID
   * @type {string}
   */
  appId: string;

  /**
   * Gihub authorized Indicator
   * @type {boolean}
   */
  authorized = false;

  /**
   * Access token from Github
   * @type {string}
   */
  token: string;

  /**
   * Loading
   * @type {boolean}
   */
  loading = false;

  /**
   * Builds the component
   * @param route
   * @param auth
   * @param router
   * @param errorHandler
   * @param pipelines
   * @param dialog
   */
  constructor(private route: ActivatedRoute,
              private auth: GithubService,
              private router: Router,
              private errorHandler: ErrorService,
              private pipelines: PipelinesService,
              private dialog: MdDialog) {
  }

  /**
   * Authenticate on github
   */
  authenticate() {
    if (!this.authorized) {
      this.auth.authenticate();
    }
  }

  /**
   * Login
   */
  login() {
    this.route.queryParams
      .subscribe((params: Params) => {
        this.auth.login(params)
          .then((result) => {
            this.token = result;
            this.authorized = true;
          })
          .catch((e) => {
            this.errorHandler.apiError(e);
            this.back();
          })
          .then(() => this.loading = true);
      });
  }

  /**
   * Open Dialog to choose a github directory
   */
  selectRepository() {

    let dialogRef: MdDialogRef<GithubDialogRepositoriesComponent>;

    dialogRef = this.dialog.open(GithubDialogRepositoriesComponent, {width: '600px', height: '500px'});

    dialogRef.componentInstance.accessToken = this.token;

    dialogRef.afterClosed().subscribe(result => {
      if (result !== undefined && Object.keys(result).length !== 0) {
        this.attachRepository(result);
      }
    });
  }

  /**
   * Attach repository to the current application
   * @param repository
   */
  attachRepository(repository) {
    this.pipelines.attachGithubRepository(this.token, repository.full_name, [this.appId])
      .then((r) => this.displayJobs())
      .catch(e => this.errorHandler.apiError(e));
  }

  /**
   * Navigate to jobs page
   */
  displayJobs() {
    this.router.navigate(['jobs', this.appId]);
  }

  /**
   * Navigate back to Connexion page
   */
  back() {
    this.router.navigate(['auth/github', this.appId]);
  }

  /**
   * On component initialize, initiate redirect url
   */
  ngOnInit() {
    this.connect = false;
    this.authorized = false;
    this.loading = false;
    this.route.params
      .subscribe((params) => {
        this.appId = params['app-id'];

        //  Connection screen
        if (this.route.snapshot.data['type'] !== 'code') {
           // Get presigned URL
          this.pipelines.getPresignedUrl(this.appId)
              .then(url => {
                this.connect = true;
                this.auth.setRedirectUrl(url.redirect_url + `/auth/github/code/${this.appId}`);
              })
              .catch(e => this.errorHandler.apiError(e))
              .then(() => this.loading = true);
        } else {
          this.connect = true; // already connected if in code sceen
           // Get authorization code from query parameters
          this.login();
        }
      });
  }
}
