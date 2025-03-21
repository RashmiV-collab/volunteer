<div id="event-manager-event-registrations" class="wpem-single-event-footer">

	<?php $event_content_toggle = apply_filters('event_manager_event_content_toggle', true);
    $event_content_toggle_class = $event_content_toggle ? 'wpem-event-attendee-info-title wpem-listing-accordion' : 'wpem-event-attendee-info-title';
    ?>

	<div class="<?php echo $event_content_toggle_class; ?> active">
    	<h3 class="wpem-heading-text"><?php _e( 'Attendee Information', 'wp-event-manager-attendee-information' ); ?></h3>
    	<?php if($event_content_toggle) : ?>
            <i class="wpem-icon-minus"></i><i class="wpem-icon-plus"></i>
        <?php endif; ?>
	</div>

	<?php do_action('single_event_attendee_listing_before'); ?>

	<div class="event-registrations wpem-main wpem-event-registrations-list-wrapper event-registrations wpem-listing-accordion-panel active" style="display: block;">
		<div class="event-registrations wpem-event-regi-list-body">
			<?php foreach ( $registrations as $registration ) : ?>
				<?php if(isset($registration->post_title) && !empty($registration->post_title) ) : ?>
					<div class="event-registration event-registration wpem-event-regi-list" id="registration-<?php esc_attr_e( $registration->ID ); ?>">
						<header>
							<?php event_registration_header( $registration ); ?>
						</header>
					</div>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
		<?php get_event_manager_template( 'event-pagination.php', array('max_num_pages' => $max_num_pages, 'current_page' => $current_page) ); ?>
	</div>

	<?php do_action('add_shortcode_after_single_event_listing');?>
</div>