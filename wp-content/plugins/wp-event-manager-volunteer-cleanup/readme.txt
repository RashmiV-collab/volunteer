=== WP Event Manager – Volunteer CleanUp ===

Current Version : 2.6.0
Event Manager at least : 3.1.39
Fluent CRM atleast : 2.8.34
Requires PHP: 5.6
iCal : 1.1.4

== Description ==

Custom changes by client requirement : 

1. New Event Posted - Admin Notification
When a new event is posted from frontend website admin is supposed to get an email notification, setting from email notification is enabled in WPEvent Manager settings but emails are not generating, there is no record for these emails in mail logs either.

Solutions : 
add_action( 'event_manager_event_submitted', [$this,'volunteer_event_admin_mail'],100,1 ); 
Added Admin mail content at the submission of event.

-----------
2. Embeddable Event Widget
Customize this widget to add option to only list events that belong to organizer that is generating this widget.

Solutions : 
add_action( 'wp_footer', [$this,'deregister_embeddable_form'] ,5,0);
add_filter('event_manager_get_listings', [$this,'volunteer_event_manager_get_listings'],10,2);

* Changes done in both form and custom js and embed js.
* CHanges done in event fetching arguments.
* Template of Form Embed.
-----------
3. Sync Organizer Profile and FluentCRM Contact Fields
If a user signs up for account they can login and access their profile to edit. This profile contains fields that sync with FluentCRM contact fields for same user. Updating the fields from frontend or from within FluentCRM contact updates the other. 

Solutions :
// Syncing Fluent CRM
add_action('fluentform/notify_on_form_submit', [$this,'volunteer_notify_on_form_submit'],10,3);
add_action('admin_menu', [$this, 'add_2_1_sub_menu'],100,0);
add_action( 'save_post_event_organizer', array($this,'volunteer_sync_fluent_data'), 100, 3 ); 
add_filter( 'the_content', array($this,'volunteer_disable_wp_auto_p'), 0 );

* Created Fluent CRM Manager and its API credentails.
- Go to FluentCRM Dashboard ➜ Managers ➜ Add New Manager
- This will open a popup and now input the email address which we collected from the previous step. In the next section, there are a lot of permissions available to be assigned to the user or the new manager.
- When you are done with adding the new manager you will see them listed as CRM Managers along with their assigned permissions.
- Then , go to  FluentCRM Dashboard ➜ REST API ➜ Add New Key
- Now When you are done adding a FluentCRM Manager and note the email address and then Add a New Key by providing the name of the key and then associating the FluentCRM Manager to it.
- After clicking on the Create Button you will be provided with an API Username and an API Password.
- Save the credentials over Volunteer CleanUp ➜ Fluent Manager

* Admin page to save the credentials.
* Code to sync data
* Fluent CRM wp-admin to Oganizer wp-admin - jan16
-----------

4 - Frontend - Delete Event
Issue : Due to elementor pae setup, event dashboard basic functions are not working.
Solutions: We have called basic event dashboard file on delete.

------------

5 - Registration and Sell Tickets Discrepency
Solutions : we have fixed the issue of Registration and Sell Tickets Discrepancy. It was coming due to a wrong calculation done by the sell tickets plugin. The plugin is counting ticket sales of those events that no longer exist. We have corrected the code through the custom plugin.

------------

6 - Event Preview Page 
solutions : we hae skip the step.

------------

7 - Weekly Alerts Emails Log
Solutions : Custom Work - Stats as been added.

------------

8- iCal
Solutions : Additional Data has been added. 


------------

changes - Message All Attendees - Link Formatting 
Feb,2024
once organizer sends a message pop-up should clear content or close, currently there is no way for organizer to know if message was send successfully. So perhaps popup can clear the form and hide it and then show a success message. Organizers can close out the popup and open again if they need to send another message
Solutions : New change has been done.


-------------

Version changes to 2.2 - March 4,2024
Changes Done

1. New Script for Alert mail on basis on Alert address. Send Grid is used to send mails to users.
Code Done in : server-cron/volunteer-server-cron-send-grid.php
CURL Url : site_url().'/wp-json/volunteer-cron/v1/alert-mail-sendGrid'

