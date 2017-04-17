@JobDetail
Feature: Pipelines Job Summary
  As an Acquia Pipelines user
  I want to have the details of the job that updates realtime so that I can monitor my application build

  Background:
    Given I visit the |*PIPELINES_URL|
    And I have navigated to |*mock-header| page
    And I enter |*jobs-yml-file-name| in the |*header-value| field
    And I click on the |*save| button
    And I wait 5 seconds for page to navigate
    And I enter |*app-id| in the |*app-input| field
    And I click on the |*sign-in| button
    And I wait 10 seconds for logging in
    Then I should see the |app-jobs| list

  @JobList_JobDetails_Summary
  Scenario: Check the latest job details
    When on the |*jobs-list| page
    And I click on the first job id in the "Build" column from the list of jobs displayed
    And I should navigate to the |*job-detail| page
    And I wait 10 seconds for the job details to load
    And And I should see a |*alert-message| with |*success-message|
    And I click on the |*jobs| link
    Then I should see the |app-jobs| list

  @JobList_LatestJobDetails
  Scenario: Check the latest job details
    When on the |*jobs-list| page
    And I click on the first job id in the "Build" column from the list of jobs displayed
    And I should navigate to the |*job-detail| page
    And I click on the |*jobs| link
    Then I should see the |app-jobs| list
