# Mock API Usage
A simple sequence mock API Server. This is intended to make functional UI tests easier for SPA applications

This Mock will serve API endpoints according to a YAML definition. Every HTTP REQUEST should use the header `X-ACQUIA-PIPELINES-N3-APIFILE` to indicate which yaml file to use. Example: `X-ACQUIA-PIPELINES-N3-APIFILE: 'jobs.yml'`.

##.htaccess configuration

Add the rule below just after `RewriteEngine on` in the `.htaccess` file

`RewriteRule ^mock/api/(.*)$ server/api/mock.php?q=$1 [QSA,NC]`

This rule will let the Apache server :
  - Capture all routes and pass them as parameter (q) to the mock.php script.
  - Rename the Path url to the mock script from `server/api` to `mock/api`

## Sequenced responses

Using this mock server you can have sequenced responses for your use cases. For example:

1. POST /login fails with 403 (assumed bad credentials)
2. POST /login succeeds the second time with 200 (assumed good credentials)
3. GET /user/1 gives back a user with name = "Laura Johnson"
4. PUT /user/1 gives back a user with name = "Laura Raghunath"
5. GET /user/1 gives back a user with name = "Laura Raghunath"

Example YAML for above:
```yaml
---
routes:
  "/login":
    POST:
      responses:
      - response:
          message: Invalid Password
        status: 403
      - response:
          id: 1
          name: Laura Johnson
        status: 200
  "/user/1":
    GET:
      response:
        name: Laura Johnson
    PUT:
      response:
        name: Laura Raghunath
    GET:
      response:
        name: Laura Raghunath
```

This feature is based on cookies, so in order to reset all test scenarios you should clear the `pipeline` cookie or just visite the API home page.

For example `http://pipelines123.network.acquia-sites.com/mock/api/`
