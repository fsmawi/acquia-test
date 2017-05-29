import { Component, OnInit, ElementRef, Input, AfterViewInit } from '@angular/core';
import * as Clipboard from 'clipboard';

@Component({
  selector: 'e-clipboard',
  templateUrl: './clipboard.component.html',
  styleUrls: ['./clipboard.component.scss']
})
export class ClipboardComponent implements AfterViewInit {

  /**
   * Input Text
   */
  @Input()
  inputText: string;

  /**
   * Clipboard object
   * @type {Clipboard}
   */
  clipboard: Clipboard;

  /**
   * Is text copied
   * @type {Boolean}
   */
  isCopied = false;

  /**
   * Holds the element to be shown for input text e.g: input, pre
   * @type {string}
   */
  @Input()
  htmlElement = 'input';

  /**
   * Element Label
   * @type {string}
   */
  @Input()
  label: string;

  /**
   * Component type
   * @type {[type]}
   */
  @Input()
  type = 'icon';

  /**
   * Builds the component
   * @param el
   */
  constructor(private el: ElementRef) { }

  /**
   * Initiate Component
   */
  ngAfterViewInit() {
    this.clipboard = new Clipboard(this.el.nativeElement.querySelector('a'), {
      text: () => this.inputText
    });
    this.clipboard.on('success', () => {
      this.isCopied = true;
      if (this.htmlElement === 'input' && this.type === 'icon') {
        const querySelector = 'input[type="text"]';
        this.el.nativeElement.querySelector(querySelector).select();
      }
      setTimeout(() => {
        this.isCopied = false;
      }, 2000);
    });
  }
}
