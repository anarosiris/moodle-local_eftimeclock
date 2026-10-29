@local @local_eftimeclock
Feature: The time clock report shows measured time to teachers
  In order to check my students' connection time
  As a teacher
  I need the simple report with its free exports and the PRO showcase

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

  Scenario: The teacher sees the report columns, the free exports and the PRO strip
    Given I am on the "Course 1" course page logged in as teacher1
    When I navigate to "Time Clock report" in current page administration
    Then I should see "Student"
    And I should see "First measured access"
    And I should see "Last measured access"
    And I should see "Total time"
    And I should see "Alumna"
    And I should see "Download Excel"
    And I should see "Download CSV"
    And I should see "Audit-ready PDF"
    And I should see "Filters, % of the declared course hours"
    And I should see "The Training Evidence Pack lets you prove it."
    And I should see "Discover the Training Evidence Pack"

  Scenario: A student does not see the report in the course navigation
    When I am on the "Course 1" course page logged in as student1
    Then I should not see "Time Clock report"
