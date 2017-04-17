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
    And I should see |*deployment-link-value| |*inner-text| containing |*latest-job-details-deployment|
    And I should see |*commit-value| |*inner-text| containing |*latest-job-details-commit|
    And I should see |*started-at-value| |*inner-text| containing |*latest-job-details-started-at|
    And I should see |*trigger-value| |*inner-text| containing |*latest-job-details-trigger|
    And I should see |*pull-request-value| |*inner-text| containing |*latest-job-details-pr|
    And I should see |*source-branch-value| |*inner-text| containing |*latest-job-details-source-branch|
    And I should see |*target-branch-value| |*inner-text| containing |*latest-job-details-target-branch|
    Then I should see |*requested-by-value| |*inner-text| containing |*latest-job-details-requested-by|

  @JobList_JobDetails_JobSummary
  Scenario: Check the job summary on job details
    When on the |*jobs-list-title| page
    And I click on the first job id in the "Build" column from the list of jobs displayed
    And I should navigate to the |*job-detail| page
    And I should see |*duration-finished-value| |*inner-text| containing |*job-details-duration|
    And I should see |*started-at-value| |*inner-text| containing |*job-details-started-at|
    And I should see |*trigger-value| |*inner-text| containing |*job-details-trigger|
    And I should see |*branch-value| |*inner-text| containing |*job-details-source-branch|
    And I should see |*requested-by-value| |*inner-text| containing |*job-details-requested-by|
    And I click on the |*pipelines| link
    Then I should see the |app-jobs| list
