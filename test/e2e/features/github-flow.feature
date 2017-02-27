@pending
@GithubFlow
Feature: Pipelines Github Flow
  As an Acquia Pipelines user
  I want to attach a Github repository to my application

  @GithubFlow_CheckGithubConnectionSuccess
  Scenario: Check that the connection to Github succeed
    Given github yml file |*github-success|
    When on |*github-auth-url|
    Then I should see the |*connection| button
    Then I click on the |*connection| button
    And I wait 10 seconds to be navigated
    Then I should be navigated back to |*github-auth-url|
    And I get success parameter with value "true"
    Then I should see a flash message with |*succes-message|
    Then I should see the |*select-repo| button

  @GithubFlow_CheckGithubConnectionFails
  Scenario: Check that the connection to Github failed
    Given github yml file |*github-fail|
    When on |*github-auth-url|
    Then I click on the |*connection| button
    And I wait 10 seconds to be navigated
    Then I should be navigated back to the github page
    And I get success parameter with value "false"
    And I get reason parameter with |*fail-reason|
    Then I should see a flash message with |*fail-reason|

  @GithubFlow_CheckAttachRepository
  Scenario: Check that attaching repository works
    Given github yml file |*github-success|
    When on |*github-auth-url|
    And I click on |*select-repo| button
    Then I should see a modal with non empty |*repo-list| list
    And I typed "no_repo" keyword in the Filter input
    Then I should see empty |*repo-list| list
    And I type "rep" keyword in the Filter input
    Then I should see in the |*repo-list| list only repositories that contain "rep" keyword
    And I choose a repository
    And I click on |*continue| button
    Then I should be navigated to application page

  @GithubFlow_CheckChooseRepositoryCancel
  Scenario: Check that the 'Cancel' button close the repository modal
    Given github yml file |*github-success|
    When on |*github-auth-url|
    And I click on |*select-repo| button
    Then I should see a modal with non empty |*repo-list| list
    And I click on |*cancel| button
    Then I should not see the repository modal

