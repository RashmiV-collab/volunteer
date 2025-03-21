<?php do_action('event_manager_group_dashboard_before'); ?>

<?php echo !empty($group_dashboard_message) ? $group_dashboard_message : ''; ?>

<?php $event_dashboard = get_option('event_manager_event_dashboard_page_id'); ?>

<div id="wpem-group-dashboard">

	<div class="wpem-dashboard-main-header">
		<div class="wpem-dashboard-main-title wpem-dashboard-main-filter">
			<h3 class="wpem-theme-text"><?php _e('Group Dashboard','wp-event-manager-guests');?></h3>

			<div class="wpem-d-inline-block wpem-dashboard-i-block-btn">

				<?php do_action('event_manager_group_dashboard_button_action_start'); ?>

				<a class="wpem-dashboard-header-btn wpem-dashboard-header-add-btn" title="<?php _e('Add Group','wp-event-manager-guests');?>" href="<?php echo add_query_arg( array( 'action' => 'add_group' ), get_permalink($event_dashboard) ); ?>"><i class="wpem-icon-plus"></i></a>

				<a href="javascript:void(0)" title="<?php _e('Filter','wp-event-manager-guests');?>" class="wpem-dashboard-event-filter wpem-dashboard-header-btn"><i class="wpem-icon-filter"></i></a>

				<?php do_action('event_manager_group_dashboard_button_action_end'); ?>
			</div>
		</div>

		<?php 
		$display_block = '';
		if(!empty($event_id))
		{
			$display_block = 'wpem-d-block';
		}
		?>

		<form action="" class="wpem-form-wrapper wpem-event-dashboard-filter-toggle wpem-dashboard-main-filter-block <?php echo $display_block;?>" method="get">
			<div class="wpem-events-filter">				
				<div class="wpem-events-filter-block">
					<div class="wpem-form-group">							
	    				<select name="event_id" id="event_id">
							<option value=""><?php _e('Select Event','wp-event-manager-guests');?></option>
							<?php if(!empty($events)) : ?>
								<?php foreach ($events as $key => $event) : ?>
									<option value="<?php echo esc_html( $event->ID ); ?>" <?php selected( $event_id, $event->ID ); ?>><?php echo esc_html( $event->post_title ); ?></option>
								<?php endforeach; ?>
							<?php endif; ?>
						</select>
	    			</div>
				</div>
				<div class="wpem-events-filter-block wpem-events-filter-submit">
					<div class="wpem-form-group">
						<input type="hidden" name="action" value="show_groups">
						
						<button type="submit" class="wpem-theme-button"><?php _e('Filter','wp-event-manager-guests');?></button>
						<input type="submit" class="wpem-theme-button" value="Reset" name="reset" />
					</div>
				</div>				
			</div>
		</form>
	</div>

	<div class="wpem-responsive-table-block">
		<?php do_action('wpem_group_dashboard_table_start');?>
		<table class="wpem-main wpem-responsive-table-wrapper">
			<thead>
				<tr>
					<?php foreach( $group_dashboard_columns as $key => $column ) : ?>						
						<th class="wpem-heading-text <?php echo esc_attr( $key ); ?>"><?php echo esc_html( $column ); ?></th>
					<?php endforeach; ?>
				</tr>
			</thead>
			<tbody>
				<?php if ( ! $groups ) : ?>
				<tr>
					<td colspan="<?php echo count($group_dashboard_columns); ?>"><div class="wpem-alert wpem-alert-danger"><?php _e( 'You do not have any groups.', 'wp-event-manager-guests' ); ?></div></td>
				</tr>
				<?php else : ?>
				<?php foreach ( $groups as $group ) : ?>
					<tr>
					<?php  foreach ( $group_dashboard_columns as $key => $column ) : ?>
						<td data-title="<?php echo esc_html( $column ); ?>" class="<?php echo esc_attr( $key ); ?>">
							<?php if ('group_name' === $key ) : ?>
							<?php $name= esc_html( $group->$key ); ?>
							<?php 
									if( isset($name) && !empty(trim($name)) )
									{
										echo $name ;
									}
									else
									{
										echo '-';
									}
							?>
							<?php elseif ('group_fields' === $key) : ?>
								<?php $fields = json_decode($group->$key, true);
								echo !empty($fields) ? esc_html( implode(', ', $fields) ) : ''; ?>

							<?php elseif ('event_id' === $key) : ?>
								<?php if(get_post_status( $group->$key ) === 'publish'): ?>
								<?php echo '<a href="'. get_permalink($group->$key) .'">' . get_the_title($group->$key) . '</a>'; ?>
								<?php else: ?>
									<?php echo "&ndash;"; ?>
								<?php endif; ?>
							
							<?php elseif ('guest_count' === $key) : ?>
								<?php $guests = get_guests($group->id, $user_id, $group->event_id);
								echo ( !empty($guests) ) ? '<a href="' . add_query_arg( array( 'action' => 'show_guest_lists', 'event_id' => $group->event_id, 'group_id' => $group->id ), get_permalink($event_dashboard) ) . '">' . count($guests) . '</a>' : '&ndash;'; ?>

							<?php elseif ('group_action' === $key) : ?>

								<div class="wpem-dboard-group-action">
									<div class="wpem-dboard-group-act-btn">
										<a href="<?php echo get_permalink($event_dashboard);?>?action=edit_group&group_id=<?php echo $group->id; ?>" class="group-dashboard-action-edit" title="<?php _e('Edit', 'wpem-guests'); ?>"><i class="wpem-icon-pencil2"></i></a>
									</div>
									<?php
									$action_url = add_query_arg ( array (
											'action' => 'delete_group',
											'group_id' => $group->id,
											'event_id' => $event_id,
											), get_permalink($event_dashboard) );
									$action_url = wp_nonce_url ( $action_url, 'event_manager_group_actions' )
									?>
									<div class="wpem-dboard-group-act-btn">
										<a href="<?php echo $action_url; ?>" class="group-dashboard-action-delete" > <i class="wpem-icon-bin2"></i></a>
									</div>
								</div>
							
							<?php else : ?>
								<?php echo esc_html( $group->$key ); ?>
							
							<?php endif; ?>
						</td>
					<?php endforeach; ?>
					</tr>
				<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
		<?php do_action('wpem_group_dashboard_table_end');?>
	</div>
	<nav class="event-manager-pagination-2">
		<?php			
			global $wp;
			$link = $wp->request;			
			echo paginate_links( apply_filters( 'event_manager_pagination_args', array(
					'base' 		=> esc_url_raw(add_query_arg( 'paged', '%#%')),
					'format'    => '',
					'current'   => $paged,
					'total' 	=> ceil($total_groups / $number_groups),
					'prev_text' => '&larr;',
					'next_text' => '&rarr;',
					'type'      => 'list',
					'end_size'  => 3,
					'mid_size'  => 3
				 ) ) );
		?>
	</nav>
   </div>
   
<?php do_action('event_manager_group_dashboard_after'); ?>
