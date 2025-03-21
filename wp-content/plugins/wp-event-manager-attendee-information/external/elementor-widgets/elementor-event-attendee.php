<?php
namespace WPEventManagerAttendee\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Elementor Event Attendee Information
 *
 * Elementor widget for event attendee information.
 *
 */
class Elementor_Event_Attendee extends Widget_Base {

	/**
	 * Retrieve the widget name.
	 *
	 * @access public
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'attendee-information';
	}

	/**
	 * Retrieve the widget title.
	 *
	 * @access public
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return __( 'Attendee Information', 'wp-event-manager-attendee-information' );
	}
	/**	
	 * Get widget icon.
	 *
	 * Retrieve shortcode widget icon.
	 *
	 * @access public
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'eicon-posts-grid';
	}
	/**
	 * Get widget keywords.
	 *
	 * Retrieve the list of keywords the widget belongs to.
	 *
	 * @access public
	 *
	 * @return array Widget keywords.
	 */
	public function get_keywords() {
		return [ 'attendee-information', 'code' ];
	}

	/**
	 * Retrieve the list of categories the widget belongs to.
	 *
	 * Used to determine where to display the widget in the editor.
	 *
	 * Note that currently Elementor supports only one category.
	 * When multiple categories passed, Elementor uses the first one.
	 *
	 * @access public
	 *
	 * @return array Widget categories.
	 */
	public function get_categories() {
		return [ 'wp-event-manager-categories' ];
	}

	/**
	 * Register the widget controls.
	 *
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @access protected
	 */
	protected function _register_controls() {
		$this->start_controls_section(
			'section_shortcode',
			[
				'label' => __( 'Event Attendee Information', 'wp-event-manager-attendee-information' ),
			]
		);

		$args = array(
				'post_type'		=> 'event_listing',
				'post_status'	=> 'publish',
				'posts_per_page'=> -1,
		);

		$events = get_posts( $args );

		$options = [];
		if(!empty($events))
		{
			foreach ($events as $event) {
				$options[$event->ID] = $event->post_title;
			}
		}
		else
		{
			$options[] = __( 'Not Found Event', 'wp-event-manager-attendee-information' );
		}

		$this->add_control(
			'event_id',
			[
				'label'     => __( 'Select Event', 'wp-event-manager-attendee-information' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => '',
				'options'	=> $options
			]
		);

		$this->add_control(
			'posts_per_page',
			[
				'label'       => __( 'Post Per Page', 'wp-event-manager-attendee-information' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => '10',
			]
		);

		$this->end_controls_section();

	}

	/**
	 * Render the widget output on the frontend.
	 *
	 * Written in PHP and used to generate the final HTML.
	 *
	 * @access protected
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		if($settings['event_id']>0)
		    $event_id = 'event_id="'.$settings['event_id'].'"';
	    else
	        $event_id = '';
	        
        if($settings['posts_per_page']>0)
            $posts_per_page = 'posts_per_page="'.$settings['posts_per_page'].'"';
        else
            $posts_per_page = '';

        $args = apply_filters( 'event_manager_event_public_attendee_list_args', array(
			'post_type'           => 'event_registration',
			'post_status'         => array_diff( array_merge( array_keys( get_event_registration_statuses() ), array( 'publish' ) ), array( 'archived' ) ),
			'post_parent'         => $settings['event_id']
		) );
		
		$registrations = get_posts($args);

		if( !empty($registrations) )
		{
			echo do_shortcode('[event_attendee '.$event_id.' '.$posts_per_page.' ]');	
		}else{
			echo '<div class="wpem-alert wpem-alert-danger">'. __('No Attendee Found.', 'wp-event-manager-attendee-information') .'</div>';
		}
	}

	/**
	 * Render the widget output in the editor.
	 *
	 * Written as a Backbone JavaScript template and used to generate the live preview.
	 *
	 * @access protected
	 */
	protected function content_template() {}
}
