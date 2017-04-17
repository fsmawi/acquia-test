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

  @JobList_LatestJob_JobSummary
  Scenario: Check the latest job summary on job list
    When on the |*jobs-list-title| page
    And I click on the first job id in the "Build" column from the list of jobs displayed
    And I should navigate to the |*job-detail| page
    And I click on the |*pipelines| link
    And I should see the |app-jobs| list
    And I should see the |*deployment-link-value|
    And I should see the |*commit-value|
    And I should see the |*started-at-value|
    And I should see the |*trigger-value|
    And I should see the |*pull-request-value|
    And I should see the |*source-branch-value|
    And I should see the |*target-branch-value|
    Then I should see the |*requested-by-value|

  @JobList_JobDetails_JobSummary
  Scenario: Check the job summary on job details
    When on the |*jobs-list-title| page
    And I click on the first job id in the "Build" column from the list of jobs displayed
    And I should navigate to the |*job-detail| page
    And I should see the |*duration-finished-value|
    And I should see the |*started-at-value|
    And I should see the |*trigger-value|
    And I should see the |*branch-value|
    And I should see the |*requested-by-value|
    And I click on the |*pipelines| link
    Then I should see the |app-jobs| list
