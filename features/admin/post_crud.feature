@managing_posts
Feature: Managing posts
    In order to manage blog posts
    As an Administrator
    I want to be able to create, update, browse and delete posts

    Background:
        Given I am logged in as an administrator

    @ui
    Scenario: Browsing posts
        Given there are 3 posts in the database
        When I browse posts
        Then I should see 3 posts in the list

    @ui
    Scenario: Accessing the post creation form
        When I want to create a new post
        Then I should see the post creation form

    @ui
    Scenario: Creating a new post with basic information
        When I want to create a new post
        And I specify its name as "My First Post"
        And I enable it
        And I add it
        Then I should be notified that it has been successfully created
        And the post "My First Post" should appear in the list

    @ui
#    Scenario: Creating a post with all tabs
#        When I want to create a new post
#        And I specify its translations.name as "Complete Post"
#        And I switch to the "Tab 2" tab
#        And I switch to the "Tab 3" tab
#        And I switch to the "Relations" tab
#        And I add it
#        Then I should be notified that it has been successfully created
#
#    @ui
#    Scenario: Updating a post
#        Given there is a post named "Original Name"
#        When I want to edit this post
#        And I change its name to "Updated Name"
#        And I save my changes
#        Then I should be notified that it has been successfully updated
#        And this post name should be "Updated Name"

#    @ui
#    Scenario: Deleting a post
#        Given there is a post named "Post to Delete"
#        When I browse posts
#        And I delete the post "Post to Delete"
#        Then I should be notified that it has been successfully deleted
#        And I should not see the post "Post to Delete" in the list

#    @ui
#    Scenario: Filtering posts by enabled status
#        Given there are 2 enabled posts
#        And there are 3 disabled posts
#        When I browse posts
#        And I filter by enabled status "Yes"
#        Then I should see 2 posts in the list
#
#    @ui
#    Scenario: Filtering posts by disabled status
#        Given there are 2 enabled posts
#        And there are 3 disabled posts
#        When I browse posts
#        And I filter by enabled status "No"
#        Then I should see 3 posts in the list
