Feature: User registers new security key

  Scenario: There should be an active registration request
    When I am on "/registration"
    Then the response status code should be 400

  Scenario: Should be able to give assertion
    Given I request for a new registration

