<?php
namespace WPEventManagerEventsByTags\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Elementor Events by tags
 *
 * Elementor widget for events listing by tags.
 *
 */
class Elementor_Events_By_Tag extends Widget_Base {

	/**
	 * Retrieve the widget name.
	 *
	 * @access public
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'event-by-tags';
	}

	/**
	 * Retrieve the widget title.
	 *
	 * @access public
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return __( 'Event By Tags', 'wp-event-manager-event-tags' );
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
		return 'eicon-tags';
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
		return [ 'event-by-tags', 'code' ];
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
				'label' => __( 'Event By Tags', 'wp-event-manager-event-tags' ),
			]
		);

		$this->add_control(
			'order',
			[
				'label' => __( 'Show Pagination', 'wp-event-manager-event-tags' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'DESC',
				'options' => [
					'ASC' => __( 'Ascending (ASC)', 'wp-event-manager-event-tags' ),
					'DESC' => __( 'Descending  (DESC)', 'wp-event-manager-event-tags' ),
				],
			]
		);

		$this->add_control(
			'orderby',
			[
				'label' => __( 'Show Pagination', 'wp-event-manager-event-tags' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'title',
				'options' => [
					'title' => __( 'Title', 'wp-event-manager-event-tags' ),
					'ID' => __( 'ID', 'wp-event-manager-event-tags' ),
					'name' => __( 'Name', 'wp-event-manager-event-tags' ),
					'parent' => __( 'Parent', 'wp-event-manager-event-tags' ),
					'rand' => __( 'Rand', 'wp-event-manager-event-tags' ),
				],
			]
		);

		$this->add_control(
			'tags',
			[
				'label'       => __( 'Tags ', 'wp-event-manager-event-tags' ),
				'type'        => Controls_Manager::TEXTAREA,
				'placeholder' => __( 'Enter Tags by comma separate', 'wp-event-manager-event-tags' ),
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
		
		echo do_shortcode('[events_by_tag order='.$settings['order'].' orderby='.$settings['orderby'].' tags='.$settings['tags'].']');
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
