<?php
global $post;
if (isset($event_id)) {
    $post = get_post($event_id);
    setup_postdata($post);
}
$start_date = get_post_meta(get_the_ID(),'_event_start_date',true);
$end_date =get_post_meta(get_the_ID(),'_event_end_date',true);?>

<div class="wpem-main wpem-google-tooltip-event-wrapper">
	<a href='<?php  echo esc_url( get_permalink( get_the_ID() ));?>' class="wpem-event-action-url">
		<div class="wpem-event-banner">
	        <div class="wpem-event-banner-img" style="background-image: url('<?php echo get_event_thumbnail($post); ?>')"></div>
		</div>
	</a>
	<div class="wpem-google-map-tooltip-information">
		<a href='<?php  echo esc_url( get_permalink( get_the_ID() ));?>' class="wpem-event-action-url">
			<div class="wpem-google-tooltip-event-title">
				<?php the_title();?>
			</div>
			<div class="wpem-google-tooltip-event-date-time">
				<?php 
				display_event_start_date();
				
				if ( get_event_start_time() )  {
                    display_date_time_separator();
                    display_event_start_time();
                }

                if ( get_event_end_date() != '' || get_event_end_time() ){
                    _e(' to ', 'wp-event-manager-google-maps');
                }

                if ( get_event_start_date() != get_event_end_date() ){
                    display_event_end_date();
                }
               
                if ( get_event_end_date() != '' && get_event_end_time() ){
                    display_date_time_separator();
                }
               
                if ( get_event_end_time() ){
                    display_event_end_time();
                }?>
			</div>
			<div class="wpem-google-tooltip-location">
				<?php display_event_location();?>
			</div>
		</a>
		<?php if(get_event_type( $post )) {  ?>
			<div class="wpem-google-tooltip-event-event-type">
				<?php _e(display_event_type( $post ),'wp-event-manager-google-maps');?>
			</div>
		<?php } ?>
	</div>	
</div>