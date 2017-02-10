import { Component, OnInit, Input } from '@angular/core';

/**
 * Progress Component
 * @module app/elemental
 */

@Component({
  selector: 'e-progress',
  templateUrl: './progress.component.html',
  styleUrls: ['./progress.component.scss']
})
export class ProgressComponent implements OnInit {

  @Input()

  /**
   * Returns progress display type
   * @type {string}
   *
   * valid types:
   *  - indeterminate
   */
  progressType: string;

  /**
   * Returns progress display type variant
   * @type {string}
   *
   * valid variants:
   *  - arrows
   */
  progressVariant: string;

  constructor() { }

  ngOnInit() {
  }

}
