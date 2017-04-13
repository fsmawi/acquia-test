import {Component, OnInit} from '@angular/core';
import {ObservableMedia} from '@angular/flex-layout';

@Component({
  selector: 'app-top-level-navigation',
  templateUrl: './top-level-navigation.component.html',
  styleUrls: ['./top-level-navigation.component.scss']
})
export class TopLevelNavigationComponent implements OnInit {

  /**
   * Flag to toggle the menu (navigation links)
   * @type {boolean}
   */
  showMenu = false;

  /**
   * Flag to toggle the profile popover (avatar on click)
   * @type {boolean}
   */
  showProfilePopover = false;

  /**
   * Builds the component
   * @param media
   */
  constructor(public media: ObservableMedia) {
  }

  ngOnInit() {
  }

  /**
   * Toggle the menu on click of Hamburger Menu on smaller screens
   */
  toggleMenu() {
    this.showMenu = !this.showMenu;
  }

  /**
   * Toggle the menu on click of Hamburger Menu on smaller screens
   */
  toggleProfilePopover() {
    this.showProfilePopover = !this.showProfilePopover;
  }
}
