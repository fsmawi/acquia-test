@Webhooks
Feature: Pipelines Webhooks
  As an Acquia Pipelines user
  I should see the webhooks information for acquia-git repos

  Background:
    Given I visit the |*PIPELINES_URL|
    And I have navigated to |*mock-header| page
    And I enter |*webhooks-yml-file-name| in the |*header-value| field
    And I click on the |*save| button
    And I should be navigated to |*pipelines-unauthenticated-url|
    And I enter |*app-id| in the |*app-input| field
    And I click on the |*sign-in| button
    And I wait 10 seconds for logging in
    Then I should see the |app-jobs| list
    Then I click on the |*more-links| link
    And I click on the |*view-application-info| link
    And I should be navigated to |*application-info-url|
    And I wait 5 seconds for the page to navigate

  @Webhooks_CheckWebhooksInfo
  Scenario: Able to check the webhooks information
    Then I should see the |*app-name|
    And I should see the |*info-title|
    And I should see the |*repo-type-acquia-title|
    And I should see the |*repo-type-acquia-icon|
    Then I should see the |*webhooks-widget|
    And I should not see the |*webhooks-info-not-available|

  @Webhooks_UpdateWebhooks
  Scenario: Able to update webhooks
    Then I should see the |*webhooks-widget|
    And I should see the |*webhooks-select|
    And I should see |*disabled| value in |*webhooks-select|
    When I select |*enabled| from |*webhooks-select|
    And I wait 5 seconds for the page to navigate
    Then I should see the |*webhooks-widget|
    And I should not see the |*webhooks-info-not-available|
    And I should see |*enabled| value in |*webhooks-select|
    When I select |*disabled| from |*webhooks-select|
    And I wait 5 seconds for the page to navigate
    Then I should see the |*webhooks-widget|
    And I should see |*disabled| value in |*webhooks-select|
    When I select |*enabled| from |*webhooks-select|
    And I wait 5 seconds for the page to navigate
    Then I should see the |*webhooks-widget|
    And I should see the |*webhooks-info-not-available|

  @Webhooks_ShouldNotSeeWebhooks
  Scenario Outline: Should not see webhooks info for github/bitbucket repos
    Then I visit the |*PIPELINES_URL|
    And I have navigated to |*mock-header| page
    And I enter |*<repo-type>| in the |*header-value| field
    And I click on the |*save| button
    And I should be navigated to |*pipelines-unauthenticated-url|
    And I enter |*app-id| in the |*app-input| field
    And I click on the |*sign-in| button
    And I wait 10 seconds for logging in
    Then I should see the |app-jobs| list
    Then I click on the |*more-links| link
    And I click on the |*view-application-info| link
    And I wait 5 seconds for the page to navigate
    Then I should not see the |*webhooks-widget|

    Examples:
      | repo-type  |
      | bitbucket     |
      | github     |
