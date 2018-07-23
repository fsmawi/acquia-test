var pusage = require('pidusage')

// classic "drop somewhere"... yeah I'm a lazy guy
var formatBytes = function (bytes, precision) {
  var kilobyte = 1024
  var megabyte = kilobyte * 1024
  var gigabyte = megabyte * 1024
  var terabyte = gigabyte * 1024

  if ((bytes >= 0) && (bytes < kilobyte)) {
    return bytes + ' B   '
  } else if ((bytes >= kilobyte) && (bytes < megabyte)) {
    return (bytes / kilobyte).toFixed(precision) + ' KB  '
  } else if ((bytes >= megabyte) && (bytes < gigabyte)) {
    return (bytes / megabyte).toFixed(precision) + ' MB  '
  } else if ((bytes >= gigabyte) && (bytes < terabyte)) {
    return (bytes / gigabyte).toFixed(precision) + ' GB  '
  } else if (bytes >= terabyte) {
    return (bytes / terabyte).toFixed(precision) + ' TB  '
  } else {
    return bytes + ' B   '
  }
}

var i = 0
var max_mem = false
var bigMemoryLeak = []

var stress = function (cb) {
  var j = 1000
  var arr = []

  while (j--) {
    arr[j] = []

    var lorem = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum. Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.';

    for (var k = 0; k < 1000; k++) {
      arr[j][k] = {lorem: lorem + lorem + lorem + lorem + lorem + lorem + lorem + lorem + lorem + lorem + lorem + lorem}
    }
  }

  if (!max_mem) {
    bigMemoryLeak.push(arr)
  }

  pusage(process.pid, function (err, stat) {
    if (err) {
      throw err
    }

    console.log('Mem: %s %ss', formatBytes(stat.memory, 2), i)

    if (stat.memory > 20e8) {
      max_mem = true
    }

    i++
    return cb(null, false)
  })
}

var interval = function () {
  return setTimeout(function () {
    stress(function (err, stop) {
      if (err) {
        throw err
      }

      if (stop) {
        process.exit()
      } else {
        return interval()
      }
    })
  }, 1000)
}

setTimeout(function () {
  interval()
}, 1000)

setTimeout(function() {
  process.exit()
}, 200000);
