@mod @mod_subcourse
Feature: Course final grades hidden in the referenced course are hidden in the target course, too.
  In order to not reveal the hidden grades to students
  As a teacher
  I need to be sure that grades hidden in the referenced course, are kept hidden when fetched into the subcourse activity

  Background:
    Given the following "users" exist:
      | username      | firstname | lastname  | email                |
      | teacher1      | Teacher   | 1         | teacher1@example.com |
      | student1      | Student   | 1         | student1@example.com |
      | student2      | Student   | 2         | student2@example.com |
    And the following "courses" exist:
      | fullname      | shortname | category  |
      | MainCourse    | M         | 0         |
      | RefCourse     | R         | 0         |
    And the following "course enrolments" exist:
      | user          | course    | role              |
      | teacher1      | M         | editingteacher    |
      | student1      | M         | student           |
      | student2      | M         | student           |
      | teacher1      | R         | editingteacher    |
      | student1      | R         | student           |
      | student2      | R         | student           |
    #
    # Set grades in the referenced course.
    #
    And I log in as "teacher1"
    And I am on "RefCourse" course homepage
    And I navigate to "Setup > Gradebook setup" in the course gradebook
    And I press "Add grade item"
    And I set the following fields to these values:
      | Item name     | Manual item 1   |
      | Maximum grade | 10              |
    And I press "Save changes"
    And I navigate to "View > Grader report" in the course gradebook
    And I turn editing mode on
    And I give the grade "5" to the user "Student 1" for the grade item "Manual item 1"
    And I give the grade "8" to the user "Student 2" for the grade item "Manual item 1"
    And I press "Save"
    And I follow "Change to aggregates only"
    And I click on "Edit grade" "link" in the "Student 1" "table_row"
    And I set the following fields to these values:
      | Hidden  | 1 |
    And I press "Save changes"
    And I turn editing mode off

  @javascript
  Scenario: If the course final grade is hidden, the associated subcourse activity grade is marked as hidden, too.
    Given I am on "MainCourse" course homepage
    And I turn editing mode on
    And I add a "Subcourse" to section "1" and I fill the form with:
      | Subcourse name                    | Unit course 1       |
      | Fetch grades from                 | RefCourse (R)       |
      | Redirect to the referenced course | 0                   |
    And I turn editing mode off
    And I am on "MainCourse" course homepage
    And I am on the "Unit course 1" "subcourse activity" page logged in as teacher1
    When I follow "Fetch grades now"
    And I am on "MainCourse" course homepage
    And I navigate to "View > Grader report" in the course gradebook
    Then the following should exist in the "user-grades" table:
      | Email address         | -4-   |
      | student1@example.com  | 5.00  |
      | student2@example.com  | 8.00  |
    And I log out
    #
    # Student 1 should not see the grade in the referenced course.
    #
    And I log in as "student1"
    And I am on "MainCourse" course homepage
    And I navigate to "User report" in the course gradebook
    And I should see "MainCourse" in the "user-grade" "table"
    And I should not see "Unit course 1" in the "user-grade" "table"
    And I should not see "5.00" in the "user-grade" "table"
    And I log out
    #
    # Student 2 should see the grade normally.
    #
    And I log in as "student2"
    And I am on "MainCourse" course homepage
    And I navigate to "User report" in the course gradebook
    And I should see "MainCourse" in the "user-grade" "table"
    And I should see "Unit course 1" in the "user-grade" "table"
    And I should see "8.00" in the "user-grade" "table"

  @javascript
  Scenario: If the whole course final grade item is hidden, the associated subcourse activity grade item is marked as hidden, too.
    Given I am on "RefCourse" course homepage
    And I navigate to "Setup > Gradebook setup" in the course gradebook
    And I set the following settings for grade item "RefCourse":
      | Hidden          | 1 |
    And I am on "MainCourse" course homepage
    And I turn editing mode on
    And I add a "Subcourse" to section "1" and I fill the form with:
      | Subcourse name                    | Unit course 1       |
      | Fetch grades from                 | RefCourse (R)       |
      | Redirect to the referenced course | 0                   |
    And I turn editing mode off
    And I am on "MainCourse" course homepage
    And I am on the "Unit course 1" "subcourse activity" page logged in as teacher1
    When I follow "Fetch grades now"
    And I am on "MainCourse" course homepage
    And I navigate to "View > Grader report" in the course gradebook
    Then the following should exist in the "user-grades" table:
      | Email address         | -4-   |
      | student1@example.com  | 5.00  |
      | student2@example.com  | 8.00  |
    And I log out
    #
    # Student 1 should not see the grade in the referenced course.
    #
    And I log in as "student1"
    And I am on "MainCourse" course homepage
    And I navigate to "User report" in the course gradebook
    And I should see "MainCourse" in the "user-grade" "table"
    And I should not see "Unit course 1" in the "user-grade" "table"
    And I should not see "5.00" in the "user-grade" "table"
    And I log out
    #
    # Student 2 should not see the grade, too.
    #
    And I log in as "student2"
    And I am on "MainCourse" course homepage
    And I navigate to "User report" in the course gradebook
    And I should see "MainCourse" in the "user-grade" "table"
    And I should not see "Unit course 1" in the "user-grade" "table"
    And I should not see "8.00" in the "user-grade" "table"
