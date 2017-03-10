import {Injectable} from '@angular/core';

import {WebSocketHandler} from '../models/web-socket-handler';

@Injectable()
export class WebSocketService {

  /**
   * Flag for browser support
   */
  supported: boolean;

  /**
   * Creates the service
   */
  constructor() {
    // Determine support
    if (WebSocket) {
      this.supported = true;
    }
  }

  /**
   * Creates a web socket if able to
   * @param url
   * @returns {any}
   */
  connect(url: string) {
    if (this.supported) {
      return new WebSocketHandler(url);
    } else {
      return null;
    }
  }
}
