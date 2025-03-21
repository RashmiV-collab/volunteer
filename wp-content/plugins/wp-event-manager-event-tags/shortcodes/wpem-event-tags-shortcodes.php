<?php
if(!defined('ABSPATH')) {
	exit;
}

/**
 * WPEM_Event_Tags_Shortcodes
 */
class WPEM_Event_Tags_Shortcodes {
	/**
	 * Constructor
	 */
	public function __construct() {
		add_shortcode('events_by_tag', array($this, 'events_by_tag'));
		add_shortcode('event_tag_cloud', array($this, 'event_tag_cloud'));
		
		add_filter('wp_generate_tag_cloud_data', array($this, 'wpem_generate_tag_cloud_data'));

		// Change core output events shortcode
		add_filter('event_manager_output_events_defaults', array($this, 'output_events_defaults'));
		add_action('event_manager_event_filters_end', array($this, 'show_tag_filter'));
		add_action('set_object_terms', array($this, 'set_object_terms'));
		add_filter('event_manager_get_listings_result', array($this, 'event_manager_get_listings_result'));
		add_filter('event_manager_get_listings', array($this, 'apply_tag_filter'));
		add_filter('event_manager_get_listings_args', array($this, 'wpem_event_tags_get_listings_args'), 10, 2);
	}

	/**
	 * Change default args
	 * @since 1.4.5
	 */
	public function wpem_event_tags_get_listings_args($args, $request) {
		parse_str($request['form_data'], $form_data);

		// Use extract to create variables from the formValues array
		extract($form_data);

		if(isset($search_event_tags) && !empty($search_event_tags)){
			// Extract the specific variable search_event_tags from the extracted variables
			$extracted_search_event_tags = implode(', ', $search_event_tags);
			
			if(isset($extracted_search_event_tags) && !empty($extracted_search_event_tags)){
				$search_tags = is_array($extracted_search_event_tags) ?  array_filter(array_map('sanitize_text_field', array_map('stripslashes', $extracted_search_event_tags))) : array_filter(array(sanitize_text_field(stripslashes($extracted_search_event_tags))));
				$args['search_tags'] = $search_tags;
			}
		}
		return $args;
	}

	/**
	 * Change default args
	 */
	public function wpem_generate_tag_cloud_data($tags_data) {
		
		$new_tags_data = [];
		foreach ($tags_data as $tag_data){
			$tag_data['class'] = $tag_data['class'] . ' wpem-event-tag-text event-tag ' . $tag_data['slug'];
			$new_tags_data[] = $tag_data;
		}
		if(!empty($new_tags_data))
			$tags_data = $new_tags_data;

		return $tags_data;
	}

	/**
	 * Change default args
	 */
	public function output_events_defaults($atts) {
		$atts['show_tags'] = true;
		$atts['selected_tag'] = '';
		return $atts;
	}

	/**
	 * Show the tag cloud
	 */
	public function show_tag_filter($shortcode_atts) {
		if(wp_count_terms('event_listing_tag') == 0) 
			return;

		if(isset($shortcode_atts['show_tags']) && ($shortcode_atts['show_tags'] === true || (string) $shortcode_atts['show_tags'] == 'true')) {
			$any_class = (get_option('event_manager_tags_filter_type') == 'any' ? 'any_tag_chosen' : 'all_tag_chosen');

			wp_enqueue_script('wp-event-managerer-ajax-tag-filters', WPEM_EVENT_TAGS_PLUGIN_URL . '/assets/js/tag-filter.min.js', array('jquery'), '1.0', true);
			wp_localize_script('wp-event-managerer-ajax-tag-filters', 'wpem_filters_sub', 
				array(
					'any_class' => $any_class, 
				)
			);
			echo '<div id="class_chose" class="filter_wide filter_by_tag '.$any_class.'">' .  __('Filter by tag:', 'wp-event-manager-event-tags') . ' <span class="filter_by_tag_cloud"></span></div>';
		}
		if(isset($shortcode_atts['selected_tag']) && !empty($shortcode_atts['selected_tag'])) {
			$selected_event_tags = !empty($shortcode_atts['selected_tag']) ? explode(',', $shortcode_atts['selected_tag']) : '';
			foreach ($selected_event_tags as $selected_event_tag) : ?>
				<input type="hidden" name="search_event_tags[]" value="<?php echo sanitize_title($selected_event_tag); ?>" />
			<?php endforeach; 
		}
	}

