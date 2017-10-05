import {Component, OnInit, Input} from '@angular/core';
import {ActivatedRoute, Params} from '@angular/router';
import {SegmentService} from '../core/services/segment.service';

@Component({
  selector: 'app-api-error',
  templateUrl: 'status-code.component.html',
  styleUrls: ['status-code.component.scss']
})
export class StatusCodeComponent implements OnInit {
  /**
   * Error code to be displayed in the header title eg: 404, 403
   */
  errorCode: String;
  /**
   * Error message to be displayed in the header title eg: Not Found/ Forbidden
   */
  errorTitle: String;
  /**
   * Error message to be displayed  eg: Not Found/ Forbidden
   */
  errorMessage: String;
  /**
   * Tag or redirect message to be displayed at the end of error message
   */
  tagMessage: String;
  /**
   * Router URL to be linked with tag message
   */
  tagLink: String;
  /**
   * Target for the tag link to open e.g., _top, _parent, _self, _blank
   */
  tagTarget: String;

  /**
   * Build the component and inject services if needed
   * @param route
   * @param segment
   */
  constructor(private route: ActivatedRoute, private segment: SegmentService) {
  }

  /**
   * Initialize and setup error handler
   */
  ngOnInit() {
    // Get the required params from the url
    // Assign if exists or fallback to the defaults
    this.route.queryParams.subscribe(
      (params: Params) => {
        this.errorCode = params['errorCode'] ? params['errorCode'] : '404';
        this.errorTitle = params['errorTitle'] ? params['errorTitle'] : 'Not Found';
        this.errorMessage = params['errorMessage'] ? params['errorMessage'] :
          `Yikes! We can’t find the page you're looking for.`;
        this.tagMessage = params['tagMessage'] ? params['tagMessage'] : 'Homepage';
        this.tagLink = params['tagLink'] ? params['tagLink'] : '/auth/tokens';
        this.tagTarget = params['tagTarget'] ? params['tagTarget'] : '_self';
        this.segment.page(`${this.errorCode}${this.errorTitle}View`);
      });

  }
}
