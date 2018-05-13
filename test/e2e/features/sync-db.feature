@SyncDb
Feature: Pipelines Sync DB configuration
  As an Acquia Pipelines user
  I should see the Sync Dbs source information

  Background:
    Given I visit the |*PIPELINES_URL|
    And I have navigated to |*mock-header| page
    And I enter |*sync-db-yml-file| in the |*header-value| field
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

  @SyncDb_CheckSyncDbInfo
  Scenario: Able to check the webhooks information
    Then I should see the |*app-name|
    And I should see the |*info-title|
    Then I should see the |*sync-db-widget|
    And I should not see the |*sync-db-info-not-available|

  @SyncDb_UpdateDbSyncParam
  Scenario: Able to update Dbs Sync Environment
    Then I should see the |*sync-db-widget|
    And I should see the |*sync-db-select|
    When I select value |dev3| from |*sync-db-select|
    And I wait 5 seconds for the page to navigate
    Then I should see the |*sync-db-widget|
    And I should not see the |*sync-db-info-not-available|
    And I should see |*dev3| value in |*sync-db-select|

  @SyncDb_ShouldNotSeeSyncDbParam
  Scenario: Should not see Sync Dbs environment source
    Then I visit the |*PIPELINES_URL|
    And I have navigated to |*mock-header| page
    And I enter |*sync-db-no-env-yml-file| in the |*header-value| field
    And I click on the |*save| button
    And I should be navigated to |*pipelines-unauthenticated-url|
    And I enter |*app-id| in the |*app-input| field
    And I click on the |*sign-in| button
    And I wait 10 seconds for logging in
    Then I should see the |app-jobs| list
    Then I click on the |*more-links| link
    And I click on the |*view-application-info| link
    And I wait 5 seconds for the page to navigate
    Then I should not see the |*sync-db-select|
    And I should see the |*sync-db-info-not-available|
