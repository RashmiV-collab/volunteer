<div id="event-manager-alerts" class="wpem-main">
	<div class="wpem-alert wpem-alert-info"><?php printf(__('Your event alerts are shown in the table below. Your alerts will be sent to %s.', 'wp-event-manager-alerts'), $user->user_email); ?>
		<a href="<?php echo remove_query_arg('updated', add_query_arg('action', 'add_alert')); ?>">
			<?php _e('Add alert', 'wp-event-manager-alerts'); ?>
		</a>
	</div>
    <div class="wpem-responsive-table-block"> 
		<table class="wpem-responsive-table-wrapper event-manager-alerts table table-bordered event-manager-events table-striped">
			<thead>
				<tr>
					<th class="wpem-heading-text"><?php _e('Alert Name', 'wp-event-manager-alerts'); ?></th>
					<th class="wpem-heading-text"><?php _e('Location', 'wp-event-manager-alerts'); ?></th>
					<th class="wpem-heading-text"><?php _e('Status', 'wp-event-manager-alerts'); ?></th>
					<th class="wpem-heading-text"><?php _e('Date Created', 'wp-event-manager-alerts'); ?></th>
				</tr>
			</thead>
 
			<tbody>
			<?php if($alerts->have_posts()){ 
				while ($alerts->have_posts()):
					$alert = $alerts->the_post(); ?>
					<tr>
						<td data-title="<?php _e('Alert Name', 'wp-event-manager-alerts'); ?>">
							<?php echo esc_html(get_the_title()); ?>
							<ul class="event-alert-actions">
								<?php
									$actions = apply_filters('event_manager_alert_actions', array(
										'view' => array(
											'label' => __('Show Results', 'wp-event-manager-alerts'),
											'nonce' => false
										),
										'email' => array(
											'label' => __('Email', 'wp-event-manager-alerts'),
											'nonce' => true
										),
										'alerts_edit' => array(
											'label' => __('Edit', 'wp-event-manager-alerts'),
											'nonce' => false
										),
										'toggle_status' => array(
											'label' => get_post_status() == 'draft' ? __('Enable', 'wp-event-manager-alerts') : __('Disable', 'wp-event-manager-alerts'),
											'nonce' => true
										),
										'delete' => array(
											'label' => __('Delete', 'wp-event-manager-alerts'),
											'nonce' => true
										)
									), $alert);

									foreach ($actions as $action => $value) {
										$link     =  add_query_arg(array(
											'action'         => $action,
											'alert_id'     => get_the_ID(),
										));
										
										$action_url = remove_query_arg('updated', add_query_arg(array('action' => $action, 'alert_id' => get_the_ID())));

										if($value['nonce'])
											$link = wp_nonce_url($action_url, 'event_manager_alert_actions');

										echo '<li><a href="' . $link . '" class="event-alerts-action-' . $action . '" title="'.$value['label'].'">' . $value['label'] . '</a></li>';
									} ?>

							</ul>
						</td>				
						<td  data-title="<?php _e('Location', 'wp-event-manager-alerts'); ?>" class="alert_location"><?php
							if(get_post_meta(get_the_ID(), '_alert_location', true))
								echo esc_html(get_post_meta(get_the_ID(), '_alert_location', true));
							else
								echo '&ndash;';?>
						</td>
						<td data-title="<?php _e('Status', 'wp-event-manager-alerts'); ?>" class="status"><?php echo get_post_status() == 'draft' ? __('Disabled', 'wp-event-manager-alerts') : __('Enabled', 'wp-event-manager-alerts'); ?></td>
						<td data-title="<?php _e('Date Created', 'wp-event-manager-alerts'); ?>" class="date"><?php echo date_i18n(get_option('date_format'), strtotime(get_the_date())); ?></td>
					</tr>
					<?php 
						endwhile;
						wp_reset_postdata();
				}else { ?>
					<td colspan="8" class="text-center wpem_data_td_empty"><div class="wpem-alert wpem-alert-info"><?php _e('Currently you have no alerts','wp-event-manager-alerts');?></div></td>
				<?php
				   }
				?>
			</tbody>
		</table>
	</div>
	<?php get_event_manager_template('pagination.php', array('max_num_pages' => $max_num_pages)); ?>
</div>
