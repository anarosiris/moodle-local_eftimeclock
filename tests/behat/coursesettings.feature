@local @local_eftimeclock
Feature: Per-course clock settings with the freemium theme gallery
  In order to control the clock of my course
  As a teacher
  I need the on/off switch and the theme gallery with the PRO themes greyed out

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Tim       | Tutor    | teacher1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |

  Scenario: The gallery offers the two free themes and shows the PRO ones disabled
    Given I am on the "Course 1" course page logged in as teacher1
    When I navigate to "Time Clock settings" in current page administration
    Then I should see "Measure time and show the clock"
    And I should see "Sapphire"
    And I should see "Slate"
    And the "Circle" "radio" should be disabled
    And the "Electric" "radio" should be disabled
    And I should see "The other 7 themes come with the Training Evidence Pack by eLearning Fácil."
    And I should see "With the Training Evidence Pack you can also configure"
    And I should see "Time-based completion"
    And I should see "Measure without showing the clock"

  Scenario: Saving the settings persists and confirms
    Given I am on the "Course 1" course page logged in as teacher1
    And I navigate to "Time Clock settings" in current page administration
    When I set the field "Slate" to "1"
    And I press "Save changes"
    Then I should see "Clock settings saved."
