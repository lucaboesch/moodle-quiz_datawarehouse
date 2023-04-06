@quiz @quiz_datawarehouse
Feature: Run of the Data warehouse report
  In order to export quiz data
  As a teacher
  I need to trigger the available exports

  @javascript
  Scenario: Check quiz data warehouse report capabilities
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And I log in as "admin"
    And I set the following system permissions of "Teacher" role:
      | capability              | permission |
      | quiz/datawarehouse:view | Allow      |
    And the following "activities" exist:
      | activity | name      | intro                 | course | idnumber | groupmode |
      | quiz     | Test quiz | Test quiz description | C1     | quiz1    | 1         |
    And I log out
    Given I am on the "Test quiz" "mod_quiz > View" page logged in as "teacher1"
    And I navigate to "Results" in current page administration
    Then I should see "Data warehouse export" in the ".tertiary-navigation" "css_element"
    And I log out
    And I log in as "admin"
    And I set the following system permissions of "Teacher" role:
      | capability              | permission |
      | quiz/datawarehouse:view | Inherit    |
    And I log out
    And I am on the "Test quiz" "mod_quiz > View" page logged in as "teacher1"
    And I navigate to "Results" in current page administration
    Then I should not see "Data warehouse export" in the ".tertiary-navigation" "css_element"
    And I log out
