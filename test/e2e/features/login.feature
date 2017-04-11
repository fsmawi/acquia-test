@LoginPage
Feature: Pipelines login page
  As an Acquia Pipelines user
  I should see the information about single sign-on on the login page

  @LoginPage_SSO_Info
  Scenario: verify the single sign on information displayed on the login page
    Given I visit the |*PIPELINES_URL|
    And I have navigated to |*mock-header| page
    And I enter |*no-jobs-yml-file-name| in the |*header-value| field
    And I click on the |*save| button
    And I should be navigated to |*pipelines-unauthenticated-url|
    Then I should see a |*sso-info-header| contains |*sso-info-header-text|
    And I should see a |*signin-instructions| contains |*signin-instruction-item1|
    And I should see a |*signin-instructions| contains |*signin-instruction-item2|
    And I should see a |*signin-instructions| contains |*signin-instruction-item3|
    And I should see the |*success-icon| besides sign-in instruction
