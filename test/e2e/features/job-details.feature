@JobDetail
Feature: Pipelines Job Details
  As an Acquia Pipelines user
  I want to have the details of the job that updates realtime so that I can monitor my application build

  Background:
    Given I visit the |*PIPELINES_URL|
    And I have navigated to |*mock-header| page
    And I enter |*jobs-yml-file-name| in the |*header-value| field
    And I click on the |*save| button
    And I should be navigated to |*pipelines-unauthenticated-url|
    And I enter |*app-id| in the |*app-input| field
    And I click on the |*sign-in| button
    And I wait 10 seconds for logging in
    Then I should see the |app-jobs| list

  @JobDetail_CheckDifferentAlertStatuses
  Scenario Outline: Check the navigation to job details screen for jobs with different status messages
    When on the |*jobs-list| page
    And I click on the job id in the "Build" column for a job which has the status as <statusIcon>
    Then I should see |*job-detail| screen with status message shown in the alert

    Examples:
      | statusIcon             |
      | timer                  |
      | state__danger          |
      | spin-reverse           |
      | state__success--circle |

  @JobDetail_AlertSummary
  Scenario: Check the status of the job is displayed as an alert
    When on the |*jobs-list| page
    And I click on the first job id in the "Build" column from the list of jobs displayed
    And I should navigate to the |*job-detail| page
    Then I can see an alert showing the status of the job and message

  @JobDetail_BackButton
  Scenario: Check the 'Jobs' link is displayed and it links to jobs-list page
    When on the |*jobs-list| page
    And I click on the first job id in the "Build" column from the list of jobs displayed
    And I should navigate to the |*job-detail| page
    And I click on the |*jobs| link
    Then I should see the |app-jobs| list

  @pending
  @JobDetail_CheckSummaryInfo
  Scenario: Check the Job details are displayed
    When on the |*jobs-list| page
    And I click on the first job id in the "Build" column from the list of jobs displayed
    Then I should see the details of the job

  @JobDetail_CheckLogs
  Scenario: Check the logs are displayed
    When on the |*jobs-list| page
    And I click on the job with jobid "34b06147"
    Then I should see the |*job-logs| for the job

  @JobDetail_CheckInProgressBar
  Scenario: Check the progress bar is displayed when the job is unfinished
    When on the |*jobs-list| page
    And I click on the job id in the "Build" column which is not yet finished
    Then I should see the |*progress-bar| below the job details

  @JobDetail_CheckNoLogsMessage
  Scenario: Check appropriate message is shown when the job has no logs
    When on the |*jobs-list| page
    And I click on the job "8f0c38d5" that does not have logs
    Then I should be shown appropriate |*empty-logs| message

  @JobDetail_CheckJobDetailCountUp
  Scenario: Check appropriate count up time is shown for the job which is in progress
    Given I have navigated to |*mock-header| page
    And I enter |job-details-countup.yml| in the |*header-value| field
    And I click on the |*save| button
    And I should be navigated to |*pipelines-unauthenticated-url|
    And I enter |*app-id| in the |*app-input| field
    And I click on the |*sign-in| button
    And I wait 10 seconds for logging in
    When on the |*jobs-list| page
    And I click on the job with jobid "34b06147"
    Then I should be shown appropriate |*count-up-time| in summary table

