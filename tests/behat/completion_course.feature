@mod @mod_subcourse
Feature: Completing the referenced course can lead to completing the subcourse activity
  In order to complete to Subcourse activity
  As a student
  I need to complete the referenced course, given such a rule is enabled

  Background:
    Given the following "users" exist:
      | username      | firstname | lastname  | email                |
      | teacher1      | Teacher   | 1         | teacher1@example.com |
      | student1      | Student   | 1         | student1@example.com |
    And the following "courses" exist:
      | fullname      | shortname | category  | enablecompletion |
      | MasterCourse  | M         | 0         | 1                |
      | SlaveCourse   | S         | 0         | 1                |
    And the following "course enrolments" exist:
      | user          | course    | role              |
      | teacher1      | M         | editingteacher    |
      | student1      | M         | student           |
      | teacher1      | S         | editingteacher    |
      | student1      | S         | student           |
    And I log in as "teacher1"
    # Create the subcourse instance.
    And I am on "MasterCourse" course homepage
    And I turn editing mode on
    And I add a "Subcourse" to section "1" and I fill the form with:
      | Subcourse name                    | Unit course 1                                     |
      | Fetch grades from                 | SlaveCourse (S)                                   |
      | Redirect to the referenced course | 0                                                 |
      | Completion tracking               | Show activity as complete when conditions are met |
      | Require course completed          | 1                                                 |
      | id_completionexpected_enabled     | 1                                                 |
    # Add the block to a the slave course to allow students to manually complete it
    And I am on "SlaveCourse" course homepage
    And I add the "Self completion" block
    And I navigate to "Course completion" in current page administration
    And I expand all fieldsets
    And I set the following fields to these values:
      | id_criteria_self | 1 |
    And I press "Save changes"
    And I log out

  @javascript
  Scenario: Student is informed about a subcourse to be completed
    When I log in as "student1"
    Then I should see "Unit course 1 should be completed"

  @javascript
  Scenario: Completing the referenced course leads to completing the subcourse
    Given I log in as "student1"
    And I am on "SlaveCourse" course homepage
    And I follow "Complete course"
    And I should see "Confirm self completion"
    And I press "Yes"
    # Running completion task just after clicking sometimes fail, as record should be created before the task runs.
    And I wait "1" seconds
    When I run the scheduled task "core\task\completion_regular_task"
    And I am on "MasterCourse" course homepage
    Then "//img[contains(@alt, 'Completed: Unit course 1')]" "xpath_element" should exist in the "li.modtype_subcourse" "css_element"
    And I log out
    And I log in as "teacher1"
    And I am on "MasterCourse" course homepage
    And I navigate to "Reports > Activity completion" in current page administration
    And "//img[contains(@title, 'Unit course 1') and contains(@title, 'Completed')]" "xpath_element" should exist in the "Student 1" "table_row"
