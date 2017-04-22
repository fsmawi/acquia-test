import {EventEmitter, Injectable} from '@angular/core';

export class WebSocketHandler extends EventEmitter<{name: string, argument: any}> {

  /**
   * Holds the websocket to emit/receive events to
   */
  socket: WebSocket|any;

  /**
   * Builds the handler
   * @param url
   * @param WebSocketClass Non-standard websocket object if needed
   */
  constructor(url: string, WebSocketClass: any = null) {
    super();
    this.socket = WebSocketClass ? new WebSocketClass(url) : new WebSocket(url);

    // map to friendly event emitter handlers
    this.socket.onopen = () => console.log('socket opened', url);
    this.socket.onclose = () => this._emit('close');
    this.socket.onerror = (e) => this._emit('error', e);
    this.socket.onmessage = (message: MessageEvent) => {
      try {
        const event = JSON.parse(message.data);
        this._emit(event.cmd, event);
      } catch (e) {
        // not json
        this._emit('un-named', message.data);
      }
    };
  }

  /**
   * Models the generic event emitter pattern
   * @param event
   * @param argument
   * @private
   */
  _emit(event: string, argument: any = null) {
    this.emit({name: event, argument: argument});
  }

  /**
   * Sends an event on the websocket
   * @param params
   */
  send(params: any) {
    this.socket.send(JSON.stringify(params));
  }
}
