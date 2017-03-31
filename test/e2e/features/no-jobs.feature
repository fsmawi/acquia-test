@JobList
Feature: Pipelines Jobs List
  As an Acquia Pipelines user
  If there are no jobs then I could able to start a new job

  @JobList_StartJobWhenNoJobs
  Scenario: Able to start a job when no jobs exist
    Given I visit the |*PIPELINES_URL|
    And I have navigated to |*mock-header| page
    And I enter |*no-jobs-yml-file-name| in the |*header-value| field
    And I click on the |*save| button
    And I should be navigated to |*pipelines-unauthenticated-url|
    And I enter |*app-id| in the |*app-input| field
    And I click on the |*sign-in| button
    And I wait 10 seconds for logging in
    And I should see a |*get-started-header| with |*get-started-message|
    And I click on the |*start-a-job| button
    And I should see a |*how-to-start-job-dialog| with |*header-message|
    And I enter |*branch-name| in the |*branch-input| field
    And I click on the |*branch-suggestion| list item
    And I click on the |*start| button
    Then I should see a |*flash-message| contains |*success-message|
