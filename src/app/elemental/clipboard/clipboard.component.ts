import { Component, OnInit, ElementRef, Input } from '@angular/core';
import * as Clipboard from 'clipboard';

@Component({
  selector: 'e-clipboard',
  templateUrl: './clipboard.component.html',
  styleUrls: ['./clipboard.component.scss']
})
export class ClipboardComponent implements OnInit {

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
   * Builds the component
   * @param el
   */
  constructor(private el: ElementRef) { }

  /**
   * Initiate Component
   */
  ngOnInit() {
    this.clipboard = new Clipboard(this.el.nativeElement.querySelector('a'), {
      text: () => this.inputText
    });
    this.clipboard.on('success', () => {
      this.isCopied = true;
      if (this.htmlElement === 'input') {
        const querySelector = 'input[type="text"]';
        this.el.nativeElement.querySelector(querySelector).select();
      }
      setTimeout(() => {
        this.isCopied = false;
      }, 2000);
    });
  }
}
