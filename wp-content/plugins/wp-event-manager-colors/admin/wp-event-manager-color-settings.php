<?php
/*
* This file use for setings at admin site for event colors  settings.
*/
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * WP_Event_Manager_Colors_Settings class.
 */
class WP_Event_Manager_Colors_Settings {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() 
    {		
			add_filter( 'event_manager_settings', array( $this, 'wp_event_colors_settings' ) );

			add_action( 'admin_enqueue_scripts', array( $this, 'colorpickers' ) );
			add_action( 'admin_footer', array( $this, 'colorpickersjs' ) );
	}

		/**
	 * gam_event_alerts_settings function.
	 *
	 * @access public
	 * @return void
	 */
	public function wp_event_colors_settings($settings) 
	{
		$event_types   = get_terms( 'event_listing_type', array( 'hide_empty' => false ) );
		$event_category   = get_terms( 'event_listing_category', array( 'hide_empty' => false ) );
		$fields = array();
		
		//add event types
		if(get_option( 'event_manager_enable_event_types' ) && !empty($event_types)){
    		$fields[] = array(
    							'name' 		  => 'event_manager_event_type_color',
    							'std' 		  => 'background',
    							'placeholder' => '',
    							'label' 	  => __( 'Event Type', 'wp-event-manager-colors' ),
    							'desc'        => __( 'Color will apply on ?', 'wp-event-manager-colors' ),
    							'type'        => 'label',
    						);
    		
    		foreach ( $event_types as $term ) {
    			$fields[] = array(
    				'name' 		  => 'event_manager_event_type_' . $term->slug . '_text_color',
    				'std' 		  => '',
    				'placeholder' => '#',
    				'label' 	  => sprintf(__('<strong>%s - Text Color</strong>', 'wp-event-manager-colors'), $term->name),
    				'desc'		  => __( 'Hex value for the text color of this event type.', 'wp-event-manager-colors' ),
    				'attributes'  => array(
    					'data-default-color' => '#fff',
    					'data-type'          => 'colorpicker'
    				)
    			);
    			$fields[] = array(
    				'name' 		  => 'event_manager_event_type_' . $term->slug . '_color',
    				'std' 		  => '',
    				'placeholder' => '#',
    				'label' 	  => sprintf(__('<strong>%s - Background</strong>', 'wp-event-manager-colors'), $term->name),
    				'desc'		  => __( 'Hex value for the background color of this event type.', 'wp-event-manager-colors' ),
    				'attributes'  => array(
    					'data-default-color' => '#fff',
    					'data-type'          => 'colorpicker'
    				)
    			);
    		}
		}
		
		//add event category
		if(get_option( 'event_manager_enable_categories' ) && !empty($event_category)){
        	$fields[] = array(
        					'name' 		  => 'event_manager_event_category_color',
        					'std' 		  => 'background',
        					'placeholder' => '',
        					'label' 	  => __( 'Event Category', 'wp-event-manager-colors' ),
        					'desc'        => __( 'Color will apply on ?', 'wp-event-manager-colors' ),
        					'type'        => 'label',
        					'options'     => array(
        						'background' => __( 'Background', 'wp-event-manager-colors' ),
        						'text'       => __( 'Text', 'wp-event-manager-colors' )
        					)
        				);    						
    		
    		foreach ( $event_category as $term ) {
    			$fields[] = array(
    				'name' 		  => 'event_manager_event_category_' . $term->slug . '_text_color',
    				'std' 		  => '',
    				'placeholder' => '#',
    				'label' 	  => sprintf( __('<strong>%s - Text Color </strong>', 'wp-event-manager-colors'), $term->name),
    				'desc'		  => __( 'Hex value for the text color of this event category.', 'wp-event-manager-colors' ),
    				'attributes'  => array(
    					'data-default-color' => '#fff',
    					'data-type'          => 'colorpicker'
    				)
    			);
    			$fields[] = array(
    				'name' 		  => 'event_manager_event_category_' . $term->slug . '_color',
    				'std' 		  => '',
    				'placeholder' => '#',
    				'label' 	  => sprintf( __('<strong>%s - Background</strong>', 'wp-event-manager-colors'), $term->name),
    				'desc'		  => __( 'Hex value for the background color of this event category.', 'wp-event-manager-colors' ),
    				'attributes'  => array(
    					'data-default-color' => '#fff',
    					'data-type'          => 'colorpicker'
    				)
    			);
    		}
		}
		$settings['event_colors'] = array(
			__( 'Type & Category Colors', 'wp-event-manager-colors' ),
			apply_filters(
				'wp_event_manager_colors_settings', $fields
			)
		);
		
		return $settings;
	} 

	public function colorpickers( $hook ) {
		$screen = get_current_screen();

		if ( 'event_listing_page_event-manager-settings' != $screen->id )
			return;

		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_style( 'wp-color-picker' );
	}

	public function colorpickersjs() {
		$screen = get_current_screen();

		if ( 'event_listing_page_event-manager-settings' != $screen->id )
			return;
		?>
			<script>
				jQuery(document).ready(function($){
					$( 'input[data-type="colorpicker"]' ).wpColorPicker();
				});
			</script>
		<?php
	}

}

new WP_Event_Manager_Colors_Settings();
