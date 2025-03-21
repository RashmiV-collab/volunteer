<?php
global $post;

do_action('before_calendar');
$color_setting = get_option('event_manager_calendar_background_color');
?>
<div id="calendar-container" class="wpem-main">
    <div class="wpem-calendar-view-container">

        <div id="calendar-filters-container" class="calendar-filters-container">   
            

            <div id="calendar-loader-container"></div>

            <div class="calendar-filters-form-wrapper wpem-form-wrapper">
                <form id="calendar-filters-form" class="calendar-filters-form">
                    <div class="wpem-d-flex wpem-align-items-center">
                        <h2 class="calendar-title wpem-heading-text wpem-mb-0"><?php echo empty($message) ? esc_html(get_month_name_from_month_number($selected_month) . ' ' . $selected_year) : $message; ?></h2>
                        <div class="wpem-calendar-filter-right">
                            <div class="wpem-calendar-month-filter">
                                <div class="wpem-form-group wpem-mb-0">
                                    <select id="calendar_month">
                                        <?php
                                        for ($month = 1; $month <= 12; $month++) {
                                            echo '<option value="' . absint($month) . '" ' . selected($month, $selected_month, false) . '>' . esc_attr(get_month_name_from_month_number($month)) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="wpem-calendar-year-filter">
                                <div class="wpem-form-group wpem-mb-0">
                                    <select id="calendar_year">
                                        <?php
                                        $start_year = date('Y') - 3;
                                        $end_year = $start_year + 10;
                                        $years = range($start_year, $end_year, 1);
                                        foreach ($years as $year) {
                                            echo '<option value="' . absint($year) . '" ' . selected($year, $selected_year, false) . '>' . esc_attr($year) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="wpem-calendar-filter-button">
                                <input class="wpem-cfilter-button wpem-theme-button wpem-mt-0 wpem-mb-0" type="button" id="event_calendar_filters_button"  value="<?php _e('Go', 'wp-event-manager-calendar'); ?>"/>        
                                <input type="hidden" id="events_calendar_nonce"  value="<?php echo wp_create_nonce('events_calendar_nonce') ?>" />
                            </div>
                            <div id="calendar-filters-navigation" class="calendar-filters-navigation">
                                <div class="wpem-calendar-left-nav">
                                    <input class="wpem-cp-button wpem-theme-button" type="button" id="calendar_navigation_previous" value="<?php _e('Previous', 'wp-event-manager-calendar'); ?>"/>
                                    <i class="wpem-icon-cheveron-right"></i>
                                </div>
                                <div class="wpem-calendar-right-nav">
                                    <input class="wpem-cp-button wpem-theme-button" type="button" id="calendar_navigation_next" value="<?php _e('Next', 'wp-event-manager-calendar'); ?>"/>
                                    <i class="wpem-icon-cheveron-right"></i>
                                </div>
                            </div>  
                        </div>
                    </div>
                        
                    
                    <?php
                    if (!empty($categories) && is_array($categories))
                        $categories = implode(',', $categories);
                    if (!empty($event_types) && is_array($event_types))
                        $event_types = implode(',', $event_types);
                    ?>
                    <input type="hidden" id="calendar_categories" class="calendar-categories" value="<?php if(!empty( $categories ) ){ echo $categories; } ?>">
                    <input type="hidden" id="calendar_event_types" class="calendar-event-types" value="<?php if( !empty($event_types) ){echo $event_types;} ?>">
                    <input type="hidden" id="calendar_event_keywords" class="calendar_event_keywords" value="<?php if( !empty($event_keywords) ){echo $event_keywords;} ?>">
                    <input type="hidden" id="calendar_event_location" class="calendar_event_keywords" value="<?php if( !empty($event_location) ){echo $event_location;} ?>">
                    <input type="hidden" id="calendar_month" value="<?php if( isset($prev_month) ){ echo absint( $prev_month ); } ?>">
                    <input type="hidden" id="calendar_year" value="<?php if( isset($prev_year) ){ echo absint( $prev_year ); } ?>">
                    <input type="hidden" id="calendar_navigation_month" value="<?php echo absint( $selected_month ); ?>">
                </form>
            </div>

        </div> <!-- end .calendar-filter-container -->

        <div id="calendar-contents-container" class="calendar-contents-container">

            <table cellpadding="0" cellspacing="0" class="calendar">

                <tr>
                    <?php for ($week_day = 0; $week_day <= 6; $week_day++) { ?> <th class="weekday-name-column"> <?php
                        echo $week_days_name[$week_day];
                    }
                    ?> </th>
                </tr>

                <tr>          
                    <?php for ($start = 0; $start < $show_empty_gray_background_untill; $start++) { ?> <td class="empty-gray-background" valign="top"></td> <?php } ?>

                    <?php
                    $count_day_box = $show_empty_gray_background_untill;
                    for ($start_day = 1; $start_day <= $total_days_of_the_selected_month; $start_day++) {
                        $today_class = ( $today == $start_day && $today_month == $selected_month && $today_year == $selected_year ) ? 'today' : '';
                        ?> 

                        <td class="calendar-day-container <?php echo $today_class; ?>" valign="top">

                            <div class="day-number"><?php echo $start_day; ?></div>
                            <?php
                            $meta_query = array(
                                'relation' => 'OR',
                                array(
                                    'key' => '_private_event', // Replace with the actual meta key for the checkbox field
                                    'value' => '1', // Replace with the value representing the checkbox being checked
                                    'compare' => '!=',
                                ),
                                array( 
                                    'key'     => '_private_event',
                                    'compare' => 'NOT EXISTS',
                                )
                            );
                                       
							$events_args = array('post_type' => 'event_listing', 'posts_per_page' => -1,'meta_query'=>$meta_query);
                            
                            $events = new WP_Query($events_args);
                            while ($events->have_posts()) : $events->the_post();
                                //event id & title
                                $event_id = get_the_ID();
                                $event_title = get_the_title();
                                $color_class = '';

                                //featured color
                                $featured = get_post_meta( $event_id, '_featured', true );
                                //cancelled color
                                $cancelled = get_post_meta( $event_id, '_cancelled', true );

                                //timestamp for start date
                                $event_start_date = get_post_meta($event_id, '_event_start_date', true);
                                $event_end_date = get_post_meta($event_id, '_event_end_date', true);
                                $timestamp = strtotime($event_start_date);

                                //define start date
                                $event_start_day = date('j', $timestamp);
                                $event_start_month = date('n', $timestamp);
                                $event_start_year = date('Y', $timestamp);

                                //we check if any events exists on current iteration
                                //if yes, return the link to event
                                if ($start_day == $event_start_day && $selected_month == $event_start_month && $selected_year == $event_start_year) {
                                    if ($color_setting == 'event_category_colors') {

                                        $event_catgegory = get_event_category($post);
                                        if (!empty($event_catgegory))
                                            foreach ($event_catgegory as $value) {
                                                $color_class = ' event-category ' . $value->slug;
                                            }
                                    } else {
                                        $event_type = get_event_type($post);
                                        if (!empty($event_type))
                                            foreach ($event_type as $value) {
                                                $color_class = ' event-type  ' . $value->slug;
                                            }
                                    }

                                    $the_content = get_the_content();
                                    $the_content = do_shortcode($the_content);
                                    ?>
                                    <a href="<?php echo display_event_permalink(); ?>" id="<?php echo $event_id; ?>" class="calendar-event-details-link <?php echo $color_class; ?>" <?php if ($featured == 1 && !$cancelled == 1 ){ echo 'style="background-color: #ffffe4"';  } if ($cancelled == 1){ echo 'style="background-color: #ffe5e5"';  } ?> >
                                        <?php
                                        $title = mb_substr(html_entity_decode($event_title), 0, 13);
                                        if (strlen($event_title) > 13) {
                                            $ellipsis = '...';
                                        } else {
                                            $ellipsis = '';
                                        }
                                        ?>
                                        <?php printf(__('%s%s', 'wp-event-manager-calendar'), $title, $ellipsis); ?>
                                        <div id="pop_up_<?php echo $event_id; ?>" class="calendar-tooltip-box popper">
                                            <div class="calendar-tooltip ">
                                                <div class="calendar-tooltip-banner" style="background-image: url('<?php
                                                $banner = get_event_banner();
                                                if (is_array($banner))
                                                    echo $banner[0];
                                                else
                                                    echo $banner;
                                                ?>')"></div>
                                                <div class="calendar-tooltip-title wpem-heading-text"><?php printf(__('%s%s', 'wp-event-manager-calendar'), $title, $ellipsis); ?></div>
                                                <div class="calendar-tooltip-content">
                                                    <div class="calendar-tooltip-event-start-date">
                                                        <?php _e('Start Date', 'wp-event-manager-calendar'); ?>: <?php display_event_start_date(); ?> <?php display_event_start_time(); ?>
                                                    </div>
                                                    <div class="calendar-tooltip-event-end-date">
                                                        <?php
                                                        if(!empty($event_end_date)){ 
                                                         _e('End Date', 'wp-event-manager-calendar'); ?>: <?php display_event_end_date(); ?> <?php display_event_end_time();
                                                         }
                                                          ?>
                                                    </div>
                                                    <div class="calendar-tooltip-event-location">Location: 
                                                        <?php
                                                        if (get_event_location() == 'Online Event' || get_event_location() == ''): echo __('Online Event', 'wp-event-manager');
                                                        else: display_event_location(false);
                                                        endif;
                                                        ?>
                                                    </div>
                                                    
                                                    <?php
                                                    $content = wp_strip_all_tags(substr($the_content, 0, 100));
                                                    if (strlen(wp_strip_all_tags($the_content)) > 100) {
                                                        $ellipsis = '...';
                                                    } else {
                                                        $ellipsis = '';
                                                    }
                                                    ?>
                                                    <p><?php printf(__('%s%s', 'wp-event-manager-calendar'), $content, $ellipsis); ?></p>
                                                </div>                                     
                                            </div>
                                        </div>
                                    </a>			     
                                    <?php
                                }
                            endwhile;
                            ?>
                        </td>
                        <?php
                        //start new row for every monday week day
                        if ($count_day_box == 6) {
                            ?>
                        </tr>
                        <?php
                        if ($start_day != $total_days_of_the_selected_month) {
                            ?>
                            <tr>
                                <?php
                            }

                            $count_day_box = 0;
                        } else {
                            $count_day_box++;
                        }
                    }
                    ?> <!-- end start_day loop -->

                    <!-- show empty gray background for filling whole month calendar space --> 
                    <?php for ($start = $count_day_box; $start <= 6; $start++) { ?> <td class="empty-gray-background" valign="top"></td> <?php } ?>
                </tr>
            </table>

        </div>	
    </div>	
</div> <!-- calendar-container -->

<?php do_action('after_calendar'); ?>
