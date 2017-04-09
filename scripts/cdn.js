const AWS = require('aws-sdk');
const fs = require('fs');
const path = require('path');

// build folder
const buildFolder = '/tmp/build/';

// cloudFront prefix url
const cloudFront = process.argv.slice(2)[0];

// regular expression for asset files
const assetRegEx = /^((styles|main|vendor|inline))(\.[0-9a-z]+)?(\.bundle\.(js|css|map))$/g;

const filePath = buildFolder + 'index.html';
var fileBody = fs.readFileSync(filePath, 'utf8');

// load credentials and set region
AWS.config.update({
  accessKeyId: process.env.PIPELINES_S3_KEY_ID,
  secretAccessKey: process.env.PIPELINES_S3_SECRET,
  region: 'us-east-1'
});

// create S3 service object
const s3 = new AWS.S3();

// read build directory
// find matched asset files and upload them to S3
// replace asset urls in index.html with CDN versions
fs.readdir(buildFolder, (err, files) => {

  if (err) {
    console.error(err);
    process.exit(1);
  }

  files = files.filter(file => file.match(assetRegEx));

  // Upload files
  Promise.all(files.map(file => uploadFile(file)))
  .then(() => {

    files.forEach(file => {
      fileBody = fileBody.replace('="' + file + '"', '="' + cloudFront + file + '"');
    });

    // Update index.html
    fs.writeFile(filePath, fileBody, 'utf8', function(err) {
      if (err) {
        return Promise.reject(err);
      }
    });
  })
  .catch((err) => {
    console.error(err);
    process.exit(1);
  });
});

/**
 * Upload a single file
 * @param  file
 * @return Promise
 */
function uploadFile(file) {
  console.log('uploading asset : ' + file);
  return new Promise((resolve, reject) => {
    const fileStream = fs.createReadStream(buildFolder + file);
    fileStream.on('error', (err) => {
      return reject(err);
    });

    let uploadParams = {
      Bucket: 'pipelines-ux',
      Key: getKey(file),
      Body: fileStream,
      ContentType: getContentType(file)
    };

    s3.upload (uploadParams, function (err, data) {
      if (err) {
        return reject(err);
      } if (data) {
        return resolve(data);
      }
    });
  });
}

/**
 * Get Key string according to current environment
 * @param  file
 * @return {String}
 */
function getKey(file) {
  let directory = '';
  switch (process.env.PIPELINE_VCS_PATH) {
    case 'production':
      directory = 'production/';
      break;
    case 'staging':
      directory = 'staging/';
      break;
    case 'master':
    default:
      directory = 'dev/';
      break;
  }
  return path.join(directory, path.basename(file));
}

/**
 * Get ContentType for given file
 * @param  {String} file
 * @return {String}
 */
function getContentType(file) {

  let contentType = 'application/octet-stream';
  var fn = file.toLowerCase();

  if (fn.indexOf('.js') >= 0) {
    contentType = 'text/x-javascript';
  }

  if (fn.indexOf('.css') >= 0) {
    contentType = 'text/css';
  }

  return contentType;
}
