@mod @mod_bigbluebuttonbn
Feature: BigBlueButton behaves gracefully when the server is not configured
  In order to avoid confusing errors when BBB has no server configured
  As a user
  I want to see informative messages instead of error pages or broken redirects

  Background:
    Given I enable "bigbluebuttonbn" "mod" plugin
    And the BigBlueButton server is not configured
    And the following "courses" exist:
      | fullname    | shortname | category |
      | Test course | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
      | student1 | Student   | One      | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "activities" exist:
      | activity        | course | name         | type |
      | bigbluebuttonbn | C1     | BBB Room     | 0    |

  Scenario: Admin visiting an unconfigured BBB activity sees a neutral page with a settings link
    When I am on the "BBB Room" "bigbluebuttonbn activity" page logged in as "admin"
    Then I should see "BigBlueButton is not yet configured"
    And I should see "You can complete the setup in Site administration"
    And "BigBlueButton settings" "link" should exist
    And I should not see "Unable to enter the room"

  Scenario: Teacher visiting an unconfigured BBB activity sees a contact admin message
    When I am on the "BBB Room" "bigbluebuttonbn activity" page logged in as "teacher1"
    Then I should see "BigBlueButton is not yet configured"
    And I should see "Please contact your site administrator to complete the setup"
    And "BigBlueButton settings" "link" should not exist
    And I should not see "Unable to connect to the BigBlueButton server"

  Scenario: Student visiting an unconfigured BBB activity sees a contact teacher message
    When I am on the "BBB Room" "bigbluebuttonbn activity" page logged in as "student1"
    Then I should see "BigBlueButton is not yet configured"
    And I should see "Please contact your teacher"
    And "BigBlueButton settings" "link" should not exist

  @javascript
  Scenario: Teacher sees BigBlueButton grayed out in the activity chooser when the server is not configured
    Given I am on the "Test course" "course" page logged in as "teacher1"
    When I open the activity chooser
    Then the "aria-disabled" attribute of "[data-internal=bigbluebuttonbn]" "css_element" should contain "true"
    And I should see "BigBlueButton" in the "[data-internal=bigbluebuttonbn]" "css_element"
