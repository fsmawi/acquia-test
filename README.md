# Pipelines UI

The Pipelines UI serves as the interface for end clients to interact with pipelines service. For a full list of documentation and architecture, visit the [docs](./docs).

### Quick Start

1. clone the master branch
2. `npm install`
3. `npm start`
4. In another terminal execute `npm run mock:jobs`
5. In browser, visit http://localhost:4200/jobs/app-id


#### Errors that may occur in installation:

`npm install` command may fail with the following error message when using windows:

` MSBUILD : error MSB4132: The tools version "2.0" is unrecognized. Available tools versions are "4.0" `

Set the following option and run npm install again...

`npm config set msvs_version 2012 --global && npm install`

### Development server

Run `npm start` for a dev server. Navigate to `http://localhost:4200/`. The app will automatically reload if you change any of the source files.
You will also have to start a [mock API using merver](https://github.com/raghunat/merver): `npm run mock:jobs` for example, or `npm run mock <path to merver yml>`.

### Code scaffolding

Run `ng generate component component-name` to generate a new component. You can also use `ng generate directive/pipe/service/class/module`.

### Build

Run `ng build` to build the project. The build artifacts will be stored in the `dist/` directory. Use the `--prod` flag for a production build.

### Linting

Run `npm run lint` to lint the typescript code using tslint.

### Running unit tests

Run `npm test` to execute the unit tests via [Karma](https://karma-runner.github.io).

### Running API integration tests

Run `npm run integration` for the full integration suite.
Run `npm run integration:acceptance` for the acceptance integration suite.

### Running end-to-end tests

Run `npm run e2e` for the full e2e suite.
Run `npm run e2e:acceptance` for the acceptance e2e suite.

### Further help

To get more help on the `angular-cli` use `ng help` or go check out the [Angular-CLI README](https://github.com/angular/angular-cli/blob/master/README.md).
