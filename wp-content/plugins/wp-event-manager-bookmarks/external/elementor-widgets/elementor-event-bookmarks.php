<?php

namespace WPEventManagerBookmarks\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

/**
 * Elementor Event Listing
 *
 * Elementor widget for event lising.
 *
 */
class Elementor_Event_Bookmarks extends Widget_Base {

    /**
     * Retrieve the widget name.
     *
     * @access public
     *
     * @return string Widget name.
     */
    public function get_name() {
        return 'bookmarks';
    }

    /**
     * Retrieve the widget title.
     *
     * @access public
     *
     * @return string Widget title.
     */
    public function get_title() {
        return __('Event Bookmarks', 'wp-event-manager-bookmarks');
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
        return 'eicon-archive-posts';
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
        return ['bookmarks', 'code'];
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
    protected function _register_controls() {
        $this->start_controls_section(
                'section_shortcode',
                [
                    'label' => __('Event Bookmarks', 'wp-event-manager-calendar'),
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
       
        echo do_shortcode('[event_manager_my_bookmarks]');
    }

}
