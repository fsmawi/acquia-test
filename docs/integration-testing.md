#Integration Testing Pipelines

## Pre-requisites

You will need to install supertest, mocha, querystring and chai for writing and running the integration tests. 
These packages are part of pipelines-ui. So, upon building pipeline-ui package, these pre-requisite packages
will also be installed automatically.

From pipelines-ui location run the following command in a terminal

Example: 

    npm install

Alternatively to install mocha, supertest, querystring and chai seperately run the following commands from the terminal

    npm install mocha should supertest
    npm install chai
    npm install querystring
    

## Writing the tests
Tests are written using mocha test framework [To define the tests], supertest [to call the api] and chai [to assert the response]

Here are the steps specific to creating the pieplines-api integration tests using supertest

###Step 1
 Describe the test using 'describe' and create the test using 'it' inside it and then Provide the 'PIPELINES_API_URI' to supertest as follows...
 
 ```js
 
const supertest = require('supertest');
const qs = require('querystring');

describe('Pipelines API /api/v1/ci/jobs', function () {
  const token = process.env.N3_KEY;
  const secret = process.env.N3_SECRET;
  const endpoint = 'https://cloud.acquia.com';
  this.timeout(10000);

  it('should return an array of job objects', () => {
    const params = '?' + qs.stringify({applications: 'fbcd8f1f-4620-4bd6-9b60-f8d9d0f74fd0'});
    return supertest(process.env.PIPELINES_API_URI)
  });
 });
 ```
###Step 2
Define the route [path to api to be called which is defined in  PIPELINES_API_URI server]

For example: 
```js
  
const supertest = require('supertest');
const qs = require('querystring');

describe('Pipelines API /api/v1/ci/jobs', function () {
  const token = process.env.N3_KEY;
  const secret = process.env.N3_SECRET;
  const endpoint = 'https://cloud.acquia.com';
  const route = '/api/v1/ci/jobs';
  this.timeout(10000);

  it('should return an array of job objects', () => {
      const params = '?' + qs.stringify({applications: 'fbcd8f1f-4620-4bd6-9b60-f8d9d0f74fd0'});
      return supertest(process.env.PIPELINES_API_URI)
        .get(route)    
  });
});
  ```
###Step 3
Form the query string with application id

For example: 
```js
  
const supertest = require('supertest');
const qs = require('querystring');

describe('Pipelines API /api/v1/ci/jobs', function () {
  const token = process.env.N3_KEY;
  const secret = process.env.N3_SECRET;
  const endpoint = 'https://cloud.acquia.com';
  const route = '/api/v1/ci/jobs';
  this.timeout(10000);

  it('should return an array of job objects', () => {
     const params = '?' + qs.stringify({applications: 'fbcd8f1f-4620-4bd6-9b60-f8d9d0f74fd0'});
     return supertest(process.env.PIPELINES_API_URI)
       .get(route+params)
  });
});
       
``` 
###Step 4 
Set the following headers  
```js

const supertest = require('supertest');
const qs = require('querystring');

describe('Pipelines API /api/v1/ci/jobs', function () {
  const token = process.env.N3_KEY;
  const secret = process.env.N3_SECRET;
  const endpoint = 'https://cloud.acquia.com';
  const route = '/api/v1/ci/jobs';
  this.timeout(10000);

  it('should return an array of job objects', () => {
      const params = '?' + qs.stringify({applications: 'fbcd8f1f-4620-4bd6-9b60-f8d9d0f74fd0'});
      return supertest(process.env.PIPELINES_API_URI)
        .get(route+params)
	      .set('X-ACQUIA-PIPELINES_N3_ENDPOINT','')
	      .set('X-ACQUIA-PIPELINES_N3_KEY', '')
	      .set('X-ACQUIA-PIPELINES_N3_SECRET', '')
	});
});
   
  ```

###Step 5
Do the assertion using chai.expect to verify status code, content-type, content inside response body etc..

```js
 
const supertest = require('supertest');
const qs = require('querystring');
const expect = require('chai').expect;
 
describe('Pipelines API /api/v1/ci/jobs', function () {
  const token = process.env.N3_KEY;
  const secret = process.env.N3_SECRET;
  const endpoint = 'https://cloud.acquia.com';
  const route = '/api/v1/ci/jobs';
  this.timeout(10000);

  it('should return an array of job objects', () => {
      const params = '?' + qs.stringify({applications: 'fbcd8f1f-4620-4bd6-9b60-f8d9d0f74fd0'});
      return supertest(process.env.PIPELINES_API_URI)
        .get(route+params)
	      .set('X-ACQUIA-PIPELINES_N3_ENDPOINT','')
	      .set('X-ACQUIA-PIPELINES_N3_KEY', '')
	      .set('X-ACQUIA-PIPELINES_N3_SECRET', '')
        .expect(200)
        .expect('Content-Type', /json/)
        .then(res => {
        expect(res.body).to.be.instanceof(Array);
        // Validating the first log json object in the array
        expect(res.body[0]).to.exist;
        expect(res.body[0].timestamp).to.exist;
	 });
 });
 
```
Here are the links for supertest, mocha and chai to know more about these testing frameworks
> https://github.com/visionmedia/supertest

Link to mocha test framework which describes how to create and run the tests using mocha
> https://mochajs.org/

Link to chai to assert the response content
> http://chaijs.com/

Current pipelines-api integration tests covering the following scenarios
validation of 200 [success], 403 [missing headers] and 404[application id not found]


## Running the tests
For running these, you will need PIPELINES_API_URI, its Access Key and Secret set as N3_KEY and N3_SECRET respectively.
They will run against api.pipelines.acquia.com

       
#####Step#1: Open a terminal and set the following environment properties
 1. PIPELINES_API_URI
 2. N3_KEY
 3. N3_SECRET
 
environment variables should be set with respective values before running the test(s). User should set these variables as follows
usage [from windows DOS prompt]:

    set PIPELINES_API_URI=api.pipelines.acquia.com 
    set N3_KEY=987c2aae-c002-1234-e78d-456d12345e83
    set N3_SECRET=+aghuJxxxkUI/A15memICVovRfOTTGpt1WDTT/8JWAQ=

usage [from bash shell]:
    
    export PIPELINES_API_URI=api.pipelines.acquia.com 
    export N3_KEY=987c2aae-c002-1234-e78d-456d12345e83
    export N3_SECRET=+aghuJxxxkUI/A15memICVovRfOTTGpt1WDTT/8JWAQ=

Alternately, you can pass these variables inline node/mocha command as follows
usage:

       PIPELINES_API_URI="api.pipelines.acquia.com" N3_KEY=987c2aae-c002-1234-e78d-456d12345e83        
       N3_SECRET=+aghuJxxxkUI/A15memICVovRfOTTGpt1WDTT/8JWAQ= node ./node_modules/.bin/mocha test/integration --recursive --watch 
       
#####Step#2: switch to 'pipeline-ui' folder from the terminal and run the below command
	   
     node ./node_modules/.bin/mocha test/integration --recursive --watch
     
  Alternatively, if you have mocha installed globally, you can run the below command
  
     mocha test/integration --recursive --watch

TroubleShoot:
1. Here are the errors that may occur while running the tests and its resolution

	Error=>  TypeError: Cannot read property 'address' of undefined
	Resolution => you must set the PIPELINES_API_URI environment property before running the tests
