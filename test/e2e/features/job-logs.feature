@JobLog
Feature: Pipelines Job log
  As an Acquia Pipelines user
  I want to have the job logs that shows the build steps and updates realtime

@JobLog_ValidateBuildStepsAndScroller
  Scenario: Check the build steps of a job and functionality of the Scroller
    Given I visit the |*PIPELINES_URL|
    And I have navigated to |*mock-header| page
    And I enter |*log-chunks-yml-file-name| in the |*header-value| field
    And I click on the |*save| button
    And I wait 5 seconds for page to navigate
    And I enter |*app-id| in the |*app-input| field
    And I click on the |*sign-in| button
    And I wait 10 seconds for logging in
    And I should see the |app-jobs| list
    When on the |*jobs-list| page
    And I click on the job with jobid "2cb7d930"
    And I wait 5 seconds for page to navigate
    And I should see a |*job-logs| contains |*job-log-task-started|
    And I should see a |*job-logs| contains |*job-log-step-install|
    And I should see a |*job-logs| contains |*job-log-step-lint|
    And I should see a |*job-logs| contains |*job-log-step-test|
    And I should see a |*job-logs| contains |*job-log-step-build|
    When I click on the |*scroll-to-bottom| button
    Then I should see a |vertical| scroller move |down| by at least |10|

