=== Easily manage all your WordPress code ===
Contributors: WPCodeBox 2
Requires at least: 5.0
Tested up to: 6.2.2
Stable tag: 1.0.3

WPCodeBox is a complete WordPress snippet manager. With WPCodeBox you can manage all of your site's code without touching functions.php.


== Description ==

= WPCodeBox - Complete WordPress Snippet Manager =

WPCodeBox is a complete WordPress snippet manager. With WPCodeBox you can manage all of your site's code without touching functions.php.

== Changelog ==

= 1.0.3 (Released on May 28th 2023) =

* Bugfix: Snippet order not preserved when reordering using drag and drop

= 1.0.2 (Released on May 24th 2023) =

* New Feature: Add "Unlink from Cloud" button to snippets context menu
* Bugfix: Autoreload not working when both WPCodeBox and WPCodeBox 2 are installed
* Bugfix: Snippets with very long descriptions not saved
* Bugfix: Snippets with many conditions not saved
* Bugfix: Folder order not preserved
* Bugfix: In some cases, deleting a cloud snippet causes a local error
* Bugfix: Plain text snippets causing errors in some cases
* Bugfix: In some rare cases, CSS and SCSS snippets can be saved with the plugins_loaded hook
* Bugfix: Functionality plugin generating errors in some cases


= 1.0.1 (Released on May 17th 2023) =

* Bugfix: Warning when both WPCodeBox and WPCodeBox 2 are installed
* Bugfix: Async and defer options not rendered on external JS tags
* Bugfix: Custom shortcode parameters are not passed to custom shortcodes
* Bugfix: Create/download from cloud not working for very large snippets
* Bugfix: Frontend header (After pagebuilders) hook not rendering JS and CSS snippets
* Bugfix: Deprecated notices in PHP 8.2 in the update library
* Bugfix: WPCodeBox error page is showing for non-WPCodeBox errors


= 1.0.0 (Released on May 10th 2023) =

* New Feature: Monaco Editor
* New Feature: Autocomplete for all WordPress actions & filters & Parameters
* New Feature: Functionality Plugin (Experimental)
* New Feature: WooCommerce hooks snippet insertion for HTML and PHP Snippets
* New Feature: Color picker for CSS/SCSS/LESS
* New Feature: SCSS Partials
* New Feature: Render PHP/HTML snippets using custom shortcodes
* New Feature: Actions and custom actions for rendering snippets
* New Feature: Option to render CSS/SCSS after page builders’ CSS
* New Feature: Show local variables in autocomplete
* New Feature: Save UI Settings to the cloud
* New Feature: Execute PHP snippets using a secure external URL
* New Feature: Collapse left/right panes using Ctrl + 1/Ctrl + 2
* New Feature: Added do not render to PHP snippets so they can be included via code
* New Feature: Emmet support
* New Feature: Oxygen Color Integration
* New Feature: Bricks Color Integration
* New Feature: Automatic CSS Integration
* New Feature: WordPress hooks and action reference on hover
* New Feature: Code map that can be disabled in settings
* New Feature: CSS Variables support and autocomplete
* New Condition: User logged in
* New Condition: User device (mobile/desktop)
* Improvement: Use custom tables to store data for better performance
* Improvement: Added info about safe mode on the error page
* Improvement: Show notice when Safe Mode is active
* Improvement: Added “Reload Local Snippets” button
* Improvement: Removed jQuery from Live Reload CSS
* Improvement: Close the context menu when clicking on another snippet
* Improvement: Added post name to the WPCodeBox custom post types
* Improvement: Removed arrow from priority input in Firefox
* Improvement: Complete backend rewrite for improved performance
* Improvement: Better error detection and handling
* Improvement: Add loader when running manual snippets
* Improvement: Allow the saving of SCSS/LESS snippets even if the compilation fails
* Improvement: Action/priority/shortcode are saved to the cloud
* Improvement: Set “plugins_loaded” as the default action for PHP snippets
* Improvement: Make the editor fill the height
* Improvement: Removed the plugins_loaded notice
* Improvement: Added wp_body_open hook
* Improvement: Fire wpcb_snippet_disabled action when a snippet is disabled
* Improvement: Small security improvements
* Bugfix: PHP Notice when using the post parent conditions for posts that don't have a parent
* Bugfix: When editing cloud snippets, the name is not updated in the list automatically
* Bugfix: The key is not checked on autoreload, causing compatibility issues with some plugins
* Bugfix: Snippet status not updated when downloading a snippet from the cloud
* Bugfix: Taxonomy “Is not” condition is not working correctly
* Bugfix: LESS is not working on PHP 8
* Bugfix: Current snippet is not always selected when refreshing the page
* Bugfix: Unsaved changes notification appears when there are no unsaved changes
* Bugfix: Delete snippets from the context menu doesn’t always work
