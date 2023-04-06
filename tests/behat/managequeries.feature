@quiz @quiz_datawarehouse
Feature: Configuration of the Data warehouse report
  In order to have my teachers export quiz data
  As an admin
  I need to manage the available queries

  @javascript
  Scenario: Add a first quiz data warehouse report query
    Given I log in as "admin"
    And I am on site homepage
    And I navigate to "Plugins > Activity modules > Quiz > Quiz data warehouse report queries" in site administration
    And I press "Add new query"
    And I set the following fields to these values:
      | Name        | First query             |
      | Description | This is the first query |
      | Query       | First query             |
      | Enabled     | Yes                     |
    And I press "Save changes"
    Then I should see "First query"
    And I log out

  @javascript
  Scenario: Add a second quiz data warehouse report query
    Given I log in as "admin"
    And I am on site homepage
    And I navigate to "Plugins > Activity modules > Quiz > Quiz data warehouse report queries" in site administration
    And I press "Add new query"
    And I set the following fields to these values:
      | Name        | First query             |
      | Description | This is the first query |
      | Query       | First query             |
      | Enabled     | Yes                      |
    And I press "Save changes"
    And I press "Add new query"
    And I set the following fields to these values:
      | Name        | Second query             |
      | Description | This is the second query |
      | Query       | Second query             |
      | Enabled     | Yes                      |
    And I press "Save changes"
    Then I should see "First query"
    And I should see "Second query"
    And I log out

  @javascript
  Scenario: Delete a quiz data warehouse report query
    Given I log in as "admin"
    And I am on site homepage
    And I navigate to "Plugins > Activity modules > Quiz > Quiz data warehouse report queries" in site administration
    And I press "Add new query"
    And I set the following fields to these values:
      | Name        | First query             |
      | Description | This is the first query |
      | Query       | First query             |
      | Enabled     | Yes                      |
    And I press "Save changes"
    And I press "Add new query"
    And I set the following fields to these values:
      | Name        | Second query             |
      | Description | This is the second query |
      | Query       | Second query             |
      | Enabled     | Yes                      |
    And I press "Save changes"
    And I should see "First query"
    And I should see "Second query"
    And I click on "Delete" "link" in the "Second query" "table_row"
    And I should see "Are you sure you want to remove this query?"
    And I should see "Confirm query removal?"
    And I press "Yes"
    Then I should see "First query"
    And I should not see "Second query"
    And I log out
