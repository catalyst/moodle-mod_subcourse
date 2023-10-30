@mod @mod_subcourse
Feature: Clicking the subcourse instance in the course outline may or may not redirect to the referenced course
  In order to visit the referenced course
  As a user
  I need to visit the subcourse activity and either click a link or there is no need to do so

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
    And I log in as "teacher1"
    And I am on "MainCourse" course homepage
    And I turn editing mode on

  @javascript
  Scenario: Student has to click the link to the referenced course manually
    And I add a "Subcourse" to section "1" and I fill the form with:
      | Subcourse name                    | Unit course 1       |
      | Fetch grades from                 | RefCourse (R)       |
      | Redirect to the referenced course | 0                   |
    And I log out
    When I log in as "student1"
    And I am on "MainCourse" course homepage
    And I follow "Unit course 1"
    Then I should see "Go to RefCourse"
    And I follow "RefCourse"
    And I should see "RefCourse" in the "page-header" "region"

  @javascript
  Scenario: Student is instantly redirected to the referenced course
    And I add a "Subcourse" to section "1" and I fill the form with:
      | Subcourse name                    | Unit course 1       |
      | Fetch grades from                 | RefCourse (R)       |
      | Redirect to the referenced course | 1                   |
    And I log out
    When I log in as "student1"
    And I am on "MainCourse" course homepage
    And I follow "Unit course 1"
    Then I should see "RefCourse" in the "page-header" "region"

  @javascript
  Scenario: Teacher is not redirected instantly even if that is enabled
    And I add a "Subcourse" to section "1" and I fill the form with:
      | Subcourse name                    | Unit course 1       |
      | Fetch grades from                 | RefCourse (R)       |
      | Redirect to the referenced course | 1                   |
    And I am on the "Unit course 1" "subcourse activity" page logged in as teacher1
    Then I should see "Go to RefCourse"
    And I follow "RefCourse"
    And I should see "RefCourse" in the "page-header" "region"

  @javascript
  Scenario: Teacher is redirected instantly if unable to fetch grades manually
    And I add a "Subcourse" to section "1" and I fill the form with:
      | Subcourse name                    | Unit course 1       |
      | Fetch grades from                 | RefCourse (R)       |
      | Redirect to the referenced course | 1                   |
      | ID number                         | subcourse1          |
    And the following "permission overrides" exist:
      | capability                    | permission  | role            | contextlevel    | reference   |
      | mod/subcourse:fetchgrades     | Prevent     | teacher         | Activity module | subcourse1  |
      | mod/subcourse:fetchgrades     | Prevent     | editingteacher  | Activity module | subcourse1  |
    And I am on "MainCourse" course homepage
    And I am on the "Unit course 1" "subcourse activity" page logged in as teacher1
    And I am on "RefCourse" course homepage
    Then I should see "RefCourse" in the "page-header" "region"
