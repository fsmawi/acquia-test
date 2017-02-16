import { Component, OnInit } from '@angular/core';
import {Router} from '@angular/router';
import {environment} from '../../environments/environment';

@Component({
  selector: 'app-mock-api',
  templateUrl: './mock-api.component.html',
  styleUrls: ['./mock-api.component.scss']
})
export class MockApiComponent implements OnInit {

  /**
   * Header ID
   * @type {string}
   */
  headerId = 'X-ACQUIA-PIPELINES-N3-APIFILE';

  /**
   * Header Value
   * @type {string}
   */
  headerValue: string;

  /**
   * Build the component
   * @param router
   */
  constructor(private router: Router) { }

  /**
   * Runs on Init
   */
  ngOnInit() {
  }

  /**
   * Set the header for Pipelines API service
   */
  save() {

    environment.headers[this.headerId] = this.headerValue;

    this.router.navigateByUrl(`/`);
  }
}
