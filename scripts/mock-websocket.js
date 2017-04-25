/**
 * Created by stephen.raghunath on 3/7/17.
 */

const WebSocket = require('ws');
const colors = require('colors');
const async = require('async');

// Create the websocket server
const wss = new WebSocket.Server({
  perMessageDeflate: false,
  port: 8080
});

// Bind to events
wss.on('connection', ws => {
  console.log('Connection detected!'.cyan);
  ws.on('message', message => {
    console.log(`received: ${message}`.gray);
    let obj = JSON.parse(message);

    switch (obj.cmd) {
      case 'authenticate':
        return console.log(`Someone authenticated! Secret: ${obj.secret.rainbow}`);
      case 'enable':
        return setupStream(ws);
      default:
        return console.log(`Unknown command ${obj.cmd}`.red);
    }
  });

  // Start off the streaming handshake
  emit(ws, 'connected')
    .then(() => emit(ws, 'available', {
      type: 'stdout'
    }));
});

/**
 * Emulates the LogTailor (https://github.com/acquia/logtailor)
 * events locally and consistently on connect for development
 * of the log streaming feature
 */
function setupStream(ws) {
  pause()
    .then(() => emit(ws, 'line', {
      type: 'log-item',
      unix_time: new Date().getTime() / 1000,
      disp_time: new Date().toISOString(),
      text: 'Some line item 1'
    }))
    .then(() => pause())
    .then(() => emit(ws, 'line', {
      type: 'log-item',
      unix_time: new Date().getTime() / 1000,
      disp_time: new Date().toISOString(),
      text: 'Some line item 2'
    }))
    .then(() => pause())
    .then(() => emit(ws, 'line', {
      type: 'log-item',
      unix_time: new Date().getTime() / 1000,
      disp_time: new Date().toISOString(),
      text: 'Some line item 3'
    }))
    .then(() => pause())
    .then(() => emit(ws, 'line', {
      type: 'log-item',
      unix_time: new Date().getTime() / 1000,
      disp_time: new Date().toISOString(),
      text: 'Some line item 4'
    }))
    .then(() => pause())
    .then(() => emit(ws, 'line', {
      type: 'log-item',
      unix_time: new Date().getTime() / 1000,
      disp_time: new Date().toISOString(),
      text: 'Some line item 5'
    }))
    .then(() => ws.close())
    .then(() => console.log('Socket closed!'.magenta))
    .catch(e => console.log('Error occured'.red, e));
}

function pause(timeout) {
  return new Promise(resolve => setTimeout(() => resolve(), timeout || 2000));
}

function emit(ws, event, data) {
  console.log(`Emitting ${event}`.blue, data);
  return Promise.resolve(ws.send(JSON.stringify(Object.assign({cmd: event}, data))));
}

