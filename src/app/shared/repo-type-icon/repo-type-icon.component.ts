import {Component, OnInit, Input, OnChanges} from '@angular/core';
import {repoType} from '../../core/repository-types';

@Component({
  selector: 'app-repo-type-icon',
  templateUrl: './repo-type-icon.component.html',
  styleUrls: ['./repo-type-icon.component.scss']
})
export class RepoTypeIconComponent implements OnChanges {

  /**
   * Repository type
   * @type {string}
   */
  @Input()
  repoType: string;

  /**
   * Repository type Label
   * @type {string}
   */
  typeLabel: string;

  /**
   * Builds the component
   */
  constructor() { }

  /**
   * Initialize component
   */
  ngOnChanges() {
    if (this.repoType && repoType[this.repoType]) {
      this.typeLabel = repoType[this.repoType].name;
    } else {
      this.typeLabel = 'Acquia Git';
    }
  }
}
