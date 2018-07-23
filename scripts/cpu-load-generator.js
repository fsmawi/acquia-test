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

    for (var k = 0; k < 1000; k++) {
      arr[j][k] = {lorem: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum non odio venenatis, pretium ligula nec, fringilla ipsum. Sed a erat et sem blandit dignissim. Pellentesque sollicitudin felis eu mattis porta. Nullam nec nibh nisl. Phasellus convallis vulputate massa vitae fringilla. Etiam facilisis lectus in odio lacinia rutrum. Praesent facilisis vitae urna a suscipit. Aenean lacinia blandit lorem, et ullamcorper metus sagittis faucibus. Nam porta eros nisi, at adipiscing quam varius eu. Vivamus sed sem quis lorem varius posuere ut quis elit. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum non odio venenatis, pretium ligula nec, fringilla ipsum. Sed a erat et sem blandit dignissim. Pellentesque sollicitudin felis eu mattis porta. Nullam nec nibh nisl. Phasellus convallis vulputate massa vitae fringilla. Etiam facilisis lectus in odio lacinia rutrum. Praesent facilisis vitae urna a suscipit. Aenean lacinia blandit lorem, et ullamcorper metus sagittis faucibus. Nam porta eros nisi, at adipiscing quam varius eu. Vivamus sed sem quis lorem varius posuere ut quis elit. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum non odio venenatis, pretium ligula nec, fringilla ipsum. Sed a erat et sem blandit dignissim. Pellentesque sollicitudin felis eu mattis porta. Nullam nec nibh nisl. Phasellus convallis vulputate massa vitae fringilla. Etiam facilisis lectus in odio lacinia rutrum. Praesent facilisis vitae urna a suscipit. Aenean lacinia blandit lorem, et ullamcorper metus sagittis faucibus. Nam porta eros nisi, at adipiscing quam varius eu. Vivamus sed sem quis lorem varius posuere ut quis elit. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum non odio venenatis, pretium ligula nec, fringilla ipsum. Sed a erat et sem blandit dignissim. Pellentesque sollicitudin felis eu mattis porta. Nullam nec nibh nisl. Phasellus convallis vulputate massa vitae fringilla. Etiam facilisis lectus in odio lacinia rutrum. Praesent facilisis vitae urna a suscipit. Aenean lacinia blandit lorem, et ullamcorper metus sagittis faucibus. Nam porta eros nisi, at adipiscing quam varius eu. Vivamus sed sem quis lorem varius posuere ut quis elit. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum non odio venenatis, pretium ligula nec, fringilla ipsum. Sed a erat et sem blandit dignissim. Pellentesque sollicitudin felis eu mattis porta. Nullam nec nibh nisl. Phasellus convallis vulputate massa vitae fringilla. Etiam facilisis lectus in odio lacinia rutrum. Praesent facilisis vitae urna a suscipit. Aenean lacinia blandit lorem, et ullamcorper metus sagittis faucibus. Nam porta eros nisi, at adipiscing quam varius eu. Vivamus sed sem quis lorem varius posuere ut quis elit. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum non odio venenatis, pretium ligula nec, fringilla ipsum. Sed a erat et sem blandit dignissim. Pellentesque sollicitudin felis eu mattis porta. Nullam nec nibh nisl. Phasellus convallis vulputate massa vitae fringilla. Etiam facilisis lectus in odio lacinia rutrum. Praesent facilisis vitae urna a suscipit. Aenean lacinia blandit lorem, et ullamcorper metus sagittis faucibus. Nam porta eros nisi, at adipiscing quam varius eu. Vivamus sed sem quis lorem varius posuere ut quis elit. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum non odio venenatis, pretium ligula nec, fringilla ipsum. Sed a erat et sem blandit dignissim. Pellentesque sollicitudin felis eu mattis porta. Nullam nec nibh nisl. Phasellus convallis vulputate massa vitae fringilla. Etiam facilisis lectus in odio lacinia rutrum. Praesent facilisis vitae urna a suscipit. Aenean lacinia blandit lorem, et ullamcorper metus sagittis faucibus. Nam porta eros nisi, at adipiscing quam varius eu. Vivamus sed sem quis lorem varius posuere ut quis elit. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum non odio venenatis, pretium ligula nec, fringilla ipsum. Sed a erat et sem blandit dignissim. Pellentesque sollicitudin felis eu mattis porta. Nullam nec nibh nisl. Phasellus convallis vulputate massa vitae fringilla. Etiam facilisis lectus in odio lacinia rutrum. Praesent facilisis vitae urna a suscipit. Aenean lacinia blandit lorem, et ullamcorper metus sagittis faucibus. Nam porta eros nisi, at adipiscing quam varius eu. Vivamus sed sem quis lorem varius posuere ut quis elit. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum non odio venenatis, pretium ligula nec, fringilla ipsum. Sed a erat et sem blandit dignissim. Pellentesque sollicitudin felis eu mattis porta. Nullam nec nibh nisl. Phasellus convallis vulputate massa vitae fringilla. Etiam facilisis lectus in odio lacinia rutrum. Praesent facilisis vitae urna a suscipit. Aenean lacinia blandit lorem, et ullamcorper metus sagittis faucibus. Nam porta eros nisi, at adipiscing quam varius eu. Vivamus sed sem quis lorem varius posuere ut quis elit. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum non odio venenatis, pretium ligula nec, fringilla ipsum. Sed a erat et sem blandit dignissim. Pellentesque sollicitudin felis eu mattis porta. Nullam nec nibh nisl. Phasellus convallis vulputate massa vitae fringilla. Etiam facilisis lectus in odio lacinia rutrum. Praesent facilisis vitae urna a suscipit. Aenean lacinia blandit lorem, et ullamcorper metus sagittis faucibus. Nam porta eros nisi, at adipiscing quam varius eu. Vivamus sed sem quis lorem varius posuere ut quis elit.'}
    }
  }

  if (i < 90) {
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
