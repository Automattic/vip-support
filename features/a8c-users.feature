Feature: Automattic users are in the VIP Support Role
  As a site owner
  In order to distinguish support users
  I must clearly see who is an VIP Support user

  @javascript @insulated
  Scenario: Adding an A8c user requires them to verify their email address
    Given I am logged in as "admin" with the password "password" and I am on "/wp-admin/user-new.php"
    Then I should see "Add New User"
    When I fill in "Username" with "actual_a8c_user"
    And I fill in "E-mail" with "actual_a8c_user@automattic.com"
    And I fill in "Password" with "password"
    And I fill in "Repeat Password" with "password"
    And I select "contributor" from "Role"
    And I press "Add New User"
    Then I should not see "This user was given the VIP Support role"
    And I should see "This user’s Automattic email address is not verified"
    When I follow "actual_a8c_user"
    Then I should see "Personal Options"
    Then I should see "This user’s Automattic email address is not verified"

  @javascript @insulated
  Scenario: A8c users with unverified emails are not in the VIP Support role
    Given I am logged in as "admin" with the password "password" and I am on "/wp-admin/users.php?role=vip_support"
    Then I should see "Users"
    And I should not see "actual_a8c_user"

  @javascript @insulated
  Scenario: Trying to assign A8c users to the VIP Support role fails and shows an error
    Given I am logged in as "admin" with the password "password" and I am on "/wp-admin/users.php?s=actual_a8c_user"
    And I follow "actual_a8c_user"
    And I select "VIP Support" from "Role"
    And I press "Update User"
    Then I should see "This user’s Automattic email address is not verified"
    And I am on "/wp-admin/users.php?role=vip_support"
    Then I should not see "actual_a8c_user"

  @javascript @insulated
  Scenario: Following the verification link in the email verifies the user's email successfully
    Given I am logged in as "actual_a8c_user" with the password "password" and I am on "/wp-admin/"
    And I follow the second URL in the latest email to actual_a8c_user@automattic.com
    Then I should see "Your email has been verified as actual_a8c_user@automattic.com"
    When I am on "/wp-admin/profile.php"
    Then I should see "email is verified"

  @javascript @insulated
  Scenario: A8c users with verified email are in the VIP Support role
    Given I am logged in as "admin" with the password "password" and I am on "/wp-admin/users.php?role=vip_support"
    Then I should see "actual_a8c_user"

