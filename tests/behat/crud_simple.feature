@local_cltools @local
Feature: As a user I want to be able to add/edit/view and list entities defined as "Simple entity"

  Background:
    Given the following "roles" exist:
      | shortname         | name                | archetype |
      | dynamictableread  | Dynamic Table Read  | user      |
      | dynamictablewrite | Dynamic Table Write | user      |
    And the following "permission overrides" exist:
      | capability                      | permission | role              | contextlevel | reference |
      | local/cltools:dynamictableread  | Allow      | dynamictableread  | System       |           |
      | local/cltools:dynamictablewrite | Allow      | dynamictablewrite | System       |           |
    And the following "users" exist:
      | username     | firstname | lastname | email                    |
      | simpleuser   | User      | Simple   | usersimple@example.com   |
      | userreadonly | User      | Read     | userreadonly@example.com |
    And the following "role assigns" exist:
      | user         | role             | contextlevel | reference |
      | userreadonly | dynamictableread | System       |           |

  @javascript @_file_upload
  Scenario: I can create a new entity using crud form
    Given I am logged in as "admin"
    And I am on the "simple" "local_cltools > Entity add" page
    Then I should see "Add Simple entity"
    And I set the following fields to these values:
      | shortname   | Shortname1         |
      | idnumber    | IdnumberShortname1 |
      | description | Shortname1         |
      | path        | 1234               |
      | sortorder   | 1                  |
      | scaleid     | scale1             |
    And I upload "local/cltools/tests/fixtures/files/icon.png" file to "image" filemanager
    And I press "Save"
    Then I should see "Shortname1"
    And I should see "IdnumberShortname1"
    Then the image at ".image img" "css_element" should be identical to "local/cltools/tests/fixtures/files/icon.png"

  @javascript
  Scenario: As an admin I can list entities
    Given I am logged in as "admin"
    And the following "local_cltools > entities" exist:
      | entitynamespace | shortname   | idnumber           | description | path  | sortorder |
      | simple          | Shortname 1 | IdnumberShortname1 | Shortname1  | 1234  | 1         |
      | simple          | Shortname 2 | IdnumberShortname2 | Shortname2  | 12345 | 1         |
    And I am on the "simple" "local_cltools > Entity index" page
    Then I should see "Shortname1"
    Then I should see "Shortname2"
    And I should see "IdnumberShortname1" in the "Shortname1" "local_cltools > Table Row"

  @javascript
  Scenario: As an admin can delete entities
    Given I am logged in as "admin"
    And the following "local_cltools > entities" exist:
      | entitynamespace | shortname   | idnumber           | description | path  | sortorder |
      | simple          | Shortname 1 | IdnumberShortname1 | Shortname1  | 1234  | 1         |
      | simple          | Shortname 2 | IdnumberShortname2 | Shortname2  | 12345 | 1         |
    And I am on the "simple" "local_cltools > Entity index" page
    Then I click on "Delete" "local_cltools > row_action_button" in the "Shortname1" "local_cltools > Table Row"
    Then I click on "Continue" "button"
    Then I click on "Continue" "button"
    Then I should see "Shortname2"
    But I should not see "Shortname1"

  @javascript
  Scenario: As an admin I can view created entities
    Given I am logged in as "admin"
    And the following "local_cltools > entities" exist:
      | entitynamespace | shortname   | idnumber           | description | path  | sortorder | image                                       |
      | simple          | Shortname 1 | IdnumberShortname1 | Shortname1  | 1234  | 1         | local/cltools/tests/fixtures/files/icon.png |
      | simple          | Shortname 2 | IdnumberShortname2 | Shortname2  | 12345 | 1         | local/cltools/tests/fixtures/files/icon.png |
    And I am on the "simple" "local_cltools > Entity index" page
    Then I click on "View" "local_cltools > row_action_button" in the "Shortname1" "local_cltools > Table Row"
    But I should see "Shortname1"

  @javascript
  Scenario: As a user I can view created entities
    Given I am logged in as "admin"
    And the following "local_cltools > entities" exist:
      | entitynamespace | shortname   | idnumber           | description | path  | sortorder | image                                       |
      | simple          | Shortname 1 | IdnumberShortname1 | Shortname1  | 1234  | 1         | local/cltools/tests/fixtures/files/icon.png |
      | simple          | Shortname 2 | IdnumberShortname2 | Shortname2  | 12345 | 1         | local/cltools/tests/fixtures/files/icon.png |
    And I am on the "simple" "local_cltools > Entity index" page
    Then I click on "View" "local_cltools > row_action_button" in the "Shortname1" "local_cltools > Table Row"
    But I should see "Shortname1"

  @javascript
  Scenario Outline: As a user with the 'local/cltools:dynamictableread' capability I should be able to see the list
    Given the following "local_cltools > entities" exist:
      | entitynamespace | shortname   | idnumber           | description | path  | sortorder |
      | simple          | Shortname 1 | IdnumberShortname1 | Shortname1  | 1234  | 1         |
      | simple          | Shortname 2 | IdnumberShortname2 | Shortname2  | 12345 | 1         |
    And I am logged in as "<user>"
    And I am on the "simple" "local_cltools > Entity index" page
    Then I <cansee>
    Examples:
      | user         | cansee                                                                             |
      | userreadonly | should see "Shortname1"                                                            |
      | simpleuser   | should see "Sorry, you do not have permissions to read the content of this table." |
      | admin        | should see "Shortname1"                                                            |
