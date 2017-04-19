const AWS = require('aws-sdk');
const fs = require('fs');
const path = require('path');
const colors = require('colors');
const glob = require('glob');

// build folder
const buildFolder = '/tmp/build/';

// cloudFront prefix url
const cloudFront = process.argv.slice(2)[0];
const cloudFrontDistribution = process.argv.slice(2)[1];

// regular expression for asset files
const assetRegEx = /^((styles|main|vendor|inline))(\.[0-9a-z]+)?(\.bundle\.(js|css|map))$/g;

// load credentials and set region
AWS.config.update({
  accessKeyId: process.env.PIPELINES_S3_KEY_ID,
  secretAccessKey: process.env.PIPELINES_S3_SECRET,
  region: 'us-east-1'
});

// create S3 service object
const s3 = new AWS.S3();

// create CloudFront service object
const cloudfront = new AWS.CloudFront();

// read build directory
// upload files to S3
glob(`${buildFolder}**`, {nodir: true}, function (err, files) {

  if (err) {
    console.error(err);
    process.exit(1);
  }

  // remove prefix
  files = files.map((file) => {
    return file.match(/(tmp\/build\/)(.*)/)[2];
  });

  // Upload files
  // Create invalidation for index.html
  Promise.all(files.map(file => uploadFile(file)))
  .then(() => {
    console.log(`All dist files uploaded`.green);
    return invalidateFile('/index.html');
  })
  .then(() => {
    console.log(`Invalidation created`.green);
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
  console.log(`uploading asset : `.gray + file);
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

    // disable cache for index.html
    if (file == 'index.html') {
      uploadParams.CacheControl = 'max-age=0';
    }

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
  return directory+file;
}

/**
 * Get ContentType for given file
 * @param  {String} file
 * @return {String}
 */
function getContentType(file) {

  let contentType = '';
  let fn = file.toLowerCase();

  if (fn.indexOf('.js') >= 0) {
    contentType = 'text/x-javascript';
  } else if (fn.indexOf('.css') >= 0) {
    contentType = 'text/css';
  } else if (fn.indexOf('.html') >= 0) {
    contentType = 'text/html';
  } else if (fn.indexOf('.png') >= 0) {
    contentType = 'image/png';
  } else if (fn.indexOf('.jpg') >= 0) {
    contentType = 'image/jpg';
  } else if (fn.indexOf('.svg') >= 0) {
    contentType = 'image/svg+xml';
  } else {
    contentType = 'application/octet-stream';
  }

  return contentType;
}

/**
 * Create an invalidation for given file
 * @param  file
 * @return {Promise}
 */
function invalidateFile(file) {
  console.log(`Creating invalidation for : `.gray + `${file}`)
  return new Promise((resolve, reject) => {
    const params = {
      DistributionId: cloudFrontDistribution, /* required */
      InvalidationBatch: { /* required */
        CallerReference: `${Date.now()}`, /* required */
        Paths: { /* required */
          Quantity: 1, /* required */
          Items: [
            file,
          ]
        }
      }
    };

    cloudfront.createInvalidation(params, function(err, data) {
      if (err) {
        reject(err);
      } else {
        resolve(data);
      }
    });
  });
}
