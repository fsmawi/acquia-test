@StandaloneApplication
Feature: Pipelines Jobs List
  As an Acquia Pipelines user
  I want to have a list of jobs that updates realtime so that I can monitor my application builds

  @StandaloneApplication_AppList
  Scenario: Check the application list functionality
    Given I visit the |*PIPELINES_URL|
    And I have navigated to |*mock-header| page
    And I enter |*jobs-yml-file-name| in the |*header-value| field
    And I click on the |*save| button
    And I wait 5 seconds for page to navigate
    And I enter |*app-id| in the |*app-input| field
    And I click on the |*sign-in| button
    And I wait 10 seconds for page to navigate
    When on the |*jobs-list| page
    Then I should see the |*applications-list|
    And I should see a |*job-summary-job-id| contains |8f0c38d5|
    And I should see a |*application-list-first-job-id| contains |8f0c38d5|
    When I click on the |*application-list-n3-api-app| link
    Then I should see the |app-jobs| list
    When I click on the |*application-list-first-job-id| link
    And I should navigate to the |*job-detail| page
    Then I can see an alert showing the status of the job and message

  @StandaloneApplication_AppListNotVisible
  Scenario: Check the application list not visible in iframe
    Given I visit the |*PIPELINES_IFRAME_URL|
    And I wait 10 seconds for page to navigate
    And I should not see the |*applications-list|
    Then I should have |*mock-header-card| list within the |*iframe|
    And I enter |*jobs-yml-file-name| in the |*header-value| field
    And I click on the |*save| button
    And I wait 5 seconds for page to navigate
    And I enter |*app-id| in the |*app-input| field
    And I click on the |*sign-in| button
    And I wait 10 seconds for page to navigate
    When on the |*jobs-list| page
    And I should not see the |*applications-list|
