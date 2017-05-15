import {Component, OnInit, Input, Output, EventEmitter} from '@angular/core';

@Component({
  selector: 'app-select-list',
  templateUrl: './select-list.component.html',
  styleUrls: ['./select-list.component.scss']
})
export class SelectListComponent implements OnInit {

  /**
   * Title
   * @type {String}
   */
  @Input()
  title: string;

  /**
   * Options list
   * @type {any[]}
   */
  @Input()
  options: any[];

  /**
   * Filter Placeholder
   * @type {String}
   */
  @Input()
  filterPlaceholder = '';

  /**
   * EventEmitter to emit selected option
   */
  @Output()
  selected = new EventEmitter();

  /**
   * Selected Item
   * @type {any}
   */
  selectedItem: any;

  /**
   * Filter text
   * @type {String}
   */
  filterText: string;

  /**
   * Builds the component
   */
  constructor() { }

  /**
   * Initiate Component
   */
  ngOnInit() {
  }

  /**
   * Select an option
   * @param option
   */
  selectOption(item) {
    this.selectedItem = item;
    this.selected.emit(this.selectedItem);
  }
}
