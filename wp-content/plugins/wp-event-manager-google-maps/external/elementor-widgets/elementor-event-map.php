<?php
namespace WPEventManagerGoogleMap\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Elementor Event Listing
 *
 * Elementor widget for event lising.
 *
 */
class Elementor_Event_Map extends Widget_Base {

	/**
	 * Retrieve the widget name.
	 *
	 * @access public
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'event-map';
	}

	/**
	 * Retrieve the widget title.
	 *
	 * @access public
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return __( 'Event Map', 'wp-event-manager-google-maps' );
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
		return 'eicon-image-box';
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
		return [ 'event-map', 'code' ];
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
				'label' => __( 'Event Map', 'wp-event-manager-google-maps' ),
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
		            'HYBRIDE ' => __( 'Hybrid ', 'wp-event-manager-google-maps' ),
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
		$this->add_control(
			'featured',
			[
				'label' => __( 'Show Featured', 'wp-event-manager-google-maps' ),
				'type' => Controls_Manager::SELECT,
				'default' => '',
				'options' => [
					'' => __( 'All Events', 'wp-event-manager-google-maps' ),
					'false' => __( 'False', 'wp-event-manager-google-maps' ),
					'true' => __( 'True', 'wp-event-manager-google-maps' ),
				],
			]
		);		

		$this->add_control(
			'cancelled',
			[
				'label' => __( 'Show Cancelled', 'wp-event-manager-google-maps' ),
				'type' => Controls_Manager::SELECT,
				'default' => '',
				'options' => [
					'' => __( 'All Events', 'wp-event-manager-google-maps' ),
					'false' => __( 'False', 'wp-event-manager-google-maps' ),
					'true' => __( 'True', 'wp-event-manager-google-maps' ),
				],
			]
		);

		$this->add_control(
			'location',
			[
				'label'       => __( 'Location', 'wp-event-manager-google-maps' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => __( 'Enter Location', 'wp-event-manager-google-maps' ),
				'default'     => '',
			]
		);

		$this->add_control(
			'keywords',
			[
				'label'       => __( 'Keywords ', 'wp-event-manager-google-maps' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => __( 'Enter Keywords ', 'wp-event-manager-google-maps' ),
				'default'     => '',
			]
		);
		
		$this->add_control(
			'categories',
			[
				'label'       => __( 'Categories ', 'wp-event-manager-google-maps' ),
				'type'        => Controls_Manager::TEXTAREA,
				'placeholder' => __( 'Enter Categories by comma separate', 'wp-event-manager-google-maps' ),
				'default'     => '',
			]
		);

		$this->add_control(
			'event_types',
			[
				'label'       => __( 'Event Types ', 'wp-event-manager-google-maps' ),
				'type'        => Controls_Manager::TEXTAREA,
				'placeholder' => __( 'Enter Event Types by comma separate', 'wp-event-manager-google-maps' ),
				'default'     => '',
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
	    
	    if(strlen($settings['height'])>0)
	        $height='height='.$settings['height'];
        else
            $height='height=400px';
            
        if(strlen($settings['width'])>0)
            $width='width='.$settings['width'];
        else
            $width='width=400px';
            
        if(strlen($settings['location'])>0)
            $location = 'location='.$settings['location'];
        else
            $location = '';
            
        if(strlen($settings['keywords'])>0)
            $keywords = 'keywords='.$settings['keywords'];
        else
            $keywords = '';
            
        if(strlen($settings['categories'])>0)
            $categories = 'categories='.$settings['categories'];
        else
            $categories = '';
            
        if(strlen($settings['event_types'])>0)
            $event_types = 'event_types='.$settings['event_types'];
        else
            $event_types = '';
            
        if(strlen($settings['featured'])>0)
            $featured = 'featured='.$settings['featured'];
        else
            $featured = '';
            
        if(strlen($settings['cancelled'])>0)
            $cancelled = 'cancelled='.$settings['cancelled'];
        else
            $cancelled = '';
            
        $shortcode= do_shortcode('[events_map '.$height.' '.$width.' maps_type='.$settings["maps_type"].' '.$featured.' '.$cancelled.' '.$location.' '.$keywords.' '.$categories.' '.$event_types.' ]');
        
        echo $shortcode;
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
