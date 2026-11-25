@local @local_eftimeclock
Feature: Per-course clock settings with the theme gallery
  In order to control the clock of my course
  As a teacher
  I need the on/off switch and the theme gallery

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

  Scenario: The gallery offers the two themes and nothing that is not part of the plugin
    Given I am on the "Course 1" course page logged in as teacher1
    When I navigate to "Time Clock settings" in current page administration
    Then I should see "Measure time and show the clock"
    And I should see "Sapphire"
    And I should see "Slate"
    And "Circle" "radio" should not exist
    And "Electric" "radio" should not exist
    And I should not see "PRO" in the "region-main" "region"
    And I should not see "The other 7 themes come with"
    And I should not see "you can also configure"
    And I should not see "Time-based completion"
    And ".etc-pro-tag" "css_element" should not exist
    And ".etc-pro-card" "css_element" should not exist
    And "a[href*='elearningfacil.com']" "css_element" should not exist in the "region-main" "region"

  Scenario: Saving the settings persists and confirms
    Given I am on the "Course 1" course page logged in as teacher1
    And I navigate to "Time Clock settings" in current page administration
    When I set the field "Slate" to "1"
    And I press "Save changes"
    Then I should see "Clock settings saved."
