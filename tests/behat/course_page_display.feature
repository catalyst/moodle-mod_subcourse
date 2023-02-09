@mod @mod_subcourse
Feature: Progress and grade in referenced course can be displayed on the course main page
  In order to control the look and feel of my course outline page
  As a teacher
  I need to be able to configure whether progress and grade in the referenced course is displayed on my course main page

  Background:
    Given the following "users" exist:
      | username      | firstname | lastname  | email                |
      | teacher1      | Teacher   | 1         | teacher1@example.com |
      | student1      | Student   | 1         | student1@example.com |
    And the following "courses" exist:
      | fullname      | shortname | category  |
      | MainCourse    | M         | 0         |
      | RefCourse     | R         | 0         |
    And the following "course enrolments" exist:
      | user          | course    | role              |
      | teacher1      | M         | editingteacher    |
      | student1      | M         | student           |
      | teacher1      | R         | editingteacher    |
      | student1      | R         | student           |
    #
    # Set grades in the referenced course.
    #
    And I log in as "teacher1"
    And I am on "RefCourse" course homepage
    And I navigate to "Settings" in current page administration
    And I set the following fields to these values:
      | Enable completion tracking 	| Yes |
    And I press "Save and display"
    And I turn editing mode on
    And I add a "Text and media area" to section "1" and I fill the form with:
      | Text   | Just a simple module to activate progress tracking |
    And I turn editing mode off
    And I navigate to "Setup > Gradebook setup" in the course gradebook
    And I press "Add grade item"
    And I set the following fields to these values:
      | Item name     | Manual item 1   |
      | Maximum grade | 10              |
    And I press "Save changes"
    And I navigate to "View > Grader report" in the course gradebook
    And I turn editing mode on
    And I give the grade "5" to the user "Student 1" for the grade item "Manual item 1"
    And I press "Save"
    And I turn editing mode off

  @javascript
  Scenario: Progress and grade displayed on both course main page and subcourse view page.
    Given I am on "MainCourse" course homepage
    And I turn editing mode on
    And I add a "Subcourse" to section "1" and I fill the form with:
      | Subcourse name                                          | Unit course 1       |
      | Fetch grades from                                       | RefCourse (R)       |
      | Redirect to the referenced course                       | 0                   |
      | Display progress from referenced course on course page  | 1                   |
      | Display grade from referenced course on course page     | 1                   |
    And I turn editing mode off
    And I am on "MainCourse" course homepage
    And I am on the "Unit course 1" "subcourse activity" page logged in as teacher1
    And I follow "Fetch grades now"
    And I log out
    When I log in as "student1"
    And I am on "MainCourse" course homepage
    Then I should see "Progress:" in the "[data-activityname='Unit course 1']" "css_element"
    And I should see "Current grade:" in the "[data-activityname='Unit course 1']" "css_element"
    And I am on the "Unit course 1" "subcourse activity" page logged in as student1
    And I should see "Progress:" in the ".subcourseinfo-progress" "css_element"
    And I should see "Current grade:" in the ".subcourseinfo-grade" "css_element"

  @javascript
  Scenario: Progress and grade displayed on subcourse view page only.
    Given I am on "MainCourse" course homepage
    And I turn editing mode on
    And I add a "Subcourse" to section "1" and I fill the form with:
      | Subcourse name                                          | Unit course 1       |
      | Fetch grades from                                       | RefCourse (R)       |
      | Redirect to the referenced course                       | 0                   |
      | Display progress from referenced course on course page  | 0                   |
      | Display grade from referenced course on course page     | 0                   |
    And I turn editing mode off
    And I am on "MainCourse" course homepage
    And I am on the "Unit course 1" "subcourse activity" page logged in as teacher1
    And I follow "Fetch grades now"
    And I log out
    When I log in as "student1"
    And I am on "MainCourse" course homepage
    Then I should not see "Progress:" in the "[data-activityname='Unit course 1']" "css_element"
    And I should not see "Current grade:" in the "[data-activityname='Unit course 1']" "css_element"
    And I am on the "Unit course 1" "subcourse activity" page logged in as student1
    And I should see "Progress:" in the ".subcourseinfo-progress" "css_element"
    And I should see "Current grade:" in the ".subcourseinfo-grade" "css_element"
