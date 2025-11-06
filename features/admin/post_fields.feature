@managing_posts
Feature: Managing post fields
    In order to fully configure blog posts
    As an Administrator
    I want to be able to use all available field types

    Background:
        Given I am logged in as an administrator

    @ui
    Scenario: Using translation fields
        When I want to create a new post
        And I specify its name as "Test Post" in "English (United States)" locale
        And I specify its description as "Test Description" in "English (United States)" locale
        And I add it
        Then I should be notified that it has been successfully created
        And the post "Test Post" should have description "Test Description"

    @ui
    Scenario: Using checkbox field for enabled status
        When I want to create a new post
        And I specify its name as "Enabled Post"
        And I enable it
        And I add it
        Then I should be notified that it has been successfully created
        And the post "Enabled Post" should be enabled

    @ui
    Scenario: Using enum field for state
        When I want to create a new post
        And I specify its name as "Post with State"
        And I switch to the "Tab 2" tab
        And I select state "Published"
        And I add it
        Then I should be notified that it has been successfully created
        And the post "Post with State" should have state "Published"

    @ui
    Scenario: Using icon field
        When I want to create a new post
        And I specify its name as "Post with Icon"
        And I select icon "file"
        And I add it
        Then I should be notified that it has been successfully created

    @ui
    Scenario: Using code editor field
        When I want to create a new post
        And I specify its name as "Post with Code"
        And I switch to the "Tab 2" tab
        And I fill the code editor with valid JSON
        And I add it
        Then I should be notified that it has been successfully created

    @ui @javascript
    Scenario: Using image field
        When I want to create a new post
        And I specify its name as "Post with Image"
        And I switch to the "Tab 3" tab
        And I attach an image
        And I add it
        Then I should be notified that it has been successfully created

    @ui @javascript
    Scenario: Using oembed field for video embed
        When I want to create a new post
        And I specify its name as "Post with Video"
        And I switch to the "Tab 3" tab
        And I specify embed URL as "https://www.youtube.com/watch?v=dQw4w9WgXcQ"
        And I add it
        Then I should be notified that it has been successfully created

    @ui @javascript
    Scenario: Using resource choice field for taxon selection
        Given there is a taxon named "Category 1"
        When I want to create a new post
        And I specify its name as "Post with Taxon"
        And I switch to the "Relations" tab
        And I select taxon "Category 1"
        And I add it
        Then I should be notified that it has been successfully created
        And the post "Post with Taxon" should have taxon "Category 1"

    @ui @javascript
    Scenario: Using resource choice field for multiple products
        Given there is a product named "Product 1"
        And there is a product named "Product 2"
        When I want to create a new post
        And I specify its name as "Post with Products"
        And I switch to the "Relations" tab
        And I select products "Product 1" and "Product 2"
        And I add it
        Then I should be notified that it has been successfully created
        And the post "Post with Products" should have 2 products

    @ui @javascript
    Scenario: Using sortable collection field
        When I want to create a new post
        And I specify its name as "Post with Collection"
        And I switch to the "Relations" tab
        And I add a new collection item with data
        And I add it
        Then I should be notified that it has been successfully created

    @ui
    Scenario: Using choice mask field with conditional fields
        When I want to create a new post
        And I specify its name as "Post with Conditional Fields"
        And I switch to the "Choice mask" tab
        And I select choice mask option "both"
        Then I should see field "virtual1"
        And I should see field "virtual2"

    @ui
    Scenario: Choice mask field shows only selected fields
        When I want to create a new post
        And I specify its name as "Post with Single Field"
        And I switch to the "Choice mask" tab
        And I select choice mask option "virtual1"
        Then I should see field "virtual1"
        And I should not see field "virtual2"

    @ui
    Scenario: Using column layout with tabs
        When I want to create a new post
        Then I should see tab "Tab 1"
        And I should see tab "Tab 2"
        And I should see tab "Tab 3"
        And I should see tab "Relations"
        And I should see tab "Choice mask"

    @ui
    Scenario: Virtual fields do not appear in index
        Given there is a post named "Test Post"
        When I browse posts
        Then I should not see virtual date fields in the list
