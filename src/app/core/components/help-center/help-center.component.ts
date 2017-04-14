import {Component, OnInit} from '@angular/core';
import {ObservableMedia} from '@angular/flex-layout';

import {Subject} from 'rxjs/Subject';
import 'rxjs/add/operator/map';
import 'rxjs/add/operator/debounceTime';

import {HelpCenterService} from '../../services/help-center.service';
import {animations} from '../../animations';
import {helpCenterContent} from './help-center.definition';
import {HelpItem} from '../../models/help-item';

@Component({
  selector: 'app-help-center',
  templateUrl: './help-center.component.html',
  styleUrls: ['./help-center.component.scss'],
  animations: animations
})
export class HelpCenterComponent implements OnInit {

  /**
   * Flag to show help center
   * @type {boolean}
   */
  showHelpCenter = false;

  /**
   * Holds the help content
   */
  helpContent: Array<HelpItem>;

  /**
   * Holds the filterered help content
   */
  filteredHelpContent: Array<HelpItem>;

  /**
   * Holds the filtered input
   * @type {string}
   */
  filterText = '';

  /**
   * Holds the subject, used to debounce
   */
  filterSubject = new Subject<string>();

  /**
   * Builds the component
   * @param helpCenterService
   * @param media
   */
  constructor(private helpCenterService: HelpCenterService,
              public media: ObservableMedia) {
    this.helpCenterService.show = this.show.bind(this);
  }

  /**
   * Initialise the component
   */
  ngOnInit() {
    this.helpContent = helpCenterContent;
    this.filter();

    this.filterSubject
      .debounceTime(400)
      .subscribe(filterText => {
        this.filterText = filterText;
        this.filter();
      });
  }

  /**
   * Show the help center
   */
  show() {
    this.filterText = '';
    this.filter();
    this.showHelpCenter = true;

    setTimeout(() => {
      const d = document.getElementById('help-center-bg');
      // Check if exists; for unit test cases
      if (d) {
        d.style.opacity = '1';
      }
    }, 10);
  }

  /**
   * Close the help center
   */
  close() {
    this.showHelpCenter = false;
  }

  /**
   * Filter the help center with the input provided
   */
  filter() {
    if (this.filterText === '' || !this.filterText) {
      this.filteredHelpContent = this.helpContent;
    } else {
      const filterTextLowerCase = this.filterText.toLowerCase();
      this.filteredHelpContent = this.helpContent && this.helpContent.filter(helpItem => {
            return helpItem.title.toLowerCase().indexOf(filterTextLowerCase) > -1 ||
              helpItem.description.toLowerCase().indexOf(filterTextLowerCase) > -1;
        });
    }
  }

}
