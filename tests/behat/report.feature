@local @local_eftimeclock
Feature: The time clock report shows measured time to teachers
  In order to check my students' connection time
  As a teacher
  I need the simple report with its exports

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

  Scenario: The teacher sees the report columns, the exports and one plain line about the full report
    Given I am on the "Course 1" course page logged in as teacher1
    When I navigate to "Time Clock report" in current page administration
    Then I should see "Student"
    And I should see "First measured access"
    And I should see "Last measured access"
    And I should see "Total time"
    And I should see "Alumna"
    And I should see "Download Excel"
    And I should see "Download CSV"
    And I should see "Need audit-ready PDF reports and more filters?"
    And "a[href='https://marketplace.moodle.com/plugins/local_efreport'][target='_blank'][rel='noopener']" "css_element" should exist in the "#etc-marketplace-more" "css_element"
    And I should see "Training Evidence: Time, Activity & Evaluation Report" in the "#etc-marketplace-more" "css_element"
    And I should not see "PRO" in the "region-main" "region"
    And I should not see "Filters, % of the declared course hours"
    And I should not see "Discover the Training Evidence Pack"
    And ".etc-pro-tag" "css_element" should not exist
    And ".etc-pro-card" "css_element" should not exist
    And ".etc-btn-pro" "css_element" should not exist
    And ".etc-banner" "css_element" should not exist
    And "a[href*='elearningfacil.com']" "css_element" should not exist in the "region-main" "region"

  Scenario: A student does not see the report in the course navigation
    When I am on the "Course 1" course page logged in as student1
    Then I should not see "Time Clock report"
