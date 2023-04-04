@quiz_datawarehouse
Feature: Configuration the Data warehouse report
  In order to have my teachers export quiz data
  As an admin
  I need to manage the available queries

  @javascript
  Scenario: Add a first quiz fata warehouse report query
    Given I log in as "admin"
    And I navigate to "Plugins > Quiz data warehouse report queries" in site administration
    And I press "Add new query"
    And I log out
