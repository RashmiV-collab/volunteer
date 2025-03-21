<?php

namespace WPEventManagerBookmarks\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

/**
 * Elementor Single Event Bookmark
 *
 * Elementor widget for single event bookmark field.
 *
 */
class Elementor_Single_Event_Bookmark extends Widget_Base {

    /**
     * Retrieve the widget name.
     *
     * @access public
     *
     * @return string Widget name.
     */
    public function get_name() {
        return 'single-event-bookmark';
    }

    /**
     * Retrieve the widget title.
     *
     * @access public
     *
     * @return string Widget title.
     */
    public function get_title() {
        return __('Single Event Bookmark', 'wp-event-manager-bookmarks');
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
        return 'eicon-star-o';
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
        return ['single-event-bookmark', 'code'];
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
        return ['wp-event-manager-categories'];
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
				'label' => __( 'Single Event Bookmark', 'wp-event-manager-bookmarks' ),
			]
		);

		$args = [
            'post_type'   => 'event_listing',
			'post_status' => 'publish',
			'posts_per_page'	=> -1
        ];

        $events = get_posts($args);

        $options = [];
        $options[''] =  __( 'Select event', 'wp-event-manager-bookmarks' );
        if(!empty( $events))
        {
            foreach ($events as $event) {
                $options[$event->ID] = $event->post_title;
            }
        }

        $this->add_control(
			'event_id',
			[
				'label'     => __( 'Select event', 'wp-event-manager-bookmarks' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => '',
				'options'	=> $options
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
        global $wpdb;

        $settings = $this->get_settings_for_display();
		if($settings['event_id']>0){
			$event_id 	= $settings['event_id'];
        }else{
            $event_id = get_the_ID();
        }
		$event = get_post($event_id);

        ob_start();

		$event_type = get_post_type_object( $event->post_type );

		if ( ! is_user_logged_in() ) {
			get_event_manager_template( 'login-to-bookmark-form.php', array(
				'post_type'     => $event_type,
				'post'          => $event
			), 'wp-event-manager-bookmarks', WPEM_BOOKMARKS_PLUGIN_DIR . '/templates/' );
		} else {
			$is_bookmarked = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}event_manager_bookmarks WHERE post_id = %d AND user_id = %d;", $event_id, get_current_user_id() ) ) ? true : false;;

			if ( $is_bookmarked ) {
				$note = $wpdb->get_var( $wpdb->prepare( "SELECT bookmark_note FROM {$wpdb->prefix}event_manager_bookmarks WHERE post_id = %d AND user_id = %d;", $event_id, get_current_user_id() ) );;
			} else {
				$note = '';
			}

			wp_enqueue_script( 'wp-event-manager-bookmarks-bookmark' );

			get_event_manager_template( 'bookmark-form.php', array(
				'post_type'     => $event_type,
				'post'          => $event,
				'is_bookmarked' => $is_bookmarked ,
				'note'          => $note
			), 'wp-event-manager-bookmarks', WPEM_BOOKMARKS_PLUGIN_DIR . '/templates/' );
		}

		echo ob_get_clean();
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
