/*
 This script uses multiple sub-processes to run e2e tests by feature file in parallel.
 Progress is hidden until all items complete, then dumped to the console as a first iteration.

 All environment variables available when running this script will be passed down to aqtest

 To specify aqtest args, use something like npm run e2e:parallel:local -- --tags ~@pending

 A complete example against the master/dev stage:

 1. Start a selenium server (if using selenium-standalone, run selenium-standalone start)
 2. Run the following command from the root of the repo:
 PIPELINES_URL=https://Acquia:pipelines2017@dev.pipelines-internal.acquia.com npm run e2e:parallel:local -- --tags ~@pending --tags ~@acceptance
 */

const exec = require('child_process').exec;
const path = require('path');
const fs = require('fs');
const glob = require('glob');
const colors = require('colors');
const async = require('async');

const doNotUse = [
  'bakery-sso.feature',
  'cloud-integration.feature',
  'github-flow-acceptance.feature'
];

let aqTestArgs = process.argv.slice(3);


// Automation Steps
// 1. Find all feature files
// 2. Filter out acceptance ones by doNotUse list
// 3. Use promise group of sub processes with aq test, store the stdout and stderr in memory
// 4. When all complete, output one by one each run's logs

console.log('Finding feature files'.cyan);
glob('test/e2e/features/*.feature', function (err, files) {
  if (err) {
    throw err;
  }

  // output holder
  let output = {};

  // filter do not use
  let featureFiles = files.map(f => path.basename(f)).filter(f => doNotUse.indexOf(f) === -1);

  // get scenarios by tag
  let tags = [].concat(...featureFiles.map(f => fs.readFileSync(`test/e2e/features/${f}`, 'utf8').match(/@.+_.+/g)));

  // filter pending
  tags = tags.filter(t => !t.match(/@pending/gi));

  console.log('Running Scenarios:\n'.cyan, tags.join('\n'), '\n');

  let update = setInterval(() => console.log('E2E Tests running'.gray), 60000);

  let concurrency = process.env.CONCURRENCY ? parseInt(process.env.CONCURRENCY) : 10;

  let startTime = new Date();

  async.eachLimit(tags, concurrency, (tag, cb) => {
    console.log(`Starting ${tag}`.gray);
    output[tag] = {
      err: false,
      start: new Date()
    };

    exec(`node_modules/.bin/aqtest test/e2e/features --tags ${tag} --test-name pipelines${tag} ${aqTestArgs.join(' ')}`, {
      cwd: path.join(__dirname, '..'),
      env: Object.assign(process.env, {CONFIG: process.env.CONFIG || 'test/e2e/aqtestfile.js'}),
      maxBuffer: 400 * 1024
    }, (err, log) => {
      console.log(`Completed ${tag}`.gray);
      output[tag].end = new Date();
      output[tag].duration = ((output[tag].end - output[tag].start) / 1000 / 60).toFixed(2) + ' Minutes';
      output[tag].err = err;
      output[tag].log = log;
      cb(null); // let other tests run, even if error
    });
  }, err => {
    if (err) {
      throw err;
    }
    // stop the update
    clearInterval(update);
    endTime = new Date();
    // log all output
    tags.forEach(k => console.log(k.cyan, output[k].log, output[k].err, output[k].duration.yellow));

    // throw error if there was any error
    if (tags.find(f => output[f].err)) {
      console.error(`The following scenarios failed: ${tags.filter(f => output[f].err).join(', ')}`.red);
      console.log(tags.filter(f => output[f].err).forEach(k => console.log(k.cyan, output[k].log, output[k].err)));
      console.log('Total Time Taken: ', ((endTime - startTime) / 1000 / 60).toFixed(2) + ' Minutes');
      process.exit(1);
    } else {
      console.log('All E2E scenarios successful'.green);
      console.log('Total Time Taken: ', ((endTime - startTime) / 1000 / 60).toFixed(2) + ' Minutes');
    }
  });
});

