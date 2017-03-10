# Unit Testing Pipelines

## Prerequisites

You will need to install following prerequisite packages for writing and running the unit tests.
These packages are part of pipelines-ui project. So, upon building pipeline-ui package, these
prerequisite packages will also be installed automatically.

* Jasmine
* Karma
* Phantom-js
* Istanbul

Jasmine is the most popular testing framework in the Angular community. This is the core framework that we will write our unit tests with.

Karma is a test automation tool for controlling the execution of our tests and what browser to perform them under. It also allows us to generate various reports on the results. For one or two tests this may seem like overkill, but as an application grows larger and the number of units to test grows,it is important to organize, execute and report on tests in an efficient manner. Karma is library agnostic so we could use other testing frameworks in combination with other tools (like code coverage reports, spy testing, e2e, etc.).
In order to test our Angular application we must create an environment for it to run in. We could use a browser like Chrome or Firefox to accomplish this (Karma supports in-browser testing), or we could use a browser-less environment to test our application, which can offer us greater control over automating certain tasks and managing our testing workflow.

PhantomJS provides a JavaScript API that allows us to create a headless DOM instance which can be used to bootstrap our Angular application. Then, using that DOM instance that is running our Angular application, we can run our tests.

Istanbul is used by Karma to generate code coverage reports, which tells us the overall percentage of our application being tested. This is a great way to track which components/services/pipes/etc. have tests written and which don't. We can get some useful insight into how much of the application is being tested and where.

From pipelines-ui location run the following command in a terminal to install all the pre-reuisite modules

Example:

  ```sh
   npm install
  ```

## Writing the tests

In Angular, a unit is most commonly defined as a class, pipe, component, or service. So, we write tests covering these.
Tests are written using Jasmine test framework, and are run using Karma.

Here are the steps for creating the pieplines-ui unit tests using Jasmine:

### Step 1
 '.spec.ts' files are the unit test files that karma will load and execute. The full name of these files
  are similar to other files located in the component, module, class, service, module, pipe, etc.
  For instance, our App component unit test file is named `app.component.spec.ts`.

### Step 2
 Describe the test using 'describe' and create the test using 'it' inside it. Also, we need to take help of 'TestBed' class in '@angular/core/testing' module
 to configure and initialize environment for unit testing and it also provides methods for creating components and services in unit tests.

 For Example to write unit tests for ConfirmationModalDialog component. It should be like

 ```ts
 describe('ConfirmationModalComponent', () => {
  let component: ConfirmationModalComponent;
  let fixture: ComponentFixture<ConfirmationModalComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ConfirmationModalComponent],
      providers: [{provide: ConfirmationModalService, useClass: MockConfirmationModalService}],
      imports: [ElementalModule]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(ConfirmationModalComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
    component.emitter = new EventEmitter<Boolean>();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should close the modal after confirmation', () => {
    component.confirmed();
    expect(component.modalOpen).toEqual(false);
  });

  it('should close the modal if cancelled', () => {
    component.cancelled();
    expect(component.modalOpen).toEqual(false);
  });

});
 ```

For writing unit tests for component which is associated with one or more services,
 1. We will create the mock service class as follows

  Here is an example to mock FlashMessageService

   ```ts
   class MockFlashMessage {
     showError(message: string, e: any) {
      return true;
     }

     showInfo(message: string, e: any = {}) {
      return true;
     }
   }
  ```

 2. Configure the mock class for the service in TestBed as follows

  ```ts
  TestBed.configureTestingModule({
        declarations: [
          ApplicationComponent
        ],
        providers: [
          FlashMessageService,
          MockBackend,
          {provide: FlashMessageService, useClass: MockFlashMessage},
        ]
      })
        .compileComponents();
    }));
  ```

 3. Use the mock service class to test that the component responds as expected with
    what the service has returned

  ```ts
  it('should show a not connected alert when the repo is not connected',
      fakeAsync(inject([ActivatedRoute, FlashMessageService, MockBackend], (route, flashMessage, mockBackend) => {

        setupConnections(mockBackend, {
          body: JSON.stringify({})
        });

        spyOn(flashMessage, 'showInfo');

        component.getConfigurationInfo();
        tick();
        expect(flashMessage.showInfo).toHaveBeenCalledWith('You are not connected yet');
      })));
  ```

### Step 3

###### Disabling Suites:
Suites can be disabled with the xdescribe function. These suites and any specs inside them are skipped when run and thus their results will not appear
in the results.

```ts
xdescribe("A spec", function() {
  var foo;

  beforeEach(function() {
    foo = 0;
    foo += 1;
  });

  it("is just a function, so it can contain any code", function() {
    expect(foo).toEqual(1);
  });
});
```

###### Pending test(s):
Pending specs do not run, but their names will show up in the results as pending.
Any spec declared with xit is marked as pending.
For example:

```ts
describe("A spec", function() {
  var foo;

  beforeEach(function() {
    foo = 0;
    foo += 1;
  });

  it("first test", function(){
    expect(foo).toEqual(1);
  });

  xit("is just a function, so it can contain any code", function() {
    expect(foo).toEqual(10);
  });
});
  ```

Further information related to the best practices around unit testing within Angular can be found:

* [Angular Testing Documentation](https://angular-2-training-book.rangle.io/handout/testing)
* [Intorduction to Jasmine](https://jasmine.github.io/2.0/introduction.html)
* [Karma test framework](https://karma-runner.github.io/1.0/index.html)


## Running the tests

Unit tests will run using karma. Before running the tests we need to configure which port to use to open the karma test runner in a browser and in which browser tests should run, what is the timeout value for browser loading etc. all these settings were configured in [karma.conf.js](../karma.conf.js).
You can override any of the settings in this file as per your needs.

All we have to do to run the tests is just call the following command from the terminal. By default, it will load and execute
all the files that are ended with '.spec.ts' in the project folder. This is scripted in [test.ts](../src/test.ts) file.

 ```sh
  npm test
 ```

To run only specific suite or test(s) prefix them 'f' as shown below

```
  fdescribe
  fit
```

Then run the tests as follows

```sh
 npm test
```

It will run only the suite or tests marked with 'fdescribe' or 'fit'

TroubleShoot:

1. Here are the errors that may occur while running the tests and its resolution

```sh
 pipelines-ui $ npm run test

> pipelines-ux@0.1.1 test pipelines-ui
> ng test --watch=false

09 03 2017 17:59:31.522:INFO [karma]: Karma v1.2.0 server started at http://localhost:9876/
09 03 2017 17:59:31.531:INFO [launcher]: Launching browser PhantomJS with unlimited concurrency
09 03 2017 17:59:31.548:INFO [launcher]: Starting browser PhantomJS
09 03 2017 17:59:34.788:INFO [PhantomJS 2.1.1 (Windows 8 0.0.0)]: Connected on socket /#rl5jPE62kfD5FmNwAAAA with id 52685094
09 03 2017 17:59:54.791:WARN [PhantomJS 2.1.1 (Windows 8 0.0.0)]: Disconnected (1 times), because no message in 20000 ms.
PhantomJS 2.1.1 (Windows 8 0.0.0) ERROR
  Disconnected, because no message in 20000 ms.
```

Resolution:
   Increase the timeout value in [karma.conf.js](../karma.conf.js) file by setting the following property and try again

   ```sh
      browserNoActivityTimeout: 60000
   ```
