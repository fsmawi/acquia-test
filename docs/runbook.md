# Runbook
This page includes all steps for automated or manual deployment of the application.

## Context
The application is a HTML/CSS/JS only application, and therefore only needs a webhost to serve it. We use [Acquia Cloud](https://cloud.acquia.com) to host the [Acquia Pipelines UI](https://cloud.acquia.com/app/develop/applications/fbcd8f1f-4620-4bd6-9b60-f8d9d0f74fd0) application.

## Manual Deployment
1. Clone the main repo
2. Checkout the branch you want to deploy (master|staging|production)
3. `npm install` (required everytime)
4. `npm run pipelines:build:<branch-name>` This will create a `dist` folder with the bundled applciation inside
5. Clone the acquia git repo: `pipelinesui@svn-182.network.hosting.acquia.com:pipelinesui.git`
6. `cd` into that repo, and checkout the branch you want to deploy (pipelines-build-master|pipelines-build-staging|pipelines-build-production)
7. Run the following commands:
  - `cd environments && composer install && cd ..`
  - `rm -rf ./docroot`
  - `mkdir ./docroot`
  - `cp -r <path-to-your-dist-folder>/* ./docroot` (use the path to your new `dist` folder)
  - `cp .htaccess ./docroot`
  - `cp index.php ./docroot`
  - `cp -r server ./docroot`
  - `cp -r environments/vendor ./docroot/server/vendor`
8. `git add -A && git commit -m "<Branch-name> Release <version-from-package.json>"`
9. Push the acquia git repo branch `git push` this deploys all the code to the environment.

## Automated Deployment
The above steps will happen automatically from pipelines itself, using the [acquia-pipelines.yaml](../acquia-pipelines.yaml) on every commit to each stage branch. The deploy will only be successful if the job passes.

## Releases
When preparing a release from the master branch, increment the semver one patch level. That will then transcend through to production:

1. Semver Master branch's package.json
2. Review pipelines build for lint/unit tests/integration tests/e2e tests
3. Perform sanity/exploratory tests on master environment in cloud
4. Checkout staging branch, and `git merge master -X theirs`
5. Review pipelines build for lint/unit tests/integration tests/e2e tests
6. Perform sanity/exploratory tests on staging environment in cloud
7. Checkout production branch, and `git merge staging -X theirs`
8. Review pipelines build for lint/unit tests/integration tests/e2e tests/acceptance tests
9. Perform sanity/exploratory tests on production environment in cloud
