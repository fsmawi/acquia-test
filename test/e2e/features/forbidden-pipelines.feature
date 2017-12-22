@ForbiddenPipelines
Feature: Pipelines Webhooks
  As an Acquia Pipelines user
  I should see limited functionality and the error message user doesn't have 'execute pipelines' permission

  Background:
    Given I visit the |*PIPELINES_URL|
    And I have navigated to |*mock-header| page
    And I enter |*forbidden-ip-yml-file| in the |*header-value| field
    And I click on the |*save| button
    And I wait 5 seconds for page to navigate
    And I enter |*app-id| in the |*app-input| field
    And I click on the |*sign-in| button
    And I wait 5 seconds for logging in
    Then I should see the |app-jobs| list

  @ForbiddenPipelines_NoStartJob
  Scenario: Pipelines UI should not show 'Start Job'
    When on the |*jobs-list| page
    Then I should see a |*flash-message-alert| contains |*forbidden-pipelines-flash-message-1|
    Then I should see a |*flash-message-alert| contains |*forbidden-pipelines-flash-message-2|
    Then I should see a |*flash-message-alert| contains |*forbidden-pipelines-flash-message-3|
    And I should not see the |*start-job-widget|
    And I should not see the |*repo-info-widget|

  @ForbiddenPipelines_NoRerunJob
  Scenario: Pipelines UI should not show 'Rerun Job'
    When on the |*jobs-list| page
    And I click on the first job id in the "Build" column from the list of jobs displayed
    And I wait 15 seconds for loading job details screen
    And I should navigate to the |*job-detail-forbidden-pipelines| page
    Then I should not see the |*rerun-job-widget|

  @ForbiddenPipelines_CheckErrorMessage
  Scenario: Pipelines UI should show appropriate error message for applications which user doesn't have 'execute pipelines' permission
    When on the |*jobs-list| page
    Then I click on the |*more-links| link
    And I click on the |*view-application-info| link
    Then I should see a |*error-title| contains |*forbidden-code|
    And I should see a |*error-message| contains |*forbidden-pipelines-message-1|
    And I should see a |*error-message| contains |*forbidden-pipelines-message-2|
    And I should see the |*job-list-link|

