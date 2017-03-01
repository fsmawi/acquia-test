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

  /**
   * Returns progress display type
   * @type {string}
   *
   * valid types:
   *  - indeterminate
   */
  @Input()
  progressType: string;

  /**
   * Returns progress display type variant
   * @type {string}
   *
   * valid variants:
   *  - arrows
   *  - dots
   */
  @Input()
  progressVariant: string;

  /**
   * Returns progress display size.
   * @type {string}
   *
   * Valid sizes
   *  - small
   *  - medium
   *  - large
   *  - giant
   *  - gargantuan
   */
  @Input()
  progressSize = 'gargantuan';

  constructor() { }

  ngOnInit() {
  }

}
