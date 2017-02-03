import {Component, OnInit} from '@angular/core';
import {Router} from '@angular/router';
import {environment} from '../../environments/environment';

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

    // HACK: Psuedo Basic Auth for Internal Demos
    if (this.accessCode !== 'pipelines2017') {
      return alert('Your beta access code is not correct. Please reach out to your manager for the correct code.');
    }

    this.router.navigateByUrl(`/jobs/${this.appId}`);
  }
}
