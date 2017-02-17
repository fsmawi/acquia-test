Feature: Pipelines Job Details
  As an Acquia Pipelines user
  I want to have the details of the job that updates realtime so that I can monitor my application build

  Background:
    Given I have navigated to "/auth/tokens"
    When I enter APP_ID "d16ebf9e-2cb0-59d4-1d91-4c6a1f360af8"
    And API_TOKEN "env:n3token"
    And API_SECRET "env:n3secret"
    And I Click on "SignIn" Button
    Then I should see the jobs-list page with an alert

  @JobDetail_CheckDifferentAlertStatuses
  Scenario Outline: Check the navigation to job details screen for jobs with different status messages
    Given jobs yml file "jobs.yml"
    When on the jobs-list page
    And I click on the job id in the "Build" column for a job which has the status as <statusIcon>
    Then I should see job-details screen with status message shown in the alert

    Examples:
      | statusIcon             |
      | timer                  |
      | state__danger          |
      | spin-reverse           |
      | state__success--circle |

  @JobDetail_AlertSummary
  Scenario: Check the status of the job is displayed as an alert
    Given jobs yml file "jobs.yml"
    When on the jobs-list page
    And I click on any job id in the "Build" column from the list of jobs displayed
    Then I can see an alert showing the status of the job and message

  @JobDetail_BackButton
  Scenario: Check the 'Jobs' button is displayed and working
    Given jobs yml file "jobs.yml"
    When on the jobs-list page
    And I click on any job id in the "Build" column from the list of jobs displayed
    Then I should see the "Jobs" button
    When I click on the button
    Then I should be navigated to jobs-list page

  @pending
  @JobDetail_CheckSummaryInfo
  Scenario: Check the Job details are displayed
    Given jobs yml file "jobs.yml"
    When on the jobs-list page
    And I click on any job id in the "Build" column from the list of jobs displayed
    Then I should see the details of the job

  @JobDetail_CheckLogs
  Scenario: Check the logs are displayed
    Given jobs yml file "jobs.yml"
    When on the jobs-list page
    And I click on the job with jobid "34b06147"
    Then I should see the logs for the job

  @JobDetail_CheckInProgressBar
  Scenario: Check the progress bar is displayed when the job is unfinished
    Given jobs yml file "jobs.yml"
    When on the jobs-list page
    And I click on the job id in the "Build" column which is not yet finished
    Then I should see the progress bar below the job details

  @JobDetail_CheckNoLogsMessage
  Scenario: Check appropriate message is shown when the job has no logs
    Given jobs yml file "jobs.yml"
    When on the jobs-list page
    And I click on the job "8f0c38d5" that does not have logs
    Then I should be shown appropriate message about the empty logs
