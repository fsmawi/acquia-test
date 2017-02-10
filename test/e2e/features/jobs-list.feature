Feature: Pipelines Jobs List
  As an Acquia Pipelines user
  I want to have a list of jobs that updates realtime so that I can monitor my application builds

  Background:
		Given I have navigated to "/auth/tokens"
		 When I enter APP_ID "d16ebf9e-2cb0-59d4-1d91-4c6a1f360af8"
			And API_TOKEN "env:n3token"
			And API_SECRET "env:n3secret"
			And I Click on "SignIn" Button
		 Then I should see the jobs-list page with an alert

  Scenario: Check the last job is displayed as an alert
    When on the jobs-list page
	  Then I can see the last job status displays as an alert with a status and message

  Scenario: Check the joblink navigates to activity table
    When on the jobs-list page
     And I click on the job link in the alert
    Then I should navigate to the job-detail page

	Scenario: Check that the activity card is visible
	  When on the jobs-list page
		Then I should see an activity card with title "Activity"

	Scenario: Check that the activity card should show the appropriate headers
	  When on the jobs-list page
		Then I should see the appropriate headers for the activity card

  @pending
	Scenario: Check that a job in a failed state should be able to be restarted from the activity card
	  When on the jobs-list page
		 And I click on the "Restart" button in the "Actions" column
		Then I should see a new job after 10 seconds with the same branch

  @pending
  Scenario: Check that a job in progress should be able to be stopped from the activity card
		When on the jobs-list page
		 And I click on the "Stop" button in the "Actions" column
		Then I should see the job status as stopped

  Scenario: Validate the activity card contains job table information
    When on the jobs-list page
    Then I should see jobs-list table inside "Activity" card

  Scenario: A job in the activity card should link to the detail page for that job
    When on the jobs-list page
     And I click on the jobs link in the "Build" column
    Then I should navigate to the job-detail page

  Scenario Outline: Each job should display a different icon based on it's status in the activity card
    When on the jobs-list page
    Then I should see the "Status" column icon as <statusIcon> for a job has the status <statusMessage> in the "Message" column

    Examples:
    |  statusMessage        |  statusIcon                         |
    |  Failed               |  state__danger                      |
    |  Job is running       |  status-spinner with color primary  |
    |  Job is terminating   |  status-spinner with color danger   |
    |  Job is queued        |  timer                              |
    |  Job has succeeded    |  state__success--circle             |

  @pending
  Scenario: Check the message when no jobs exist
    When on the jobs-list page
     And there are no jobs
    Then I should see the message as "You have no jobs for this application" inside the "Activity" card
