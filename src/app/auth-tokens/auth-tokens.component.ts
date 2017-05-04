import {Component, OnInit} from '@angular/core';
import {Router} from '@angular/router';

import {environment} from '../../environments/environment';
import {defaultIfEmpty} from 'rxjs/operator/defaultIfEmpty';
import {AuthService} from '../core/services/auth.service';

@Component({
  selector: 'app-auth-tokens',
  templateUrl: './auth-tokens.component.html',
  styleUrls: ['./auth-tokens.component.scss']
})
export class AuthTokensComponent implements OnInit {

  /**
   * Application ID to navigate to
   */
  appId: string;

  /**
   * N3 Key header to use
   */
  n3Key: string;

  /**
   * N3 Secret Header to use
   */
  n3Secret: string;

  /**
   * Psuedo Basic Auth Access
   */
  accessCode: string;

  /**
   * Log in loading indicator
   */
  loading: boolean;

  /**
   * Logged in flag
   */
  loggedIn: boolean;

  /**
   * Builds the component
   */
  constructor(private router: Router, private auth: AuthService) {
  }

  /**
   * Runs on Init
   */
  ngOnInit() {
    this.loading = true;
    // if not standalone, get appId from session storage if exists and redirect to /applications
    if (!environment.standalone && sessionStorage.getItem('pipelines.standalone.application.id')) {
      this.router.navigateByUrl(`/applications/${sessionStorage.getItem('pipelines.standalone.application.id')}`);
    } else {
      this.auth.isLoggedIn()
        .then(authenticated => {
          if (authenticated) {
            this.loggedIn = true;
          }
        })
        .then(() => this.loading = false);
    }
  }

  /**
   * Set the Headers for the Pipelines API Service
   */
  login() {
    this.loading = true;
    this.auth.isLoggedIn().then(authenticated => {
      if (authenticated) {
        this.router.navigateByUrl(`/applications/${this.appId}`);
      } else {
        window.top.location.href = '/';
      }
    });
  }
}
