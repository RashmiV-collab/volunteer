<?php
namespace WPEventManagerAlert\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Elementor Event Alert Dashboart
 *
 * Elementor widget for event alert dashboard.
 *
 */
class Elementor_Event_Alert_Dashboard extends Widget_Base {

	/**
	 * Retrieve the widget name.
	 *
	 * @access public
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'alert-dashboard';
	}

	/**
	 * Retrieve the widget title.
	 *
	 * @access public
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return __( 'Event Alert Dashboard', 'wp-event-manager-alerts' );
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
		return 'eicon-posts-ticker';
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
		return [ 'alert-dashboard', 'code' ];
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
				'label' => __( 'Event Alert Dashboard', 'wp-event-manager-alerts' ),
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
		// if($settings['posts_per_page']>0){
		// 	$settings['posts_per_page']='posts_per_page='.(int)$settings['posts_per_page'];
		// }
		// else{
		// 	$settings['posts_per_page']='';
		// }
		$shortcode = '[event_alerts]';
		echo $shortcode;
	}

/*	public function render_plain_content() {
		// In plain mode, render without shortcode
		$settings = $this->get_settings_for_display();
		// if($settings['posts_per_page']>0){
		// 	$posts_per_page = 'posts_per_page='.(int)$settings['posts_per_page'];
		// }
		// else{
		// 	$posts_per_page = '';
		// }
		// $shortcode = '[event_alerts '.$posts_per_page.' ]';
		$shortcode = '[event_alerts]';
		echo $shortcode;
	}*/

	/**
	 * Render the widget output in the editor.
	 *
	 * Written as a Backbone JavaScript template and used to generate the live preview.
	 *
	 * @access protected
	 */
	protected function content_template() {

		// $shortcode = do_shortcode('[event_alerts {{{settings.posts_per_page}}} ]');
		$shortcode = do_shortcode('[event_alerts]');
		?>
		<div class="elementor-shortcode"><?php echo $shortcode; ?></div>
		<?php
	}
}
