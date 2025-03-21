<?php
/**
 * Create csv file and put data in to the csv file.
 */
function csv_generate($file_name='events', $row = [], $events = [], $custom_fields = []) {
	@set_time_limit(0);
	if (function_exists('apache_setenv')) {
		@apache_setenv('no-gzip', 1);
	}
	@ini_set('zlib.output_compression', 0);
	//@ob_clean();

	header('Content-Type: text/csv; charset=UTF-8');
	header('Content-Disposition: attachment; filename=' . $file_name . '.csv');
	header('Pragma: no-cache');
	header('Expires: 0');

	$fp  = fopen('php://output', 'w');
	
	fwrite($fp, implode(',', $row) . "\n");

	foreach ($events as $event) {
		$row   = array();
		foreach ($custom_fields as $custom_field) {
			if($custom_field == '_event_title'){
				$row[] = $event->post_title;
			} elseif($custom_field == '_post_id'){
				$row[] = $event->ID;
			} elseif($custom_field == '_event_description'){
				$row[] = $event->post_content;
			} elseif($custom_field =='_event_category' || $custom_field =='event_category'){
				$categories = get_the_terms($event->ID, 'event_listing_category');
				$cat = [];
				if(isset($categories) && !empty($categories))
					foreach ($categories as $term) {
							$cat[] = $term->name;
					}
				$row[] = $cat;
			} elseif($custom_field =='_event_type' || $custom_field =='event_type'){
				$categories = get_the_terms($event->ID, 'event_listing_type');
				$cat = [];
				if(isset($categories) && !empty($categories))
					foreach ($categories as $term) {
							$cat[] = $term->name;
					}
				$row[] = $cat;
			} elseif($custom_field =='_organizer_logo' || $custom_field =='_venue_logo' || $custom_field =='_event_banner'){
				$logo = get_post_meta($event->ID, $custom_field, true);
				if(!isset($logo) || empty($logo)){
					$thumbnail_id = get_post_meta($event->ID, '_thumbnail_id', true);
					if(isset($thumbnail_id) && !empty($thumbnail_id))
						$row[] = get_the_post_thumbnail_url($event->ID,'full');
					else
						$row[] = '';
				} else {
					$row[] = $logo;
				}
			} elseif($custom_field == '_thumbnail_id'){
				$thumbnail_id = get_the_post_thumbnail_url($event->ID,'full');
				if(empty($thumbnail_id)){
					if(get_post_type($event->ID) == 'event_organizer'){
						$row[] = get_post_meta($event->ID, '_organizer_logo', true);
					} elseif(get_post_type($event->ID) == 'event_venue'){
						$row[] = get_post_meta($event->ID, '_venue_logo', true);
					} elseif(get_post_type($event->ID) == 'event_listing'){
						$row[] = get_post_meta($event->ID, '_event_banner', true);
					}
				} else {
					$row[] = $thumbnail_id;
				}
			} else {
				if(is_array(get_post_meta($event->ID, $custom_field, true)))
					$row[] = json_encode(get_post_meta($event->ID, $custom_field, true));
				else
    				$row[] = get_post_meta($event->ID, $custom_field, true);
			}
		}				
		$row   = array_map('wrap_column', $row);
		fwrite($fp, implode(',', $row) . "\n");
	}
	fclose($fp);
	exit;
}

/**
 * Create xml file and put data in to the xml file.
 * @since 1.3.4
 */
