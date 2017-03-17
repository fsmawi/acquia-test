@Acceptance
Feature: Pipelines Github Flow
  As an Acquia Pipelines user
  I want to connect disconnect and reautorize github repos to pipelines

  Background:
    Given I visit |https://cloud.acquia.com|
    And I enter |*woody| in the |#edit-name| field
    And I enter |*WOODY_PASS| in the |#edit-pass| field
    And I click on the |*login| button
    And I wait 10 seconds for logging in
    And I visit |*cloud-pipelines-url|
    And I wait 10 seconds for pipelines to load
    Then I should have |*jobs| list within the |*iframe|

  @pending
  @GithubFlow_DisconnectPipelinesFromGithubRepo
  Scenario: Disconnect github repository from pipelines
    When I click on the |*view-connection-info| link
    And I wait 5 seconds for the page to navigate
    And I click on the |*re-authorize| button
    And I wait 5 seconds for the page to navigate
    And I click on the |*connect-to-github| button
    And I enter |*github-user| in the |*github-userid| field
    And I enter |*GITHUB-PASS| in the |*github-password| field
    And I click on the |*github-signin| button
    #TODO - Yet to write steps here to handle disconnect from github-ui

  @pending
  @GithubFlow_ConnectPipelinesToGithubRepo
  Scenario: Connect pipelines to github repository
    When I click on the |*view-connection-info| link
    And I wait 5 seconds for the page to navigate
    And I click on the |*re-authorize| button
    And I wait 5 seconds for the page to navigate
    And I click on the |*connect-to-github| button
    And I wait 10 seconds for the page to navigate
    And I enter |*github-user| in the |*github-userid| field
    And I enter |*GITHUB-PASS| in the |*github-password| field
    And I click on the |*github-signin| button
    #TODO - Yet to write steps here to handle connect and repo authorization from github-ui

  @pending
  @GithubFlow_ReAuthorizePipelinesToAnotherGithubRepo
  Scenario: Reauthorize pipelines to another github repository
    When I click on the |*view-connection-info| link
    And I wait 5 seconds for the page to navigate
    And I click on the |*re-authorize| button
    And I wait 5 seconds for the page to navigate
    And I click on the |*connect-to-github| button
    And I enter |*github-user| in the |*github-userid| field
    And I enter |*GITHUB-PASS| in the |*github-password| field
    And I click on the |*github-signin| button
    And I wait 5 seconds for the page to navigate
    #TODO - Yet to write steps here to handle repo authorization from github-ui
    And I should be navigated to |*pipelines| page
    And I click on the |*select-repo| button
    Then I should see a modal with non empty |*repo-list| list
    And I enter |rep| in the |*repo-filter-text| field
    Then I should see in the |*repo-list| list only repositories that contain "rep" keyword
    And I click on the |*repo1-radio| button
    And I click on the |*continue| button
    Then I should be navigated to |*application-information| page
