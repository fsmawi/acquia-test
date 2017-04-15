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

  @JobList_ValidateStartJobContent
  Scenario: validate the content inside Start a Job dialog window
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
    Then I should see a |*type-branch-name-label| contains |*type-branch-name-label-text|
    And I should see a |*actions-to-start-a-job-header| contains |*actions-to-start-a-job-header-text|
    And I should see a |*list-of-actions-to-start-a-job| contains |*add-file-to-repository|
    And I should see a |*list-of-actions-to-start-a-job| contains |*create-branch|
    And I should see a |*list-of-actions-to-start-a-job| contains |*create-pull-request|
    And I should see a |*list-of-actions-to-start-a-job| contains |*use-cli|
    And I click on the |*learn-more| link
    And I should see |*acquia-docs-using-pipelines| window opened
    And I click on the |*learn-more-about-using-git| link
    And I should see |*acquia-docs-using-git| window opened

