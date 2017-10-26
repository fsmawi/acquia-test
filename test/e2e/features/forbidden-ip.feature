@ForbiddenIP
Feature: Pipelines Webhooks
  As an Acquia Pipelines user
  I should see the error message if the IP white listing is enabled

  @ForbiddenIP_CheckErrorMessage
  Scenario: Pipelines UI should show appropriate error message for applications which enabled IP white listing.

    Given I visit the |*PIPELINES_URL|
    And I have navigated to |*mock-header| page
    And I enter |*forbidden-ip-yml-file| in the |*header-value| field
    And I click on the |*save| button
    And I should be navigated to |*pipelines-unauthenticated-url|
    And I enter |*app-id| in the |*app-input| field
    And I click on the |*sign-in| button
    And I wait 10 seconds for logging in
    Then I should see a |*error-title| contains |*forbidden-code|
    And I should see a |*error-message| contains |*forbidden-ip-message-1|
    And I should see a |*error-message| contains |*forbidden-ip-message-2|
    And I should see the |*homepage-link|

