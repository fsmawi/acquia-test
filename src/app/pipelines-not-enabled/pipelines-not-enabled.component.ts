import {Component, OnInit, Input} from '@angular/core';
import {ActivatedRoute} from '@angular/router';

@Component({
  selector: 'app-pipelines-not-enabled',
  templateUrl: './pipelines-not-enabled.component.html',
  styleUrls: ['./pipelines-not-enabled.component.scss']
})
export class PipelinesNotEnabledComponent implements OnInit {

  /**
   * Holds the application Id
   */
  appId: string;

  /**
   * Flag to check if the component is loading
   */
  isLoading = true;

  /**
   * Builds the component
   * @param route
   */
  constructor(private route: ActivatedRoute) { }

  /**
   * Initialize the component
   */
  ngOnInit() {
    this.route.params.subscribe(params => {
      this.appId = params['app'];
      this.isLoading = false;
    });
  }
}
