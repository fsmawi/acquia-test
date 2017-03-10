import {ActivatedRoute} from '@angular/router';
import {Component, OnInit} from '@angular/core';
import {MdDialogRef} from '@angular/material';


@Component({
  selector: 'app-start-job',
  templateUrl: './start-job.component.html',
  styleUrls: ['./start-job.component.scss']
})
export class StartJobComponent implements OnInit {

  /**
   * App Id segment tracking.
   */
  appId: string;

  /**
   * Builds the component
   * @param dialogRef
   * @param route
   */
  constructor(
    public dialogRef: MdDialogRef<StartJobComponent>,
    private route: ActivatedRoute) {
  }

  /**
   * Initialize component
   */
  ngOnInit() {
    // get the appId if specified
    this.appId = this.route.snapshot.params['app-id'];
  }
}
