<?php
/*
* This file use for setings at admin site for event alerts settings.
*/
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * WPEM_Event_Tags_Settings class.
 */
class WPEM_Event_Tags_Settings {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct(){		
		add_filter( 'event_manager_settings', array( $this, 'settings' ) );
	}

	/**
	 * Add Settings
	 * @param  array $settings
	 * @return array
	 */
	public function settings( $settings = array() ) {
		$settings['event_listings'][1][] = array(
						'name' 		=> 'event_manager_enable_tag_archive',
						'std' 		=> '1',
						'label' 	=> __( 'Tag Archives', 'wp-event-manager-event-tags' ),
						'cb_label'  => __( 'Enable Tag Archives', 'wp-event-manager-event-tags' ),
						'desc'		=> __( 'Enabling tag archives will make event tags (inside events and tag clouds) link through to an archive of all events with said tag. Please note, tag archives will look like your post archives unless you create a special template to handle the display of event listings called <code>taxonomy-event_listing_tag.php</code> inside your theme. See <a href="http://codex.wordpress.org/Template_Hierarchy#Custom_Taxonomies_display">Template Hierarchy</a> for more information.', 'wp-event-manager-event-tags' ),
						'type'      => 'checkbox'
					);
					$settings['event_listings'][1][] = array(
						'name'       => 'event_manager_tags_filter_type',
						'std'        => 'any',
						'label'      => __( 'Tags Filter Type', 'wp-event-managerer' ),
						'desc'       => __( 'Determines how events are queried when selecting tags.', 'wp-event-manager-event-tags' ),
						'type'       => 'select',
						'options' => array(
							'any' => __( 'Events will be shown if within ANY chosen tag', 'wp-event-manager-event-tags' ),
							'all' => __( 'Events will be shown if within ALL chosen tags', 'wp-event-manager-event-tags' ),
						)
					);
					$settings['event_submission'][1][] = array(
						'name' 		=> 'event_manager_max_tags',
						'std' 		=> '',
						'label' 	=> __( 'Maximum Event Tags', 'wp-event-manager-event-tags' ),
						'desc'		=> __( 'Enter the number of tags per event submission you wish to allow, or leave blank for unlimited tags.', 'wp-event-manager-event-tags' ),
						'type'      => 'number'
					);
					$settings['event_submission'][1][] = array(
						'name' 		=> 'event_manager_tag_input',
						'std' 		=> '',
						'label' 	=> __( 'Tag Input', 'wp-event-manager-event-tags' ),
						'options'   => array(
							''            => __( 'Text box (comma select tags)', 'wp-event-manager-event-tags'),
							'multiselect' => __( 'Multiselect (list of pre-defined tags)', 'wp-event-manager-event-tags'),
							'checkboxes'  => __( 'Checkboxes (list of pre-defined tags)', 'wp-event-manager-event-tags')
						),
						'desc'		=> __( 'Enabling tag archives will make event tags (inside events and tag clouds) link through to an archive of all events with said tag. Please note, tag archives will look like your post archives unless you create a special template to handle the display of event listings called <code>taxonomy-event_listing_tag.php</code> inside your theme. See <a href="http://codex.wordpress.org/Template_Hierarchy#Custom_Taxonomies_display">Template Hierarchy</a> for more information.', 'wp-event-manager-event-tags' ),
						'type'      => 'select'
					);
					return $settings;
	}
}
new WPEM_Event_Tags_Settings();