function xml_generate($file_name='events', $row = [], $events = [], $custom_fields = []) {
	
	// File Name & Content Header For Download
	header('Content-Type: text/xml');
	header('Content-Disposition: attachment; filename=' . $file_name . '.xml');
	$xml_document = new DOMDocument('1.0','utf-8');
	$xml_document->formatOutput = true;
	/**
	 * Create Root Element
	*/
	$root = $xml_document->createElement("source");
	$xml_document->appendChild($root); //append root element to document

	foreach ($events as $event) {
		$event_element = $xml_document->createElement($file_name);
		$row   = array();
		
		foreach ($custom_fields as $custom_field) {

			if($custom_field == '_event_title'){
				$row[$custom_field] = $event->post_title;
			} elseif($custom_field == '_post_id'){
				$row[$custom_field] = $event->ID;
			} elseif($custom_field == '_event_description'){
				$row[$custom_field] = strip_tags(htmlspecialchars_decode($event->post_content));
			} elseif($custom_field == '_organizer_description'){
				$row[$custom_field] = strip_tags(htmlspecialchars_decode($event->post_content));
			} elseif($custom_field == '_venue_description'){
				$row[$custom_field] = strip_tags(htmlspecialchars_decode($event->post_content));
			} elseif($custom_field =='_event_category' || $custom_field =='event_category'){
				$categories = get_the_terms($event->ID, 'event_listing_category');
				$cat = [];
				if(isset($categories) && !empty($categories))
					foreach ($categories as $term) {
							$cat[] = $term->name;
					}
				$row[$custom_field] = $cat;
			} elseif($custom_field =='_event_type' || $custom_field =='event_type'){
				$categories = get_the_terms($event->ID, 'event_listing_type');
				$cat = [];
				if(isset($categories) && !empty($categories))
					foreach ($categories as $term) {
							$cat[] = $term->name;
					}
				$row[$custom_field] = $cat;
			} elseif($custom_field =='_organizer_logo' || $custom_field =='_venue_logo' || $custom_field =='_event_banner'){
				$logo = get_post_meta($event->ID, $custom_field, true);
				if(!isset($logo) || empty($logo)){
					$thumbnail_id = get_post_meta($event->ID, '_thumbnail_id', true);
					if(isset($thumbnail_id) && !empty($thumbnail_id))
						$row[$custom_field] = get_the_post_thumbnail_url($event->ID,'full');
					else
						$row[$custom_field] = '';
				} else {
					$row[$custom_field] = $logo;
				}
			} elseif($custom_field == '_thumbnail_id'){
				$thumbnail_id = get_the_post_thumbnail_url($event->ID,'full');
				if(empty($thumbnail_id)){
					if(get_post_type($event->ID) == 'event_organizer'){
						$row[$custom_field] = get_post_meta($event->ID, '_organizer_logo', true);
					} elseif(get_post_type($event->ID) == 'event_venue'){
						$row[$custom_field] = get_post_meta($event->ID, '_venue_logo', true);
					} elseif(get_post_type($event->ID) == 'event_listing'){
						$row[$custom_field] = get_post_meta($event->ID, '_event_banner', true);
					}
				} else {
					$row[$custom_field] = $thumbnail_id;
				}
			} elseif($custom_field == '_event_organizer_ids') {
				$row['_event_organizer_ids']   =  json_encode((get_post_meta($event->ID, $custom_field, true)));
			} elseif($custom_field == '_event_venue_ids') {
				$row['_event_venue_ids']   =  json_encode((get_post_meta($event->ID, $custom_field, true)));
			} else {
				if(is_array(get_post_meta($event->ID, $custom_field, true)))
					$row[$custom_field] = json_encode(get_post_meta($event->ID, $custom_field, true));
				else
    				$row[$custom_field] = get_post_meta($event->ID, $custom_field, true);
			}
			if(is_array($row[$custom_field]))
				$row[$custom_field] = implode(",", $row[$custom_field]);
			$title = $xml_document->createElement($custom_field);
			$title->appendChild($xml_document->createCDATASection($row[$custom_field]));
			$event_element->appendChild($title);
			$root->appendChild($event_element);
		}
	}
	echo $xml_document->saveXML();
	exit;
}
/**
 * Create xls file and put data in to the xls file.
 * @since 1.3.4
 */
