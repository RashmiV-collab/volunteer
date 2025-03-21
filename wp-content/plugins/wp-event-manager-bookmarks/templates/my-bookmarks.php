<div id="event-manager-bookmarks" class="wpem-responsive-table-block">
	<table class="wpem-main wpem-responsive-table-wrapper event-manager-bookmarks table table-bordered event-manager-events table-striped">
		<thead>
			<tr>
				<th class="wpem-heading-text"><?php _e( 'Bookmark', 'wp-event-manager-bookmarks' ); ?></th>
				<th class="wpem-heading-text"><?php _e( 'Notes', 'wp-event-manager-bookmarks' ); ?></th>
				<th class="wpem-heading-text"><?php _e( 'Event Start', 'wp-event-manager-bookmarks' ); ?></th>
				<th class="wpem-heading-text"><?php _e( 'Event End', 'wp-event-manager-bookmarks' ); ?></th>
				<th class="wpem-heading-text"><?php _e( 'Action', 'wp-event-manager-bookmarks' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php 
			foreach ( $bookmarks as $bookmark ) : 
				if ( get_post_status( $bookmark->post_id ) !== 'publish' ) {
					continue;
				}
				$has_bookmark = true; ?>
				<tr>
					<td width="25%">
						<?php echo '<a href="' . get_permalink( $bookmark->post_id ) . '">' . get_the_title( $bookmark->post_id ) . '</a>'; ?>						
					</td>
					<td width="25%">
						<?php echo wpautop( wp_kses_post( $bookmark->bookmark_note ) ); ?>
					</td>
					<td width="20%">
						<?php
						display_event_start_date('', '', true, $bookmark->post_id);
						if (get_event_start_time($bookmark->post_id)){
                            display_date_time_separator();
                            display_event_start_time('', '', true, $bookmark->post_id);
                        } ?>
					</td>
					<td width="20%">
						<?php 
						display_event_end_date('', '', true, $bookmark->post_id);
						
						if (get_event_end_time($bookmark->post_id)){
                            display_date_time_separator();
                            display_event_end_time('', '', true, $bookmark->post_id);
                        } ?>
					</td>
					<td width="10%">
						<ul class="event-manager-bookmark-actions">
							<?php
								$actions = apply_filters( 'event_manager_bookmark_actions', array(
									'delete' => array(
										'label' => __( 'Delete', 'wp-event-manager-bookmarks' ),
										'url'   =>  wp_nonce_url( add_query_arg( 'remove_bookmark', $bookmark->post_id ), 'remove_bookmark' )
									)
								), $bookmark );

								foreach ( $actions as $action => $value ) {
									echo '<li><a href="' . esc_url( $value['url'] ) . '" class="event-manager-bookmark-action-' . $action . '" title="' . $value['label'] . '"></a></li>';
								}
							?>
						</ul>
					</td>
				</tr>
			<?php endforeach;  

			if ( empty( $has_bookmark ) ) : ?>
				<tr>
					<td colspan="4"><?php _e( 'You currently have no bookmarks.', 'wp-event-manager-bookmarks' ); ?></td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>
</div>