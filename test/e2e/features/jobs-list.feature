@JobList
Feature: Pipelines Jobs List
  As an Acquia Pipelines user
  I want to have a list of jobs that updates realtime so that I can monitor my application builds

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

  @JobList_CheckAlertLastJob
  Scenario: Check the last job is displayed as an alert
    When on the |*jobs-list| page
    Then I can see the last job status displays as an alert with a status and message

  @JobList_CheckAlertJobLink
  Scenario: Check the joblink navigates to activity table
    When I click on the |*jobId-link| link
    Then I should navigate to the |*job-detail| page

  @JobList_CheckActivityCard
  Scenario: Check that the activity card is visible
    When on the |*jobs-list| page
    Then I should see the |*activity-tab|

  @JobList_CheckActivityTableHeaders
  Scenario: Check that the activity card should show the appropriate headers
    When on the |*jobs-list| page
    Then I should see the appropriate headers for the activity card

  @JobList_RestartJob @pending
  Scenario: Check that a job in a failed state should be able to be restarted from the activity card
    When on the |*jobs-list| page
    And I click on the "Restart" button in the "Actions" column
    Then I should see a new job after 10 seconds with the same branch

  @JobList_StopJob @pending
  Scenario: Check that a job in progress should be able to be stopped from the activity card
    When on the |*jobs-list| page
    And I click on the "Stop" button in the "Actions" column
    Then I should see the job status as "Job is paused"

  @JobList_CheckDetailLink
  Scenario: A job in the activity card should link to the detail page for that job
    When on the |*jobs-list| page
    And I click on the jobs link in the "Build" column
    Then I should navigate to the |*job-detail| page

  @JobList_CheckStatusIcons
  Scenario Outline: Each job should display a different icon based on it's status in the activity card
    When on the |*jobs-list| page
    Then I should see the "Status" column icon as <statusIcon> for a job with id "<jobId>"

    Examples:
      | jobId    | statusIcon             |
      | 55b06145 | state__danger          |
      | ad9aacb4 | spin-reverse           |
      | 8f0c38d5 | timer                  |
      | 34b06147 | state__success--circle |

  @JobList_CheckSummaryTable @pending
  Scenario: Check the job summary table displaying at the top of jobs-list page with details of last run job
    When on the |*jobs-list| page
    Then I should see last run job details as a summary table

  @JobList_CheckTooltips
  Scenario: Check the tooltips of the fields displayed in jobList page
    When on the |*jobs-list| page
    Then I should see |*Destination-Environment-label| |*tooltip| containing |*Destination-Environment-tooltip-text|
    Then I should see |*Commit-label| |*tooltip| containing |*Commit-tooltip-text|
    Then I should see |*Job-Duration-label| |*tooltip| containing |*Job-Duration-tooltip-text|
    Then I should see |*Started-at-label| |*tooltip| containing |*Started-at-tooltip-text|
    Then I should see |*Job-Trigger-label| |*tooltip| containing |*Job-Trigger-tooltip-text|
    Then I should see |*Pull-request-label| |*tooltip| containing |*Pull-request-tooltip-text|
    Then I should see |*Source-branch-label| |*tooltip| containing |*Source-branch-tooltip-text|
    Then I should see |*Target-branch-label| |*tooltip| containing |*Target-branch-tooltip-text|
    Then I should see |*Requested-by-label| |*tooltip| containing |*Requested-by-tooltip-text|
    Then I should see |*filter-by-status-label| |*tooltip| containing |*filter-by-status-tooltip-text|
    Then I hover on |*acquia-git-icon|
    Then I should see |*acquia-git-icon| |*tooltip| containing |*acquia-git-icon-tooltip|

  @JobList_CheckStartJob
  Scenario: Check Start Job functionality in top menu
    When on the |*jobs-list| page
    And I click on the |*start-job| link
    And I should see a |*how-to-start-job-dialog| with |*header-message|
    And I enter |*branch-name| in the |*branch-input| field
    And I click on the |*branch-suggestion| list item
    And I click on the |*start| button
    Then I should see a |*flash-message| contains |*success-message|

  @JobList_CheckConfigureStartJob
  Scenario: Check Start Job functionality in application info configure
    When on the |*jobs-list| page
    And I click on the |*more-links| link
    And I click on the |*view-application-info| link
    And I wait 5 seconds to navigate to github connection page
    And I click on the |*configure| link
    And I click on the |*start-job-button| button
    And I should see a |*how-to-start-job-dialog| with |*header-message|
    And I enter |*branch-name| in the |*branch-input| field
    And I click on the |*branch-suggestion| list item
    And I click on the |*start| button
    Then I should see a |*flash-message| contains |*success-message|



