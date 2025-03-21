<?php do_action('event_manager_guests_dashboard_before'); ?>

<?php echo !empty($guest_lists_dashboard_message) ? $guest_lists_dashboard_message : ''; ?>

<?php $event_dashboard = get_option('event_manager_event_dashboard_page_id'); ?>

<?php $group_fields = isset($group->group_fields) ? json_decode($group->group_fields, true) : []; ?>

<div id="wpem-guest-dashboard">

	<div class="wpem-dashboard-main-header">
		
		<div class="wpem-dashboard-main-title wpem-dashboard-main-filter">
			<h3 class="wpem-theme-text"><?php _e('Guest Dashboard','wp-event-manager-guests');?></h3>

			<div class="wpem-d-inline-block wpem-dashboard-i-block-btn">

				<?php do_action('event_manager_guests_dashboard_button_action_start', $event_id); ?>

				<a class="wpem-dashboard-header-btn wpem-dashboard-header-add-btn" title="<?php _e('Add Guest','wp-event-manager-guests');?>" href="<?php echo add_query_arg( array( 'action' => 'add_guest' ), get_permalink($event_dashboard) ); ?>"><i class="wpem-icon-plus"></i></a>

				<a href="javascript:void(0)" title="<?php _e('Filter','wp-event-manager-guests');?>" class="wpem-dashboard-event-filter wpem-dashboard-header-btn"><i class="wpem-icon-filter"></i></a>

				<?php do_action('event_manager_guests_dashboard_button_action_end', $event_id); ?>
			</div>
		</div>

		<?php 
		$display_block = '';
		if( !empty($event_id) || !empty($group_id) )
		{
			$display_block = 'wpem-d-block';
		}
		?>
		<form action="" class="wpem-form-wrapper wpem-event-dashboard-filter-toggle wpem-group-dashboard-filter-toggle wpem-dashboard-main-filter-block <?php echo $display_block;?>" method="get">
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
				<div class="wpem-events-filter-block">
					<div class="wpem-form-group">
						<select name="group_id" id="group_id">
							<option value=""><?php _e('Select Group','wp-event-manager-guests');?></option>
							<?php if(!empty($groups)) : ?>
								<?php foreach ($groups as $key => $group) : ?>
									<option value="<?php echo esc_html( $group->id ); ?>" <?php selected( $group_id, $group->id ); ?>><?php echo esc_html( $group->group_name ); ?></option>
								<?php endforeach; ?>
							<?php endif; ?>
						</select>
	    			</div>
				</div>
				<div class="wpem-events-filter-block wpem-events-filter-submit">
					<div class="wpem-form-group">
						<input type="hidden" name="action" value="show_guest_lists">

						<button type="submit" class="wpem-theme-button"><?php _e('Filter','wp-event-manager-guests');?></button>
						<input type="submit" class="wpem-theme-button" value="Reset" name="reset" />
					</div>
				</div>
			</div>
		</form>
	</div>
			
	<div class="wpem-responsive-table-block">
		<table class="wpem-main wpem-responsive-table-wrapper">
			<thead>
				<tr><th class="wpem-heading-text" ><input type="checkbox" id="all_select" ></th>
					<input type="hidden" name="guests_ids" id="guests_ids" value="">
					<?php foreach( $fields as $name => $field ) : ?>
						<?php if ( !empty($group_fields) ) : ?>
							<?php if ( in_array($name, $group_fields) ) : ?>
								<th class="wpem-heading-text <?php echo esc_attr( $name ); ?>"><?php echo esc_html( $field['label'] ); ?></th>
							<?php endif; ?>
						<?php else : ?>
							<th class="wpem-heading-text <?php echo esc_attr( $name ); ?>"><?php echo esc_html( $field['label'] ); ?></th>
						<?php endif; ?>
					<?php endforeach; ?>
						<th class="wpem-heading-text guest_lists_group"><?php _e('Group', 'wp-event-manager-guests'); ?></th>
						<th class="wpem-heading-text"><?php _e('Event', 'wp-event-manager-guests'); ?></th>
						<th class="wpem-heading-text guest_lists_check_in"><?php _e('Check In', 'wp-event-manager-guests'); ?></th>
						<th class="wpem-heading-text guest_lists_action"><?php _e('Action', 'wp-event-manager-guests'); ?></th>
				</tr>
			</thead>			
			<tbody>
				<?php if ( empty($guests->have_posts()) ) : ?>
					<tr>
						<td colspan="<?php echo count($fields)+3; ?>">
						<div class="wpem-alert wpem-alert-danger">
							<?php _e( 'There are not guest.', 'wp-event-manager-guests' ); ?></div>
						</td>
					</tr>
				<?php else : ?>
					<?php while( $guests->have_posts() ) : $guests->the_post(); ?>
						<tr class="guests-id-<?php echo $guests->post->ID; ?>" ><td><input type="checkbox" class="guest-list" name="guest_list_id[]" value="<?php echo $guests->post->ID; ?>"></td>
							<?php foreach( $fields as $name => $field ) : ?>
								<?php if ( !empty($group_fields) ) : ?>
									<?php if ( in_array($name, $group_fields) ) : ?>
										<td data-title="<?php echo esc_html( $name ); ?>" class="<?php echo esc_attr( $name ); ?>"> 
										</td>
									<?php endif; ?>
								<?php else : ?>
									<td data-title="<?php echo esc_html( $name ); ?>" class="<?php echo esc_attr( $name );?>">
									<?php $guest_name=get_post_meta($guests->post->ID, $name, true);?>
											  <?php 
													if(is_array($guest_name)){
											  			$s = '';
											  			foreach ($guest_name as $key => $value) {
											  				echo $s.$value;
											  				$s = ', ';
											  			}
											  		}elseif( isset($guest_name) && !empty($guest_name) ){	
														if($field['type'] ==='checkbox' && $guest_name === '1' ){
															echo "Yes";
														}else{
														echo $guest_name;
														}
													}
													else
													{
														echo '-';
													}
													?>  
									</td>
								<?php endif; ?>
						<?php endforeach; ?>

							<td data-title="<?php _e( 'Group', 'wp-event-manager-guests' ); ?>" class="guest_lists_group">
								<?php
								$group_id = get_post_meta($guests->post->ID, '_guests_group', true);

								if( isset($group_id) && !empty($group_id) )
								{
									$group = get_event_guests_group($group_id);
									
									echo $group->group_name;
								}
								else
								{
									echo '-';
								}
								?>
							</td>
							<td><a href="<?php echo get_the_permalink($guests->post->post_parent ); ?>"><?php echo get_the_title( $guests->post->post_parent ); ?></a></td>
							<td data-title="<?php _e( 'Check In', 'wp-event-manager-guests' ); ?>" class="guest_lists_check_in">
								<?php
								$check_in = get_post_meta( $guests->post->ID , '_check_in',true );  
				                if(isset($check_in) && $check_in == true ){
				                      $checkin_hidden =   'wpem-checkin-hide';
				                      $undo_hidden = '';
				                }
				                else{
				                    $checkin_hidden = '';
				                    $undo_hidden = 'wpem-checkin-hide';
				                }
				                echo "<span class='".$checkin_hidden."'><a class='wpem-theme-button guest_checkin' data-value='1' data-source='web' data-post-id='".$guests->post->ID."' data-event-id='".$event_id."'><span>".__('Check in','wp-event-manager-guests')."</span></a></span>";

                				echo "<span class='".$undo_hidden."'><a class='wpem-theme-button guest_uncheckin'  data-value='0' data-source='' data-post-id='".$guests->post->ID."' data-event-id='".$event_id."' href='#'><span>".__('Undo Check in','wp-event-manager-guests')."</span></a></span>";
								?>
							</td>

							<td data-title="<?php _e( 'Action', 'wp-event-manager-guests' ); ?>" class="guest_lists_action">
								<div class="wpem-dboard-guest-action">
									<div class="wpem-dboard-guest-act-btn">
										<a href="<?php echo get_permalink($event_dashboard);?>?action=edit_guest&guest_id=<?php echo $guests->post->ID; ?>" class="guest-dashboard-action-edit" title="<?php _e('Edit', 'wp-event-manager-guests'); ?>"><i class="wpem-icon-pencil2"></i></a>
									</div>
									<?php
									$action_url = add_query_arg ( array ( 
											'action' => 'delete_guest',
											'event_id' => $event_id,
											// 'group_id' => $group_id, 											
											'guest_id' => $guests->post->ID, 
											), get_permalink($event_dashboard) );
									$action_url = wp_nonce_url ( $action_url, 'event_manager_guests_actions' )
									?>
									<div class="wpem-dboard-guest-act-btn">
										<a href="<?php echo $action_url; ?>" class="guest-dashboard-action-delete" title="<?php _e('Delete', 'wp-event-manager-guests'); ?>"><i class="wpem-icon-bin2"></i></a>
									</div>
								</div>
							</td>
						</tr>
					<?php endwhile; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
	<button id="guest_delete" type="button" class="wpem-theme-button"><?php _e( 'Delete', 'wp-event-manager-guests' ) ?></button>
   </div>
    <?php
	    get_event_manager_template( 'pagination.php', array('max_num_pages' => $max_num_pages ) );

	    wp_reset_postdata();
    ?> 
   
<?php do_action('event_manager_guests_dashboard_after'); ?>