function xls_generate($file_name='events', $row = [], $events = [], $custom_fields = []) {
	@set_time_limit(0);
	if (function_exists('apache_setenv')) {
		@apache_setenv('no-gzip', 1);
	}

	header("Content-Type: application/xls");    
	header("Content-Disposition: attachment; filename=" . $file_name . ".xls");  
	header("Pragma: no-cache"); 
	header("Expires: 0");

	$i=0;
	foreach ($events as $event) {
		$row   = array();
		foreach ($custom_fields as $custom_field) {
			if($custom_field == '_event_title'){
				$row[$custom_field] = $event->post_title;
			} elseif($custom_field == '_post_id'){
				$row[] = $event->ID;
			} elseif($custom_field == '_event_description'){
				$row[$custom_field] = strip_tags(htmlspecialchars_decode($event->post_content));
			} elseif($custom_field == '_organizer_description'){
				$row[$custom_field] = strip_tags(htmlspecialchars_decode($event->post_content));
			} elseif($custom_field == '_venue_description'){
				$row[$custom_field] = strip_tags(htmlspecialchars_decode($event->post_content));
			} elseif($custom_field =='_event_category'){
				$categories = get_the_terms($event->ID, 'event_listing_category');
				
				$cat = '';
				if(isset($categories) && !empty($categories))
					foreach ($categories as $term) {
							$cat = str_replace("&amp;", "or",$term->name);
					}
				$row[$custom_field] = $cat;
			} elseif($custom_field =='_event_type'){
				$categories = get_the_terms($event->ID, 'event_listing_type');
				$cat = '';
				if(isset($categories) && !empty($categories))
					foreach ($categories as $term) {
							$cat = $term->name;
					}
				$row[$custom_field] = $cat;

			} elseif($custom_field == '_thumbnail_id'){
				$thumbnail_id = get_the_post_thumbnail_url($event->ID,'full');
				if(empty($thumbnail_id)){
					if(get_post_type($event->ID) == 'event_organizer'){
						$row[] = get_post_meta($event->ID, '_organizer_logo', true);
					} elseif(get_post_type($event->ID) == 'event_venue'){
						$row[] = get_post_meta($event->ID, '_venue_logo', true);
					} elseif(get_post_type($event->ID) == 'event_listing'){
						$row[] = get_post_meta($event->ID, '_event_banner', true);
					}
				} else {
					$row[] = $thumbnail_id;
				}
			} elseif($custom_field == '_event_organizer_ids') {
				$row['_event_organizer_ids']   =  json_encode((get_post_meta($event->ID, $custom_field, true)));
			} elseif($custom_field == '_event_venue_ids') {
				$row['_event_venue_ids']   =  json_encode((get_post_meta($event->ID, $custom_field, true)));
			} else {
				if(is_array(get_post_meta($event->ID, $custom_field, true)))
					$row[$custom_field] = json_encode(get_post_meta($event->ID, $custom_field, true));
				else
    				$row[$custom_field] = get_post_meta($event->ID, $custom_field, true);
			}
		}
		if($i==0)
			echo implode("\t", array_keys($row)) . "\n";
		echo implode("\t", array_values($row)) . "\n";
		$i++;
		
	}
	exit;
}

/**
 * Create xls file and put data in to the xls file.
 * @since 1.3.4
 */
