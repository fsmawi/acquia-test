/* tslint:disable:no-unused-variable */
import {TestBed, async, inject} from '@angular/core/testing';
import {EventEmitter} from '@angular/core';

import {WebSocketHandler} from './web-socket-handler';

class MockWebSocket {
  send() {
  }
}

describe('WebSocketHandler', () => {

  let handler: WebSocketHandler;

  beforeEach(() => {
    handler = new WebSocketHandler('someurl', MockWebSocket);
    TestBed.configureTestingModule({});
  });

  it('should create an event emitter', inject([], () => {
    expect(handler instanceof WebSocketHandler).toBe(true);
    expect(handler instanceof EventEmitter).toBe(true);
  }));

  it('should emit web socket lifecycle events', inject([], () => {
    spyOn(handler, '_emit');
    handler.socket.onopen();
    handler.socket.onclose();
    handler.socket.onerror();
    handler.socket.onmessage({data: '{"cmd": "something"}'});
    expect(handler._emit).toHaveBeenCalledWith('close');
    expect(handler._emit).toHaveBeenCalledWith('error', undefined);
    expect(handler._emit).toHaveBeenCalledWith('something', {cmd: 'something'});
  }));

  it('should send a message', inject([], () => {
    try {
      handler.send('something');
    } catch (e) {
      expect(e).toBeFalsy();
    }
  }));

  it('should emit an event', inject([], () => {
    try {
      handler._emit('something', 'something');
      handler._emit('something', '{"cmd":"something"}');
    } catch (e) {
      expect(e).toBeFalsy();
    }
  }));
});