2. Fluent CRM syncing - make configuation changes by server guy.
Code Done in : wpem-volunteer-cleanup-2-1.php

3. Email Templates : Two templates - Organizer and Volunteer
Code done in - event-mails/wpem-volunteer-cleanup-em-mails.php

4. Ticket PDF is attached to email confirmation
Code Done in : event-mails/wpem-volunteer-cleanup-em-mails.php
Update filter values forcefully by code

5. Ticket Start Date Bug
Code Done in : event-mails/wp-event-manager-volunteer-cleanup.php
Set dateformat

6. 2 Emails to host on every registration order instead of 1
Code Done in : event-mails/wpem-volunteer-cleanup-em-mails.php
System itself is sending two mails. Forcefully stop by code.

7. Checkout process workflow flaw for existing accounts 
Code done in : wpem-volunteer-cleanup-2-2.php

Bug fixes :
1. New Cleanups aren't setting expiration date +1 anymore
2. Listing Expire Date - Needs to be +1 day

-------------

Version changes to 2.2.05 - March 8,2024
Changes Done

Option provided between wp_mail and sendGrid. Client can used any feature as per his wish

-------------

Version changes to 2.2.1 - March 15,2024
Changes Done
1. Cancelled Tickets don't go back into the Pool of Tickets
Code done in : wpem-volunteer-cleanup-2-2.php

2. Confirmed vs. Total Registrations - Just to remove option 
Template - event-registration-edit.php
content-tickets-details.php

3. Fix Front End Post Event - registration limit, attendee information collection and Paid Ticket option 
Template - event-submit.php
multiselect-field.php

------------
Version changes to 2.2.2 - March 15,2024
All Registrations are coming for user who haven't created any event - Issue resolved

------------
Version changes to 2.2.3 - March 22,2024
Organizer and FluentCRM syn - Issue resolved
Emebbed Plugin

------------
Version changes to 2.2.4 - March 20,2024
SendGrid Live

------------
Version changes to 2.2.5 - April 1,2024
Handling FuentForm ff_profile_bio textarea

------------
Version changes to 2.2.6 - June 6,2024
Add phone Number to registrants form on checkout page

1. Add field with "_attendee_phone" name at Registration Form ( wp-admin/edit.php?post_type=event_registration&page=event-registrations-form-editor ) with Field label : (XXX) xxx-xxxx and placeholder : (XXX) xxx-xxxx

2. The add "attendee_name : Attendee Name |attendee_email : Attendee Email |_attendee_phone : Phone Number |needs_community_service_hours : Needs Community Service Hours? " at Form fields( wp-admin/edit.php?post_type=event_listing&page=event-manager-form-editor)
** make sure name should be _attendee_phone

------------
Version changes to 2.3.1 - Aug 23,2024
Features added : 
1. Add iCal to checkout confirmation page
2. Create An Alert - New Process
3. Thank You Page Shortcodes
4. Tagging
5. Create An Account / Registration - New Process
6. Visual Feedback when organizer sending message to all RSVP's
7. VC plugin - Alerts

Documentation added to wp-admin/admin.php?page=volunteer--settings
Manadatory Add : 
A. volunteer--settings
B. Fluent form should be same as development site in terms of 
	1. field name and its default values
	2. Confirmation Settings & Double Optin Confirmation
	3. All form Integration

------------
Version changes to 2.3.2 - Sept 25,2024
Features added : 
1. Email Name Cut Off
2. Sendgrid fixes
3. add/remove 'weekly-alert' and 'newsletter' tagging 

------------
Version changes to 2.4.3 - Jan 27,2025
Features added : 
1. Duplicate Event for Backend
2. Duplicate Event for Organizer
3. Remove 'zip code' as a tag for users signing up for weekly alerts.
4. RSVP download sheet - delete a column
5. Display upcoming events on Organizers Profile Page in date sequence
6. BUG: guest overwriting buyer name in Fluent CRM
7. Tag existing people who have weekly alerts with 'Weekly Alert' in FluentCRM Script
8. Add 'Duplicate Page' plugin to Wordpress for pages. plugin Duplicate Page  added.
9. Attendee Phone Number - make not mandatory
10. Cancel my RSVP (no user account)
11. Logging 