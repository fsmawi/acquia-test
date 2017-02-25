@Acceptance @BakerySSO
Feature: Bakery Authentication Strategy
  As an Acquia Pipelines user
  I want to have a single sign on procedure for all my Acquia products, including pipelines.

  @BakerySSO_VerifySSO
  Scenario: Log in with bakery
  Logging into cloud.acquia.com authenticates pipelines

    Given I visit the |*pipelines-url|
    When I enter |*woody-app| in the |*app-input| field
    And I click on the |*sign-in| button
    Then I should be navigated to |*pipelines-unauthenticated-url|
    Then I visit |https://cloud.acquia.com|
    And I enter |*woody| in the |#edit-name| field
    And I enter |*WOODY_PASS| in the |#edit-pass| field
    And I click on the |*login| button
    And I wait 10 seconds for logging in
    And I visit |*pipelines-url|
    And I enter |*woody-app| in the |*app-input| field
    And I click on the |*sign-in| button
    And I wait 10 seconds for logging in
    Then I should see the |app-jobs| list
