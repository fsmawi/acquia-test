@HelpCenterDrawerFlow
Feature: Pipelines Help Content Drawer Flow
  As an Acquia Pipelines user
  I want to check the help content drawer

  Background:
    Given I visit the |*PIPELINES_URL|
    And I have navigated to |*mock-header| page
    And I enter |*jobs-yml-file-name| in the |*header-value| field
    And I click on the |*save| button
    And I wait 5 seconds for page to navigate
    And I enter |*app-id| in the |*app-input| field
    And I click on the |*sign-in| button
    And I wait 10 seconds for logging in
    And I should see the |app-jobs| list


  @HelpCenterDrawerFlow_CheckHelpContent
  Scenario: Check the help drawer content
    When on the |*pipelines| page
    Then I click on the |*help-center| link
    And I should see the |*help-center-title|
    And I should see the |*get-started-title|
    And I should see the |*search-box|
    And I click on the |*close-help-center| button
    And I should see the |app-jobs| list

  @HelpCentertDrawerFlow_CheckSearchHelpContent
  Scenario: Check the search help content functionality
    When on the |*pipelines| page
    Then I click on the |*help-center| link
    And I should see the |*search-box|
    And I enter |guide| in the |*search-box| field
    Then I should see in the |*help-content-list| only the items that contain "guide" keyword
    And I click on the |*close-help-center| button
    And I should see the |app-jobs| list
