import {Component, OnInit, Input} from '@angular/core';
import {Job} from '../../core/models/job';

@Component({
  selector: 'app-job-list',
  templateUrl: './job-list.component.html',
  styleUrls: ['./job-list.component.scss']
})
export class JobListComponent implements OnInit {

  /**
   * List of Jobs to Display
   */
  @Input()
  jobs: Array<Job>;

  /**
   * App ID, used to make back links
   */
  @Input()
  appId: string;

  /**
   * Builds the component and injects services if needed
   */
  constructor() {
  }

  /**
   * Initialize
   */
  ngOnInit() {
  }
}
