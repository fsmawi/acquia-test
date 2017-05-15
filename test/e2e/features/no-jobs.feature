@NoJobs
Feature: Pipelines Jobs List
  As an Acquia Pipelines user
  If there are no jobs then I could able to start a new job

  Background:
    Given I visit the |*PIPELINES_URL|
    And I have navigated to |*mock-header| page
    And I enter |*no-jobs-yml-file-name| in the |*header-value| field
    And I click on the |*save| button
    And I should be navigated to |*pipelines-unauthenticated-url|
    And I enter |*app-id| in the |*app-input| field
    And I click on the |*sign-in| button
    And I wait 10 seconds for logging in
    And I should see a |*get-started-header| with |*get-started-message|

  @NoJobs_StartJobWhenNoJobs
  Scenario: Able to start a job when no jobs exist
    When I click on the |*start-a-job| button
    And I should see a |*how-to-start-job-dialog| with |*header-start-job-message|
    And I should see non empty |*branch-list| list
    And I enter |master| in the |*branch-filter-text| field
    Then I should see in the |*branch-list| list only items that contain "master" keyword
    And I click on the |*master-radio| button
    And I click on the |*start| button
    Then I should see a |*flash-message| contains |*success-message|

  @NoJobs_ValidateStartJobContent
  Scenario: validate the content inside Start a Job dialog window
    When I click on the |*start-a-job| button
    And I should see a |*how-to-start-job-dialog| with |*header-start-job-message|
    And I click on the |*learn-how-to-start-job| button
    And I should see a |*how-to-start-job-dialog| with |*header-how-to-start-job-message|
    And I should see a |*actions-to-start-a-job-header| contains |*actions-to-start-a-job-header-text|
    And I should see a |*list-of-actions-to-start-a-job| contains |*add-file-to-repository|
    And I should see a |*list-of-actions-to-start-a-job| contains |*create-branch|
    And I should see a |*list-of-actions-to-start-a-job| contains |*create-pull-request|
    And I should see a |*list-of-actions-to-start-a-job| contains |*use-cli|
    And I click on the |*learn-more| link
    And I should see |*acquia-docs-using-pipelines| window opened
    And I click on the |*learn-more-about-using-git| link
    And I should see |*acquia-docs-using-git| window opened

  @NoJobs_ValidateNoJobsContentAndLinks
  Scenario: validate the content links inside no-jobs page
    Then I should see a |*no-jobs-card| contains |*acquia-pipelines-description|
    And I should see a |*run-pipelines-header| with |*run-pipelines-header-text|
    And I should see the |*pipelines-logo|
    And I should see the |*start-a-job| button
    And I should see the |*select-source-header| with |*select-source-header-text|
    And I should see a |*acquia-git-image| element contains |*acquia-git-image-file-name|
    And I should see a |*git-image| element contains |*git-image-file-name|
    And I should see the |*select-source-link|
    And I should see a |*deploy-automatically-header| with |*deploy-automatically-header-text|
    And I should see a |*acquia-cloud-image| element contains |*acquia-cloud-image-file-name|
    And I should see the |*view-environments| link
    And I click on the |*learn-more-no-jobs| link
    And I should see |*acquia-docs-pipelines| window opened
