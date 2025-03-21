<p class="wpem-alert wpem-alert-success">
    <i class="wpem-icon-user-check"></i>
    <?php _e( 'Your bookmark has been add successfully.', 'wp-event-manager-bookmarks');
    if(isset($bookmarks_page_id) && !empty($bookmarks_page_id))
        echo sprintf( __('You can check it from <a href="%s">here</a>', 'wp-event-manager-bookmarks' ), get_the_permalink($bookmarks_page_id) ); ?>
</p>