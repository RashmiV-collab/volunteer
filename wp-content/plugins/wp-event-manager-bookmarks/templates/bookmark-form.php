<?php global $wp; ?>
<div class="clearfix">&nbsp;</div>
<?php if ( $is_bookmarked ) : ?>
	<a class="event-manager-bookmark-action-delete wpem-icon-text-button remove-bookmark" href="<?php echo wp_nonce_url( add_query_arg( 'remove_bookmark', absint( $post->ID ), get_permalink() ), 'remove_bookmark' ); ?>">
		<span><?php printf( __( 'Remove Bookmarked!', 'wp-event-manager-bookmarks' ), $post_type->labels->singular_name ); ?></span>
		<i class="wpem-icon-cross"></i>
	</a> 
<?php else : ?>
	<a class="wpem-icon-text-button bookmark-notice" href="#">
		<i class="wpem-icon-bookmark"></i> 
		<?php printf( __( 'Bookmark This %s', 'wp-event-manager-bookmarks' ), ucwords( $post_type->labels->singular_name ) ); ?>
	</a>
<?php endif; ?>
<div class="bookmark-details" style="display: none;">
	<form method="post" action="<?php echo defined( 'DOING_AJAX' ) ? '' : esc_url( remove_query_arg( array( 'page', 'paged' ), add_query_arg( $wp->query_string, '', home_url( $wp->request ) ) ) ); ?>" class="event-manager-form wp-event-manager-bookmarks-form wpem-form-wrapper">
		<?php do_action('event_manager_bookmark_form_fields_start'); ?>
		<div class="wpem-form-group">
			<label for="bookmark_notes" class="wpem-form-label">
				<?php _e( 'Notes:', 'wp-event-manager-bookmarks' ); ?>
			</label>
			<textarea name="bookmark_notes" id="bookmark_notes" placeholder="<?php _e( 'Notes:', 'wp-event-manager-bookmarks' ); ?>" cols="25" rows="3"><?php echo esc_textarea( $note ); ?></textarea>
			<?php wp_nonce_field( 'update_bookmark' ); ?>
		</div>
		<?php do_action('event_manager_bookmark_form_fields_end'); ?>
		<div class="wpem-form-footer">
			<input type="hidden" name="bookmark_post_id" value="<?php echo absint( $post->ID ); ?>" />
			<input type="submit" class="wpem-theme-button" name="submit_bookmark" value="<?php echo $is_bookmarked ? __( 'Update Bookmark', 'wp-event-manager-bookmarks' ) : __( 'Add Bookmark', 'wp-event-manager-bookmarks' ); ?>" />
		</div>
	</form>
</div>