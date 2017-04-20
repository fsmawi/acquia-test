@ConnectedRepoInformationFlow
Feature: Pipelines Connected Repo Information Flow
  As an Acquia Pipelines user
  I want to check the connected repository information

  @ConnectedRepoInformationFlow_CheckInfoByRepoType
  Scenario Outline: Check the application's connected repository information (Repo type: <repo-type>)
    Given I visit the |*PIPELINES_URL|
    And I have navigated to |*mock-header| page
    And I enter |*<repo-type>| in the |*header-value| field
    And I click on the |*save| button
    And I wait 5 seconds for page to navigate
    And I enter |*app-id| in the |*app-input| field
    And I click on the |*sign-in| button
    And I wait 10 seconds for logging in
    Then I should see the |app-jobs| list
    When I click on the |*more-links| link
    When I click on the |*view-application-info| link
    And I wait 5 seconds to navigate to github connection page
    And I should see the |*info-title|
    And I should see the |*repo-type-<repo-type>-title|
    And I should see the |*repo-type-<repo-type>-icon|
    And I should see |*repo-url| |value| containing |*repo-url-<repo-type>-text|
    And I should see |*command-stage| |value| containing |*command-stage-text|
    And I should see |*command-commit| |value| containing |*command-commit-text|
    And I should see |*command-push| |value| containing |*command-push-text|
    And I click on the |*pipelines| link
    Then I should see the |app-jobs| list

    Examples:
      | repo-type  |
      | acquia     |
      | github     |
      
