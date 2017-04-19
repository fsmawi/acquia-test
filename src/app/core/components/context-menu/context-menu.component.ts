import {Component, HostListener, OnInit} from '@angular/core';

import {ContextMenuService} from '../../services/context-menu.service';
import {MenuItem} from '../../models/menu-item';

declare const window;

@Component({
  selector: 'app-context-menu',
  templateUrl: './context-menu.component.html',
  styleUrls: ['./context-menu.component.scss']
})
export class ContextMenuComponent implements OnInit {

  /**
   * Flag to show/hide menu
   * @type {Boolean}
   */
  menuOpen = false;

  /**
   * Mouse location
   * @type {Object}
   */
  mouseLocation = {x: 0, y: 0};

  /**
   * Items menu
   * @type {MenuItem[]}
   */
  items: MenuItem[];

  /**
   * Handle all events that should close the menu
   */
  @HostListener('document:click')
  @HostListener('document:contextmenu')
  @HostListener('window:scroll')
  @HostListener('document:keydown', ['$event'])
  public clickedOutside(): void {
    if (this.menuOpen) {
      this.menuOpen = false;
    }
  }

  /**
   * Builds the component
   * @param contextMenuService
   */
  constructor(private contextMenuService: ContextMenuService) {
    this.contextMenuService.show = this.show.bind(this);
  }

   /**
   * Show context menu
   * @param {any}
   */
  show(menu: any) {
    this.menuOpen = true;
    this.items = menu.items;
    this.mouseLocation = menu.mouseLocation;
  }

  /**
   * Get menu css position X/Y
   * @return {any}
   */
  get locationCss(): any {

    let positionX = this.mouseLocation.x,
        positionY = this.mouseLocation.y;

    if (this.mouseLocation.x + 200 >= window.innerWidth) {
      positionX -= 200;
    }

    if (this.mouseLocation.y + (this.items.length * 32) >= window.innerHeight) {
      positionY -= this.items.length * 32;
    }

    return {
      position: 'fixed',
      left: positionX,
      top: positionY
    };
  }

  /**
   * Initiate Component
   */
  ngOnInit() {
    this.items = [];
  }
}
