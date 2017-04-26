import {Injectable} from '@angular/core';
import 'ansi_up';

/**
 * Global from 3rd party
 */
declare const ansi_up;

@Injectable()
export class AnsiService {
  /**
   * Build the service
   */
  constructor() {
  }

  /**
   * Convert a terminal string to html
   * @param input
   * @returns {String}
   */
  convert(input: string) {
    // escape any prior HTML
    return ansi_up.ansi_to_html(input.replace(/[\"&'\/<>]/g, function (a) {
      return {
        '"': '&quot;', '&': '&amp;', '\'': '&#39;',
        '/': '&#47;', '<': '&lt;', '>': '&gt;'
      }[a];
    }));
  }
}
