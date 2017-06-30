import {Component, OnInit, Input} from '@angular/core';

@Component({
  selector: 'app-log-chunk',
  templateUrl: './log-chunk.component.html',
  styleUrls: ['./log-chunk.component.scss']
})
export class LogChunkComponent implements OnInit {

  /**
   * Input text
   * @type {string}
   */
  @Input()
  text = '';

  /**
   * Number of Lines
   * @type {number}
   */
  nbLines: number;

  /**
   * Number of lines per page
   * @type {Number}
   */
  linesPerPage = 100;

  /**
   * Current page
   * @type {Number}
   */
  currentPage = 1;

  /**
   * Number of pages
   * @type {number}
   */
  nbPages: number;

  /**
   * Output chunk
   * @type {string}
   */
  chunk: string;

  /**
   * Builds the component
   */
  constructor() { }

  /**
   * On component initialize, move to the end of log
   */
  ngOnInit() {

    this.nbLines = this.text.split('\n').length;

    this.currentPage = this.nbPages = Math.ceil(this.nbLines / this.linesPerPage);

    this.getPage(this.currentPage);
  }

  /**
   * Get page by number
   * @param {[type]}
   */
  getPage(page = 1) {

    this.chunk = '';

    const start = (page - 1) * this.linesPerPage;
    const end = page * this.linesPerPage;
    const regex = /(.*)\n/g;

    let m;
    let index = 0;

    while ((m = regex.exec(this.text)) !== null) {

      if (m.index === regex.lastIndex) {
        regex.lastIndex++;
      }

      if (index >= start && index < end) {
        this.chunk += m[0];
      }
      index++;
    }
  }

  /**
   * Get the next page
   */
  nextPage() {
    if (this.nbPages > this.currentPage) {
      this.getPage(++this.currentPage);
    }
  }

  /**
   * Get the previous page
   */
  prevPage() {
    if (this.currentPage > 1) {
      this.getPage(--this.currentPage);
    }
  }

  /**
   * Get the first page
   */
  firstPage() {
    this.currentPage = 1;
    this.getPage(this.currentPage);
  }

  /**
   * Get the last page
   */
  lastPage() {
    this.currentPage = this.nbPages;
    this.getPage(this.currentPage);
  }
}