	/**
	 * Clear transients
	 */
	public function set_object_terms() {
		delete_transient('event_tag_q');
	}

	/**
	 * When updating events via ajax, get tag cloud
	 * @param  array $results
	 * @return array
	 */
	public function event_manager_get_listings_result($results) {
		if(!empty($results) && isset($_POST['search_categories']) && !empty($_POST['search_categories'])){
			// Search within category
			$search_categories = isset($_POST['search_categories']) ? $_POST['search_categories'] : '';
			if(!empty($search_categories)){
				if(is_array($search_categories)) {
					$search_categories = array_filter(array_map('sanitize_text_field', array_map('stripslashes', $search_categories)));
				} else {
					$search_categories = array_filter(array(sanitize_text_field(stripslashes($search_categories))));
				}

				if($search_categories) {
					$transient_key = md5(implode(', ', $search_categories));
					$transient     = array_filter((array) get_transient('event_tag_q'));

					if(empty($transient[ $transient_key ])) {
						$events_in_category = get_objects_in_term($search_categories, 'event_listing_category');
						$include_tags     = array(0);

						foreach ($events_in_category as $event_id) {
							$terms = wp_get_post_terms($event_id, 'event_listing_tag', array('fields' => 'ids'));

							if(is_array($terms)) 
								$include_tags = array_merge($include_tags, $terms);
							$include_tags = array_unique($include_tags);
						}

						$transient[ $transient_key ] = $include_tags;
						set_transient('event_tag_q', $transient, YEAR_IN_SECONDS);
					} else {
						$include_tags = $transient[ $transient_key ];
					}
				} else {
					$include_tags = true;
				}
				if(!empty($include_tags)) {
					$atts = array(
						'smallest'                  => 1, 
						'largest'                   => 2, 
						'unit'                      => 'em', 
						'number'                    => 25, 
						'format'                    => 'flat', 
						'separator'                 => "\n", 
						'orderby'                   => 'count', 
						'order'                     => 'DESC', 
						'exclude'                   => null, 
						'link'                      => 'view', 
						'taxonomy'                  => 'event_listing_tag', 
						'echo'                      => false, 
						'topic_count_text_callback' => array($this, 'tag_cloud_text_callback'), 
						'include'                   => is_array($include_tags) ? implode(', ', $include_tags) : null
					);
					$html = wp_tag_cloud(apply_filters('event_filter_tag_cloud', $atts));
					$html = preg_replace("/<a(.*)href='([^'']*)'(.*)>/", '<a href="#"$1$3>', $html);
				} else {
					$html = '';
				}
				$results['tag_filter'] = $html;
			}
		}
		return $results;
	}

	/**
	 * Filter by tag
	 */
	public function apply_tag_filter($args) {
		global $wp_version;

		$params = array();
		if(isset($_POST['form_data'])) {

			parse_str($_POST['form_data'], $params);

			if(isset($params['event_tag'])) {
				$tags      = array_filter($params['event_tag']);
				$tag_array = array();
				foreach ($tags as $tag) {
					$tag = get_term_by('name', $tag, 'event_listing_tag');
					$tag_array[] = $tag->slug;
				}

				$args['tax_query'][] = array(
					'taxonomy' => 'event_listing_tag', 
					'field'    => 'slug', 
					'terms'    => $tag_array, 
					'operator' => get_option('event_manager_tags_filter_type', 'any') ? "IN" : "AND"
				);
		
				add_filter('event_manager_get_listings_custom_filter', '__return_true');
				add_filter('event_manager_get_listings_custom_filter_text', array($this, 'apply_tag_filter_text'));
				add_filter('event_manager_get_listings_custom_filter_rss_args', array($this, 'apply_tag_filter_rss'));
			}
		}
		return $args;
	}

