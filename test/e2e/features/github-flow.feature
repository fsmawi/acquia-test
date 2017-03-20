@GithubFlow
Feature: Pipelines Github Flow
  As an Acquia Pipelines user
  I want to attach a Github repository to my application

  Background:
    Given I visit the |*PIPELINES_URL|
    And I have navigated to |*mock-header| page
    And I enter |*github-success| in the |*header-value| field
    And I click on the |*save| button
    And I should be navigated to |*pipelines-unauthenticated-url|
    And I enter |*app-id| in the |*app-input| field
    And I click on the |*sign-in| button
    And I wait 10 seconds for logging in
    Then I should see the |app-jobs| list

  @GithubFlow_CheckGithubConnectionSuccess
  Scenario: Check that the connection to Github succeed
    When I click on the |*view-connection-info| link
    And I wait 5 seconds to navigate to github connection page
    And I click on the |*re-authorize| button
    And I click on the |*connect-to-github| button
    And I should see a |*flash-message| with |*success-message|
    And I get success parameter with value "true"
    And I should see the |*select-repo| button

  @GithubFlow_CheckGithubConnectionFails
  Scenario: Check that the connection to Github failed
    Given I have navigated to |*mock-header| page
    And I enter |*github-fail| in the |*header-value| field
    And I click on the |*save| button
    And I should be navigated to |*pipelines-unauthenticated-url|
    And I enter |*app-id| in the |*app-input| field
    And I click on the |*sign-in| button
    And I wait 10 seconds for logging in
    And I should see the |app-jobs| list
    When I click on the |*view-connection-info| link
    And I wait 5 seconds to navigate to github connection page
    And I click on the |*re-authorize| button
    And I click on the |*connect-to-github| button
    And I get reason parameter with value |*fail-reason|
    And I get success parameter with value "false"
    Then I should see a |*flash-message| with |*fail-reason|

  @GithubFlow_CheckAttachRepository
  Scenario: Check that attaching repository works
    When I click on the |*view-connection-info| link
    And I wait 5 seconds to navigate to github connection page
    And I click on the |*re-authorize| button
    And I click on the |*connect-to-github| button
    And I should see a |*flash-message| with |*success-message|
    And I click on the |*select-repo| button
    Then I should see a modal with non empty |*repo-list| list
    And I enter |no_repo| in the |*repo-filter-text| field
    Then I should see empty |*repo-list| list
    And I enter |rep| in the |*repo-filter-text| field
    Then I should see in the |*repo-list| list only repositories that contain "rep" keyword
    And I click on the |*repo1-radio| button
    And I click on the |*continue| button
    Then I should be navigated to |*application-information| page

  @GithubFlow_CheckChooseRepositoryCancel
  Scenario: Check that the 'Cancel' button close the repository modal
    When I click on the |*view-connection-info| link
    And I wait 5 seconds to navigate to github connection page
    And I click on the |*re-authorize| button
    And I click on the |*connect-to-github| button
    And I should see a |*flash-message| with |*success-message|
    And I click on the |*select-repo| button
    Then I should see a modal with non empty |*repo-list| list
    And I click on the |*cancel| button
    Then I should not see the repository modal