function xlsx_generate($file_name='events', $row = [], $events = [], $custom_fields = []) {

	@set_time_limit(0);
	if (function_exists('apache_setenv')) {
		@apache_setenv('no-gzip', 1);
	}
	
	// File Name & Content Header For Download
	header("Content-Disposition: attachment; filename=" . $file_name . ".xlsx");
	header("Content-Type: application/vnd.ms-excel");

	$i=0;
	foreach ($events as $event) {
		$row   = array();
		foreach ($custom_fields as $custom_field) {
			if($custom_field == '_event_title'){
				$row[$custom_field] = $event->post_title;
			} elseif($custom_field == '_post_id'){
				$row[] = $event->ID;
			} elseif($custom_field == '_event_description'){
				$row[$custom_field] = strip_tags(htmlspecialchars_decode($event->post_content));
			} elseif($custom_field == '_organizer_description'){
				$row[$custom_field] = strip_tags(htmlspecialchars_decode($event->post_content));
			} elseif($custom_field == '_venue_description'){
				$row[$custom_field] = strip_tags(htmlspecialchars_decode($event->post_content));
			} elseif($custom_field =='_event_category'){
				$categories = get_the_terms($event->ID, 'event_listing_category');
				
				$cat = '';
				if(isset($categories) && !empty($categories))
					foreach ($categories as $term) {
							$cat = str_replace("&amp;", "or",$term->name);
					}
				$row[$custom_field] = $cat;
			} elseif($custom_field =='_event_type'){
				$categories = get_the_terms($event->ID, 'event_listing_type');
				$cat = '';
				if(isset($categories) && !empty($categories))
					foreach ($categories as $term) {
							$cat = $term->name;
					}
				$row[$custom_field] = $cat;

			} elseif($custom_field == '_thumbnail_id'){
				$thumbnail_id = get_the_post_thumbnail_url($event->ID,'full');
				if(empty($thumbnail_id)){
					if(get_post_type($event->ID) == 'event_organizer'){
						$row[] = get_post_meta($event->ID, '_organizer_logo', true);
					} elseif(get_post_type($event->ID) == 'event_venue'){
						$row[] = get_post_meta($event->ID, '_venue_logo', true);
					} elseif(get_post_type($event->ID) == 'event_listing'){
						$row[] = get_post_meta($event->ID, '_event_banner', true);
					}
				} else {
					$row[] = $thumbnail_id;
				}
			} elseif($custom_field == '_event_organizer_ids') {
				$row['_event_organizer_ids']   =  json_encode((get_post_meta($event->ID, $custom_field, true)));
			} elseif($custom_field == '_event_venue_ids') {
				$row['_event_venue_ids']   =  json_encode((get_post_meta($event->ID, $custom_field, true)));
			} else {
				if(is_array(get_post_meta($event->ID, $custom_field, true)))
					$row[$custom_field] = json_encode(get_post_meta($event->ID, $custom_field, true));
				else
    				$row[$custom_field] = get_post_meta($event->ID, $custom_field, true);
			}
		}
		if($i==0)
			echo implode("\t", array_keys($row)) . "\n";
		echo implode("\t", array_values($row)) . "\n";
		$i++;
	}
	exit;
}

/**
 * Wrap a column in quotes for the CSV
 * @param  string data to wrap
 * @return string wrapped data
 */
function wrap_column($data) {
	$data = is_array($data) ? json_encode($data) : $data;
	return '"' . str_replace('"', '""', $data) . '"';
}
	
/* Filter File Data
* @since 1.3.4
*/
function filter_column_data(&$str) {
	$str = preg_replace("/\t/", "\\t", $str);
	$str = preg_replace("/\r?\n/", "\\n", $str);
	if(strstr($str, '"')) $str = '"' . str_replace('"', '""', $str) . '"';
}

/**
 * Get all events by user id.
 * @return $events
 */
function get_event_posts($post_type = 'event_listing', $auther_id = '', $events = array()) {
	if($post_type == 'event_listing' && !empty($events)){
		$args = array(
			'post_type' => $post_type,
			'post__in'  => $events
		);
	} else {
		$args = array(
					'post_type'      => $post_type,
					'post_status'    => array('publish', 'draft', 'expired') ,
					'posts_per_page' => -1,
				) ;

		if(!empty($auther_id)){
			$args['author__in'] = array($auther_id);
		}
	}
	$args = apply_filters('wpem_export_event_listing_args', $args, $post_type);
	$events = get_posts($args);
	return $events;
}