@Acceptance @CloudIntegration
Feature: Pipelines Cloud Integration
  As an Acquia Pipelines user
  I want to have the pipelines feature shown when I login to Acquia accounts

  @CloudIntegration_VerifyPipelinesIframe
  Scenario: Verify Pipelines iframe
  Navigating to pipelines URL should show the pipelines app in iframe

    Given I visit |https://accounts.acquia.com|
    When I enter |*woody| in the |#edit-name| field
    And I enter |*WOODY_PASS| in the |#edit-pass| field
    And I click on the |*login| button
    Then I wait 10 seconds for logging in
    And I visit |*cloud-pipelines-url|
    Then I wait 10 seconds for pipelines to load
    Then I should be shown pipelines app in an |*iframe|
    Then I should have |*jobs| list within the |*iframe|


