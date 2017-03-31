@Acceptance
Feature: Pipelines Github Flow
  As an Acquia Pipelines user
  I want to connect and disconnect github repos to pipelines

  Background:
    Given I visit |https://cloud.acquia.com|
    And I enter |*woody| in the |#edit-name| field
    And I enter |*WOODY_PASS| in the |#edit-pass| field
    And I click on the |*login| button
    And I wait 10 seconds for logging in
    And I visit |*cloud-pipelines-url|
    And I wait 10 seconds for pipelines to load
    Then I should have |*jobs| list within the |*iframe|

  @GithubFlow_ConnectPipelinesToGithubRepo
  Scenario: Connect pipelines to github repository
    When I click on the |*view-connection-info| link
    And I wait 5 seconds for the page to navigate
    And I click on the |*configure| link
    And I should be navigated to |*configure-pipelines| page
    And I wait 5 seconds for the page to navigate
    And I click on the |*configure-github| link
    And I wait 5 seconds for the page to navigate
    And I click on the |*connect-to-github| button
    And I wait 10 seconds for the page to navigate
    And I enter |*github-user| in the |*github-userid| field
    And I enter |*GITHUB-PASS| in the |*github-password| field
    And I click on the |*github-signin| button
    And I wait 5 seconds for the page to navigate
    And I click on the |*select-repo| button within the |*iframe|
    And I click on the |*repo-name-link| link
    And I click on the |*continue| button
    And I should be navigated to |*application-information| page
    And I should see the |*github-header|
    Then I should see the repo name |*repo-name| in the |*repo-info|
    Then I delete the browser cookies

  @GithubFlow_DisconnectPipelinesFromGithubRepo
  Scenario: Disconnect github repository from pipelines
    When I click on the |*view-connection-info| link
    And I wait 5 seconds for the page to navigate
    And I click on the |*configure| link
    And I should be navigated to |*configure-pipelines| page
    And I should see the |*configure-acquia|
    And I click on the |*configure-acquia| link
    And I should be navigated to |*configure-pipelines-acquia| page
    And I click on the |*enable-acquia-git| link
    And I should be navigated to |*application-information| page
    Then I should see the |*acquia-git-header|


