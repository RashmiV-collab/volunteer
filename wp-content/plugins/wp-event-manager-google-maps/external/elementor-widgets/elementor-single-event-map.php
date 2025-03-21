<?php
namespace WPEventManagerGoogleMap\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Elementor Single Event Map
 *
 * Elementor widget for single event map.
 *
 */
class Elementor_Single_Event_Map extends Widget_Base {

	/**
	 * Retrieve the widget name.
	 *
	 * @access public
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'single-event-map';
	}

	/**
	 * Retrieve the widget title.
	 *
	 * @access public
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return __( 'Single Event Map', 'wp-event-manager-google-maps' );
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
		return 'eicon-image-hotspot';
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
		return [ 'single-event-map', 'code' ];
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
	protected function register_controls() {
		$this->start_controls_section(
			'section_shortcode',
			[
				'label' => __( 'Single Event Map', 'wp-event-manager-google-maps' ),
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
			$options[] = __( 'Not Found Event', 'wp-event-manager-google-maps' );
		}

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
			$options[] = __( 'Not Found Event', 'wp-event-manager-google-maps' );
		}

		$this->add_control(
			'event_id',
			[
				'label'     => __( 'Select Event', 'wp-event-manager-google-maps' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => '',
				'options'	=> $options
			]
		);
		
		$this->add_control(
			'maps_type',
			[
				'label' => __( 'Map Type', 'wp-event-manager-google-maps' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'SATELLITE',
				'options' => [
					'ROADMAP' => __( 'Roadmap', 'wp-event-manager-google-maps' ),
					'SATELLITE' => __( 'Satellite', 'wp-event-manager-google-maps' ),
					'HYBRID ' => __( 'Hybrid ', 'wp-event-manager-google-maps' ),
					'TERRAIN' => __( 'Terrain', 'wp-event-manager-google-maps' ),
				],
			]
		);		
		$this->add_control(
			'height',
			[
				'label'       => __( 'Map Height', 'wp-event-manager-google-maps' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '400px',
			]
		);
		$this->add_control(
			'width',
			[
				'label'       => __( 'Map Width', 'wp-event-manager-google-maps' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '400px',
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
	        $event_id = 'event_id='.(int)$settings['event_id'];
        else
            $event_id = '';
            
        if($settings['height']>0)
            $height='height='.$settings['height'];
        else
            $height='';
            
        if($settings['width']>0)
            $width='width='.$settings['width'];
        else
            $width='';
        
		$single_event =  do_shortcode('[single_event_location_map '.$event_id.' '.$width.' '.$height.' maps_type='.$settings['maps_type'].']');

		echo $single_event;
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