	/**
	 * Append 'showing' text
	 * @return string
	 */
	public function apply_tag_filter_text($text) {
		$params = array();
		parse_str($_POST['form_data'], $params);
		$text .= ' ' . __('tagged', 'wp-event-manager-event-tags') . ' &quot;' . implode('&quot;, &quot;', array_filter($params['event_tag'])) . '&quot;';
		return $text;
	}

	/**
	 * apply_tag_filter_rss
	 * @return array
	 */
	public function apply_tag_filter_rss($args) {
		$params = array();
		parse_str($_POST['form_data'], $params);

		$args['event_tags'] = implode(', ', array_filter($params['event_tag']));

		return $args;
	}

	/**
	 * Events by tag shortcode
	 *
	 * @return string
	 */
	public function events_by_tag($atts) {
		global $event_manager;

		ob_start();

		extract(shortcode_atts(array(
			'per_page'        => '-1', 
			'orderby'         => 'date', 
			'order'           => 'desc', 
			'tag'             => '', 
			'tags'            => ''
		), $atts));

		$tags   = array_filter(array_map('sanitize_title', explode(', ', $tags)));
		
		if($tag)
			$tags[] = sanitize_title($tag);

		if(!$tags)
			return;

		$args = array(
			'post_type'           => 'event_listing', 
			'post_status'         => 'publish', 
			'ignore_sticky_posts' => 1, 
			'posts_per_page'      => $per_page, 
			'orderby'             => $orderby, 
			'order'               => $order, 
		);
		$args['tax_query'] = array(
			array(
				'taxonomy' => 'event_listing_tag', 
				'field'    => 'slug', 
				'terms'    => $tags
			)
		);
		if(get_option('event_manager_hide_filled_positions') == 1)
			$args['meta_query'] = array(
				array(
					'key'     => '_filled', 
					'value'   => '1', 
					'compare' => '!='
				)
			);
		$events = new WP_Query(apply_filters('event_manager_output_events_args', $args));

		if($events->have_posts()) : ?>
			<div id="event-listing-view" class="wpem-main wpem-event-listings event_listings wpem-event-listing-list-view">	
				<?php while ($events->have_posts()) : $events->the_post(); 
					get_event_manager_template_part('content', 'event_listing'); 
				endwhile; ?>
			</div>
		<?php else :
			echo '<div class="wpem-alert wpem-alert-info">' . sprintf(__('No events found tagged with %s.', 'wp-event-manager-event-tags'), implode(', ', $tags)) . '</div>';
		endif;
		wp_reset_postdata();
		return ob_get_clean();
	}

	/**
	 * Event Tag cloud shortcode
	 */
	public function event_tag_cloud($atts) {
		ob_start();

		$atts = shortcode_atts(array(
			'smallest'                  => 8, 
		    'largest'                   => 22, 
		    'unit'                      => 'pt', 
		    'number'                    => 45, 
		    'format'                    => 'flat', 
		    'separator'                 => "\n", 
		    'orderby'                   => 'count', 
		    'order'                     => 'DESC', 
		    'exclude'                   => null, 
		    'include'                   => null, 
		    'link'                      => 'view', 
		    'taxonomy'                  => 'event_listing_tag', 
		    'echo'                      => false, 
		    'topic_count_text_callback' => array($this, 'tag_cloud_text_callback')
		), $atts);

		$html = wp_tag_cloud(apply_filters('event_tag_cloud', $atts));

		if(!apply_filters('enable_event_tag_archives', get_option('event_manager_enable_tag_archive')))
			$html = str_replace('</a>', '</span>', preg_replace("/<a(.*)href='([^'']*)'(.*)>/", '<span$1$3>', $html));
		return $html;
	}

	/**
	 * tag_cloud_text_callback
	 */
	public function tag_cloud_text_callback($count) {
		return sprintf(_n('%s event', '%s events', $count, 'wp-event-manager-event-tags'), number_format_i18n($count));
	}
}
new WPEM_Event_Tags_Shortcodes();