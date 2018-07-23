require(__dirname+"/processor-usage.js").startWatching();

var shouldRun = true;
var desiredLoadFactor = 1.5;
var i = 0
var bigMemoryLeak = []

function blockCpuFor(ms) {
	var now = new Date().getTime();
	var result = 0
	while(shouldRun) {
		result += Math.random() * Math.random();
		Math.sqrt(result);
		if (new Date().getTime() > now +ms)
			return;
	}
}

function start() {
	shouldRun = true;
	blockCpuFor(1000*desiredLoadFactor);

	var j = 1000
  var arr = []

  while (j--) {
    arr[j] = []

		var lorem = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum. Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.';

    for (var k = 0; k < 1000; k++) {
      arr[j][k] = {lorem: lorem + lorem + lorem + lorem + lorem + lorem + lorem + lorem + lorem + lorem + lorem + lorem}
    }
	}

	if (i < 70) {
    bigMemoryLeak.push(arr)
  }

	setTimeout(start, 1000* (1 - desiredLoadFactor));
}

setInterval(function() {
    console.log("current process cpu usage: "+(global.processCpuUsage || 0)+"% "+ (i++) + "s");}
, 1000);

if (process.argv[2]) {
    var value = parseFloat(process.argv[2]);
    if (value < 0 || value > 1) {
        console.log("please give desired load value as a range [0..1]");
	      process.exit(-1);
    } else {
        desiredLoadFactor = value;
    }
}
start();

setTimeout(function() {
  process.exit();
}, 120000);
