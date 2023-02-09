@mod @mod_subcourse
Feature: Grades can be fetched either a real values or as percentages
  In order to have the same grade in the referenced course and in the target meta course
  As a teacher
  I need to be able to set whether grades are fetched as real values or as percentual values

  Background:
    Given the following "users" exist:
      | username      | firstname | lastname  | email                |
      | teacher1      | Teacher   | 1         | teacher1@example.com |
      | student1      | Student   | 1         | student1@example.com |
      | student2      | Student   | 2         | student2@example.com |
      | student3      | Student   | 3         | student3@example.com |
      | student4      | Student   | 4         | student4@example.com |
      | student5      | Student   | 5         | student5@example.com |
    And the following "courses" exist:
      | fullname      | shortname | category  |
      | MainCourse    | M         | 0         |
      | RefCourse     | R         | 0         |
    And the following "course enrolments" exist:
      | user          | course    | role              |
      | teacher1      | M         | editingteacher    |
      | student1      | M         | student           |
      | student2      | M         | student           |
      | student3      | M         | student           |
      | student4      | M         | student           |
      | student5      | M         | student           |
      | teacher1      | R         | editingteacher    |
      | student1      | R         | student           |
      | student2      | R         | student           |
      | student3      | R         | student           |
      | student4      | R         | student           |
      | student5      | R         | student           |
    #
    # Set grades in the referenced course.
    #
    And I log in as "teacher1"
    And I am on "RefCourse" course homepage
    And I navigate to "Setup > Gradebook setup" in the course gradebook
    And I set the following settings for grade item "RefCourse":
      | Aggregation           | Natural |
      | Exclude empty grades  | 1       |
    And I press "Add grade item"
    And I set the following fields to these values:
      | Item name     | Manual item 1   |
      | Maximum grade | 10              |
    And I press "Save changes"
    And I press "Add grade item"
    And I set the following fields to these values:
      | Item name     | Manual item 2   |
      | Maximum grade | 10              |
    And I press "Save changes"
    And I press "Add grade item"
    And I set the following fields to these values:
      | Item name     | Manual item 3   |
      | Maximum grade | 10              |
    And I press "Save changes"
    And I navigate to "Setup > Course grade settings" in the course gradebook
    And I set the field "Grade display type" to "Real (percentage)"
    #
    # Set also Grader report preferences to
    #
    And I press "Save changes"
    And I am on the "RefCourse" course page logged in as "teacher1"
    And I navigate to "View > Grader report" in the course gradebook
    And I turn editing mode on
    #
    # Student 1 has all three grades.
    #
    And I give the grade "10" to the user "Student 1" for the grade item "Manual item 1"
    And I give the grade "5" to the user "Student 1" for the grade item "Manual item 2"
    And I give the grade "5" to the user "Student 1" for the grade item "Manual item 3"
    #
    # Student 2 has one grade empty.
    #
    And I give the grade "10" to the user "Student 2" for the grade item "Manual item 1"
    And I give the grade "10" to the user "Student 2" for the grade item "Manual item 2"
    #
    # Student 3 has one explicitly excluded grade.
    #
    And I give the grade "10" to the user "Student 3" for the grade item "Manual item 1"
    And I give the grade "5" to the user "Student 3" for the grade item "Manual item 2"
    And I give the grade "5" to the user "Student 3" for the grade item "Manual item 3"
    #
    # Student 4 has only one zero grade.
    #
    And I give the grade "0" to the user "Student 4" for the grade item "Manual item 1"
    #
    # Student 4 has no grade.
    #
    And I press "Save changes"
    And I turn editing mode off
    #
    # Explicitly exclude a grade from Student 3.
    #
    And I navigate to "View > Single view" in the course gradebook
    And I click on "Users" "link" in the ".page-toggler" "css_element"
    And I click on "Student 3" in the "user" search widget
    And I turn editing mode on
    And I set the field "Exclude for Manual item 3" to "1"
    And I press "Save"
    And I should see "Grades were set for 1 item"
    And I press "Save"
    #
    # Check the grades in the referenced course.
    #
    And I navigate to "View > Grader report" in the course gradebook
    And I turn editing mode off
    And the following should exist in the "user-grades" table:
      | Email address         | -7-               |
      | student1@example.com  | 20.00 (66.67 %)   |
      | student2@example.com  | 20.00 (100.00 %)  |
      | student3@example.com  | 15.00 (75.00 %)   |
      | student4@example.com  | 0.00 (0.00 %)     |
      | student5@example.com  | -                 |

  @javascript
  Scenario: Grades are fetched as real values by default
    Given I am on the "MainCourse" course page logged in as "teacher1"
    And I turn editing mode on
    And I add a "Subcourse" to section "1" and I fill the form with:
      | Subcourse name                    | Unit course 1       |
      | Fetch grades from                 | RefCourse (R)       |
      | Redirect to the referenced course | 0                   |
    And I turn editing mode off
    And I am on "MainCourse" course homepage
    And I navigate to "Setup > Gradebook setup" in the course gradebook
    And I click on "Edit" "link" in the "Unit course 1" "table_row"
    And I click on "Edit settings" "link" in the "Unit course 1" "table_row"
    And I click on "Show more..." "link"
    And I set the following fields to these values:
      | Grade display type  | Real (percentage) |
    And I press "Save changes"
    And I am on "MainCourse" course homepage
    And I am on the "Unit course 1" "subcourse activity" page logged in as teacher1
    When I follow "Fetch grades now"
    And I am on "MainCourse" course homepage
    And I navigate to "View > Grader report" in the course gradebook
    Then the following should exist in the "user-grades" table:
      | Email address         | -4-               |
      | student1@example.com  | 20.00 (66.67 %)   |
      | student2@example.com  | 20.00 (66.67 %)   |
      | student3@example.com  | 15.00 (50.00 %)   |
      | student4@example.com  | 0.00 (0.00 %)     |
      | student5@example.com  | -                 |

  @javascript
  Scenario: Grades can be fetched as percentual values
    Given I am on the "MainCourse" course page logged in as "teacher1"
    And I turn editing mode on
    And I add a "Subcourse" to section "1" and I fill the form with:
      | Subcourse name                    | Unit course 1       |
      | Fetch grades from                 | RefCourse (R)       |
      | Redirect to the referenced course | 0                   |
      | Fetch grades as                   | Percentual values   |
    And I turn editing mode off
    And I am on "MainCourse" course homepage
    And I navigate to "Setup > Gradebook setup" in the course gradebook
    And I click on "Edit" "link" in the "Unit course 1" "table_row"
    And I click on "Edit settings" "link" in the "Unit course 1" "table_row"
    And I click on "Show more..." "link"
    And I set the following fields to these values:
      | Grade display type  | Real (percentage) |
    And I press "Save changes"
    And I am on "MainCourse" course homepage
    And I am on the "Unit course 1" "subcourse activity" page logged in as teacher1
    When I follow "Fetch grades now"
    And I am on the "MainCourse" course page logged in as "teacher1"
    And I navigate to "View > Grader report" in the course gradebook
    Then the following should exist in the "user-grades" table:
      | Email address         | -4-               |
      | student1@example.com  | 20.00 (66.67 %)   |
      | student2@example.com  | 30.00 (100.00 %)  |
      | student3@example.com  | 22.50 (75.00 %)   |
      | student4@example.com  | 0.00 (0.00 %)     |
      | student5@example.com  | -                 |
