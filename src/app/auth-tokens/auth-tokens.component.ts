import {Component, OnInit} from '@angular/core';
import {Router} from '@angular/router';
import {environment} from '../../environments/environment';
import {defaultIfEmpty} from 'rxjs/operator/defaultIfEmpty';

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
   * Builds the component
   */
  constructor(private router: Router) {
  }

  /**
   * Runs on Init
   */
  ngOnInit() {
  }

  /**
   * Set the Headers for the Pipelines API Service
   */
  login() {
    environment.n3Key = this.n3Key;
    environment.n3Secret = this.n3Secret;
    if (!environment.production) {
      document.cookie = 'CHOCOLATECHIPSSL=123456;'; // mock one
    }
    this.router.navigateByUrl(`/jobs/${this.appId}`);
  }
}
