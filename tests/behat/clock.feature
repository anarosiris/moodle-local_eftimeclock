@local @local_eftimeclock
Feature: The floating clock measures students on activity pages
  In order to see my connection time while I study
  As a student
  I need the clock widget on the activities of a course with the clock enabled

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Ana       | Alumna   | student1@example.com |
      | teacher1 | Tim       | Tutor    | teacher1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | teacher1 | C1     | editingteacher |
    And the following "activities" exist:
      | activity | course | idnumber | name      | intro   |
      | page     | C1     | p1       | Test page | Content |

  # The @eftimeclock_standalone scenarios require the free plugin to be the
  # only clock installed (CI / Directory): if the PRO suite (local_efreport)
  # is present, the free plugin steps aside on purpose and its widget does not exist.
  @eftimeclock_standalone
  Scenario: A student sees the clock and the measurement notice on an activity
    When I am on the "Test page" "page activity" page logged in as student1
    Then "#etc-widget" "css_element" should exist
    And "#etc-consent" "css_element" should exist

  Scenario: The teacher is not measured and sees no clock
    When I am on the "Test page" "page activity" page logged in as teacher1
    Then "#etc-widget" "css_element" should not exist

  Scenario: With measurement disabled by default the student sees no clock
    Given the following config values are set as admin:
      | default_enabled | 0 | local_eftimeclock |
    When I am on the "Test page" "page activity" page logged in as student1
    Then "#etc-widget" "css_element" should not exist

  Scenario: The clock only lives on activity pages, not on the course page
    When I am on the "Course 1" course page logged in as student1
    Then "#etc-widget" "css_element" should not exist
