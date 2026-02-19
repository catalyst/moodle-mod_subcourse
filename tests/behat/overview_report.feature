@mod @mod_subcourse
Feature: Testing overview integration in subcourse activity
  In order to summarize the subcourse activity
  As a user
  I need to be able to see the subcourse activity overview

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
    And the following "courses" exist:
      | fullname  | shortname | category |
      | Course 1  | M         | 0        |
      | RefCourse | R         | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | M      | editingteacher |
      | student1 | M      | student        |
      | teacher1 | R      | editingteacher |
      | student1 | R      | student        |
    And I am on the "Course 1" course page logged in as "teacher1"
    And I turn editing mode on
    And I add a "subcourse" activity to course "Course 1" section "1" and I fill the form with:
      | Subcourse name                    | Unit course 1 |
      | Fetch grades from                 | RefCourse (R) |
      | Redirect to the referenced course | 0             |

  @javascript
  Scenario: The subcourse activity overview report should generate log events
    Given the site is running Moodle version 5.0 or higher
    And I am on the "Course 1" "course > activities > subcourse" page logged in as "teacher1"
    When I am on the "Course 1" "course" page logged in as "teacher1"
    And I navigate to "Reports" in current page administration
    And I click on "Logs" "link"
    And I click on "Get these logs" "button"
    Then I should see "Course activities overview page viewed"
    And I should see "viewed the instance list for the module 'subcourse'"

  @javascript
  Scenario: The subcourse activity index redirect to the activities overview
    Given the site is running Moodle version 5.0 or higher
    When I am on the "Course 1" "course > activities > subcourse" page logged in as "admin"
    Then I should see "Name" in the "subcourse_overview_collapsible" "region"
    And I should see "Actions" in the "subcourse_overview_collapsible" "region"
