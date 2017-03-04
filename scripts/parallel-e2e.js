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
const glob = require('glob');
const colors = require('colors');
const async = require('async');

const doNotUse = [
  'bakery-sso.feature',
  'cloud-integration.feature'
];

let aqTestArgs = process.argv.slice(2);


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

  console.log('Running features: '.cyan, featureFiles.join(', '));
  async.each(featureFiles, (featureFile, cb) => {
    console.log(`Starting ${featureFile}`.gray);
    output[featureFile] = {
      err: false
    };

    exec(`node_modules/.bin/aqtest test/e2e/features/${featureFile} ${aqTestArgs.join(' ')}`, {
      cwd: path.join(__dirname, '..'),
      env: Object.assign(process.env, {CONFIG: process.env.CONFIG || 'test/e2e/aqtestfile.js'}),
      maxBuffer: 400 * 1024
    }, (err, log) => {
      console.log(`Completed ${featureFile}`.gray);
      output[featureFile].err = err;
      output[featureFile].log = log;
      cb(null); // let other tests run, even if error
    });
  }, err => {
    if (err) {
      throw err;
    }

    // log all output
    featureFiles.forEach(k => console.log(k.cyan, output[k].log, output[k].err));

    // throw error if there was any error
    if (featureFiles.find(f => output[f].err)) {
      throw `The following features failed: ${featureFiles.filter(f => output[f].err).join(', ')}`.red;
    } else {
      console.log('All E2E tests successful'.green);
    }
  });
});

