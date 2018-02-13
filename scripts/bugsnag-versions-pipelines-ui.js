const request = require('superagent');
const colors = require('colors');
const glob = require('glob');
const upload = require('bugsnag-sourcemaps').upload;
const path = require('path');
const fs = require('fs');
const URL = require('url').URL;

const tmpUrl = new URL(process.env.PIPELINES_PRODUCTION_URL);
const pipelinesUrl = `${tmpUrl.protocol}//${tmpUrl.host}`;

const packageJson = require(__dirname + '/../package.json');

// build folder
const buildFolder = '/tmp/build/';


// Send Deploy information to bugsnag
request
  .post(`https://notify.bugsnag.com/deploy`)
  .send({
    apiKey: process.env.PIPELINES_BUGSNAG_API_KEY,
    appVersion: packageJson.version
  })
  .end((err, res) => {
    if (err) {
      console.log(`Bugsnag : unable to send deployment information`.red);
      throw err;
    }
    console.log(`Bugsnag : deployment information sent ${packageJson.version}`.green);
    uploadSourceMapFiles();
  });

// Sends source map files
function uploadSourceMapFiles() {
  console.log('Sending source map files to Bugsnag..');
  glob(`${buildFolder}*.js`, {nodir: true}, function (err, files) {
    if (err) {
      console.error(err);
      process.exit(1);
    }

    // Remove prefix
    files = files.map((file) => {
      return file.match(/(tmp\/build\/)(.*)/)[2];
    });

    // Upload files
    files.forEach(function (file) {
      let fileMap = path.join(buildFolder, `${file}.map`);
      if (!fs.existsSync(fileMap)) {
        console.log(`${fileMap} don't exists`);
      } else {
        upload({
          apiKey: process.env.PIPELINES_BUGSNAG_API_KEY,
          appVersion: packageJson.version,
          minifiedUrl: `${pipelinesUrl}/${file}`,
          sourceMap: fileMap,
          overwrite: true
        }, function(err) {
          if (err) {
            console.error(err);
          } else {
            console.log(`${fileMap} uploaded.`);
          }
        });
      }
    });
  });
}


