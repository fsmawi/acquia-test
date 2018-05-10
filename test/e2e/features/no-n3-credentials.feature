@NoN3Credentials
Feature: Pipelines Webhooks
  As an Acquia Pipelines user
  I should see a confirmation dialog before attaching the N3 credentials(API tokens)

  Background:
    Given I visit the |*PIPELINES_URL|
    And I have navigated to |*mock-header| page
    And I enter |*no-n3-creds-file| in the |*header-value| field
    And I click on the |*save| button
    And I wait 5 seconds for page to navigate
    And I enter |*app-id| in the |*app-input| field
    And I click on the |*sign-in| button
    And I wait 5 seconds for logging in
    Then I should see the |*no-n3-dialog|
    And I should see the |*api-token-title|

  @NoN3Credentials_ClickYes
  Scenario: Pipelines UI should have all the functionality
    When on the |*jobs-list| page
    Then I click on the |*yes-button| button
    Then I should see a |*flash-message-alert| contains |*no-n3-yes-flash-message-1|
    Then I should see a |*flash-message-alert| contains |*no-n3-yes-flash-message-2|
    And I should see the |*start-job-widget|
    And I should see the |*repo-info-widget|
    When I click on the |*more-links| link
    And I click on the |*view-application-info| link
    And I wait 5 seconds to navigate to github connection page
    And I should see the |*configure|

  @NoN3Credentials_ClickNo
  Scenario: Pipelines UI should not show 'Configure' link
    When on the |*jobs-list| page
    Then I click on the |*no-button| button
    Then I should see a |*flash-message-alert| contains |*no-n3-no-flash-message-1|
    Then I should see a |*flash-message-alert| contains |*no-n3-no-flash-message-2|
    And I should see the |*start-job-widget|
    And I should see the |*repo-info-widget|
    When I click on the |*more-links| link
    And I click on the |*view-application-info| link
    And I wait 5 seconds to navigate to github connection page
    And I should not see the |*configure|


