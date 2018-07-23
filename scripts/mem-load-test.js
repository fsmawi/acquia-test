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
  var j = 500
  var arr = []

  while (j--) {
    arr[j] = []

    for (var k = 0; k < 1000; k++) {
      arr[j][k] = {lorem: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum non odio venenatis, pretium ligula nec, fringilla ipsum. Sed a erat et sem blandit dignissim. Pellentesque sollicitudin felis eu mattis porta. Nullam nec nibh nisl. Phasellus convallis vulputate massa vitae fringilla. Etiam facilisis lectus in odio lacinia rutrum. Praesent facilisis vitae urna a suscipit. Aenean lacinia blandit lorem, et ullamcorper metus sagittis faucibus. Nam porta eros nisi, at adipiscing quam varius eu. Vivamus sed sem quis lorem varius posuere ut quis elit.'}
    }
  }

  if (!max_mem) {
    bigMemoryLeak.push(arr)
  }

  pusage(process.pid, function (err, stat) {
    if (err) {
      throw err
    }

    console.log('Mem: %s %ss', formatBytes(stat.memory), i)

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
}, 120000);
