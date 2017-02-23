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
      this.el.nativeElement.querySelector('input[type="text"]').select();
      setTimeout(() => {
        this.isCopied = false;
      }, 2000);
    });
  }
}
