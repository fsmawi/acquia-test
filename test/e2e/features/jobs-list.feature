@JobList
Feature: Pipelines Jobs List
  As an Acquia Pipelines user
  I want to have a list of jobs that updates realtime so that I can monitor my application builds

  @JobList_CheckAlertLastJob
  Scenario: Check the last job is displayed as an alert
    Given jobs yml file "jobs.yml"
    When on the jobs-list page
    Then I can see the last job status displays as an alert with a status and message

  @JobList_CheckAlertJobLink
  Scenario: Check the joblink navigates to activity table
    Given jobs yml file "jobs.yml"
    When on the jobs-list page
    And I click on the job link in the alert
    Then I should navigate to the job-detail page

  @JobList_CheckActivityCard
  Scenario: Check that the activity card is visible
    Given jobs yml file "jobs.yml"
    When on the jobs-list page
    Then I should see an activity card with title "Activity"

  @JobList_CheckActivityTableHeaders
  Scenario: Check that the activity card should show the appropriate headers
    Given jobs yml file "jobs.yml"
    When on the jobs-list page
    Then I should see the appropriate headers for the activity card

  @pending
  @JobList_RestartJob
  Scenario: Check that a job in a failed state should be able to be restarted from the activity card
    Given jobs yml file "jobs.yml"
    When on the jobs-list page
    And I click on the "Restart" button in the "Actions" column
    Then I should see a new job after 10 seconds with the same branch

  @pending
  @JobList_StopJob
  Scenario: Check that a job in progress should be able to be stopped from the activity card
    Given jobs yml file "jobs.yml"
    When on the jobs-list page
    And I click on the "Stop" button in the "Actions" column
    Then I should see the job status as "Job is paused"

  @JobList_CheckActivityTable
  Scenario: Validate the activity card contains job table information
    Given jobs yml file "jobs.yml"
    When on the jobs-list page
    Then I should see jobs-list table inside "Activity" card

  @JobList_CheckDetailLink
  Scenario: A job in the activity card should link to the detail page for that job
    Given jobs yml file "jobs.yml"
    When on the jobs-list page
    And I click on the jobs link in the "Build" column
    Then I should navigate to the job-detail page

  @JobList_CheckStatusIcons
  Scenario Outline: Each job should display a different icon based on it's status in the activity card
    Given jobs yml file "jobs.yml"
    When on the jobs-list page
    Then I should see the "Status" column icon as <statusIcon> for a job with id "<jobId>"

    Examples:
      | jobId    | statusIcon             |
      | 2cb7d930 | state__danger          |
      | ad9aacb4 | spin-reverse           |
      | 8f0c38d5 | timer                  |
      | 34b06147 | state__success--circle |

  @JobList_CheckNoJobsDisplayed
  Scenario: Check the message when no jobs exist
    Given jobs yml file "no-jobs.yml"
    When on the jobs-list page with no jobs
    Then I should see the message as "You have no jobs for this application" inside the "Activity" card

  @JobList_CheckSummaryTable
  Scenario: Check the job summary table displaying at the top of jobs-list page with details of last run job
    Given jobs yml file "jobs.yml"
    When on the jobs-list page
    Then I should see last run job details as a summary table

