@local_cltools  @_file_upload
Feature: As a user I want to be able to add/edit/view and list entities defined as "Simple entity"

  @javascript
  Scenario:
    Given I am logged in as "admin"
    And I am on the "simple" "local_cltools > Entity add" page
    Then I should see "Add Simple entity"
    And I set the following fields to these values:
    | shortname | Shortname1 |
    | idnumber | IdnumberShortname1 |
    | description | Shortname1 |
    | path | 1234 |
    | sortorder | 1 |
    | scaleid | scale1 |
    And I upload "local/cltools/tests/fixtures/files/icon.png" file to "image" filemanager
    And I press "Save"
    Then I should see "Shortname1"
    And I should see "IdnumberShortname1"
    Then the image at ".image" "css_element" should be identical to "local/cltools/tests/fixtures/files/icon.png"