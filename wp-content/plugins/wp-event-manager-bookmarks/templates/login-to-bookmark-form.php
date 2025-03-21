<div class="event-manager-form wp-event-manager-bookmarks-form">
	<div class="clearfix">&nbsp;</div>
	<div class="wpem-event-sidebar-button wpem-bookmark-event-button">
		<a class="bookmark-notice wpem-icon-text-button" href="<?php echo apply_filters( 'event_manager_bookmark_form_login_url', get_option('event_manager_login_page_url',wp_login_url( get_permalink() ) )); ?>">
			<?php printf( __( 'Login to bookmark this %s', 'wp-event-manager-bookmarks' ), $post_type->labels->singular_name ); ?>
		</a>
	</div>
</div>
