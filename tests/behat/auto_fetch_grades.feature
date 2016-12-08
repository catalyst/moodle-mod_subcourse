@mod @mod_subcourse
Feature:
  In order to see student's final course grade as a grade item in another course
  As a teacher
  I need to give the final grade in a referenced course and that's enough

  @javascript
  Scenario: Grade is immediately copied from a subcourse to the master course
    Given the following "users" exist:
      | username      | firstname | lastname  | email                |
      | teacher1      | Teacher   | 1         | teacher1@example.com |
      | student1      | Student   | 1         | student1@example.com |
      | student2      | Student   | 2         | student2@example.com |
    And the following "courses" exist:
      | fullname      | shortname | category  |
      | MasterCourse  | M         | 0         |
      | SlaveCourse   | S         | 0         |
    And the following "course enrolments" exist:
      | user          | course    | role              |
      | teacher1      | M         | editingteacher    |
      | student1      | M         | student           |
      | teacher1      | S         | editingteacher    |
      | student1      | S         | student           |
      | student2      | S         | student           |
    And I log in as "admin"
    #
    # We use Mean of grades in this test to be able to override the maximum course grade.
    #
    And I set the following administration settings values:
      | grade_aggregations_visible | Mean of grades |
    And I log out
    #
    # Set grades in the referenced course.
    #
    And I log in as "teacher1"
    And I follow "SlaveCourse"
    And I navigate to "Setup > Gradebook setup" in the course gradebook
    And I click on "Edit" "link" in the "SlaveCourse" "table_row"
    And I click on "Edit settings" "link" in the "SlaveCourse" "table_row"
    And I set the following fields to these values:
      | Aggregation   | Mean of grades  |
      | Maximum grade | 1000            |
    And I press "Save changes"
    And I press "Add grade item"
    And I set the following fields to these values:
      | Item name     | Manual item 1   |
      | Maximum grade | 200             |
    And I press "Save changes"
    And I navigate to "View > Grader report" in the course gradebook
    And I turn editing mode on
    And I give the grade "100" to the user "Student 1" for the grade item "Manual item 1"
    And I give the grade "50" to the user "Student 2" for the grade item "Manual item 1"
    And I press "Save changes"
    And I turn editing mode off
    #
    # Create the subcourse instance.
    #
    And I am on homepage
    And I follow "MasterCourse"
    And I turn editing mode on
    And I add a "Subcourse" to section "1" and I fill the form with:
      | Subcourse name                    | Unit course 1       |
      | Fetch grades from                 | SlaveCourse (S)     |
      | Redirect to the referenced course | 0                   |
    And I turn editing mode off
    And I follow "Unit course 1"
    #
    # Upon creation, no grades are fetched yet.
    #
    Then I should see "The grades have not been fetched yet"
    And I press "Fetch now"
    And I should see "Last fetch:"
    #
    # After fetching, the grades are copied.
    #
    And I press "See all course grades"
    And I navigate to "View > User report" in the course gradebook
    And I set the field "Select all or one user" to "Student 1"
    And the following should exist in the "user-grade" table:
      | Grade item    | Grade | Range   |
      | Unit course 1 | 500   | 0–1000  |
    #
    # Changing grades in the referenced course has instant effect.
    #
    And I am on homepage
    And I follow "SlaveCourse"
    And I navigate to "View > Grader report" in the course gradebook
    And I turn editing mode on
    And I give the grade "150" to the user "Student 1" for the grade item "Manual item 1"
    And I press "Save changes"
    And I turn editing mode off
    And I am on homepage
    And I follow "MasterCourse"
    And I navigate to "View > Grader report" in the course gradebook
    And I should not see "Student 2"
    And I navigate to "View > User report" in the course gradebook
    And I set the field "Select all or one user" to "Student 1"
    And the following should exist in the "user-grade" table:
      | Grade item    | Grade | Range   |
      | Unit course 1 | 750   | 0–1000  |
    #
    # Enrolling a student into the master course brings her grades instantly
    #
    And the following "course enrolments" exist:
      | user          | course    | role              |
      | student2      | M         | student           |
    And I am on homepage
    And I follow "MasterCourse"
    And I navigate to "View > User report" in the course gradebook
    And I set the field "Select all or one user" to "Student 2"
    And the following should exist in the "user-grade" table:
      | Grade item    | Grade | Range   |
      | Unit course 1 | 250   | 0–1000  |
