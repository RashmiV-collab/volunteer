<?php
$within_dropdown=WPEM_Google_Maps_Filters::get_within_filter();
$distance_dropdown=WPEM_Google_Maps_Filters::get_distance_filter();
$order_by_dropdown=WPEM_Google_Maps_Filters::get_order_by_filter();
$default_within = isset($within_dropdown[0]) ? $within_dropdown[0] : ''; ?>
<div class="wpem-row">
    <!-- Search by map section section start -->	
    <div class="wpem-col">
        <div class="wpem-form-group">
            <label for="search_within_radius" class="wpem-form-label"><?php _e( 'Within', 'wp-event-manager-google-maps' ); ?></label>
            <select name="search_within_radius[]" id="search_within_radius" class="event-manager-category-dropdown" data-placeholder="Within" data-no_results_text="<?php _e('No results match', 'wp-event-manager-google-maps');?>" data-multiple_text="<?php _e('Select Some Options', 'wp-event-manager-google-maps');?>" >
                <option value="" ><?php _e('Within', 'wp-event-manager-google-maps');?></option>
                <?php foreach ( $within_dropdown as $key => $value ) : ?>
                    <option value="<?php echo $value; ?>" >
                        <?php printf(__('%s', 'wp-event-manager-google-maps'), $value); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    
    <div class="wpem-col">
        <div class="wpem-form-group">
            <label for="search_distance_units" class="wpem-form-label"><?php echo __( 'Miles', 'wp-event-manager-google-maps' ); ?></label>
            <select name="search_distance_units[]" id="search_distance_units" class="event-manager-category-dropdown" data-placeholder="Miles" data-no_results_text="<?php _e('No results match', 'wp-event-manager-google-maps');?>" data-multiple_text="<?php _e('Select Some Options', 'wp-event-manager-google-maps');?>" >
                <?php foreach ( $distance_dropdown as $key => $value ) : ?>
                    <option value="<?php echo $key ; ?>" >
                        <?php printf(__('%s', 'wp-event-manager-google-maps'), $value); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    
    <div class="wpem-col">
        <div class="wpem-form-group">
            <label for="search_orderby" class="wpem-form-label"><?php _e( 'Sort By', 'wp-event-manager-google-maps' ); ?></label>
            <select name="search_orderby[]" id="search_orderby" class="event-manager-category-dropdown" data-placeholder="Sort By" data-no_results_text="<?php _e('No results match', 'wp-event-manager-google-maps');?>" data-multiple_text="<?php _e('Select Some Options', 'wp-event-manager-google-maps');?>" >
                <option value=""><?php echo __('Order By', 'wp-event-manager-google-maps');?></option>
                <?php foreach ( $order_by_dropdown as $key => $value ) : ?>
                    <?php $label = str_replace('_', ' ', $value); 
                    $label = ucwords($label); ?>
                    <option value="<?php echo $value ; ?>" >
                        <?php printf(__('%s', 'wp-event-manager-google-maps'), $label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</div>
<input type="hidden" id="google_map_lat" name="google_map_lat" value="" />
<input type="hidden" id="google_map_lng" name="google_map_lng" value="" />	