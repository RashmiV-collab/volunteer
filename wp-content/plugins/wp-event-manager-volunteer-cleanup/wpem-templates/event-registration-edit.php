<form class="wpem-form-wrapper event-manager-registration-edit-form event-manager-form" method="post">
	<div class="wpem-form-group">
		<label class="wpem-form-label-text" for="registration-status-<?php esc_attr_e($registration->ID); ?>"><?php _e('Registration status', 'wp-event-manager-registrations'); ?>:</label>
		<div class="field">
			<select id="registration-status-<?php esc_attr_e($registration->ID); ?>" name="registration_status">
				<?php foreach(get_event_registration_statuses() as $name => $label) : ?>
					<option value="<?php echo esc_attr($name); ?>" <?php selected($registration->post_status, $name); ?>>
						<?php echo esc_html($label); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
	</div>
	<div class="wpem-form-footer">
		<a class="wpem-theme-button delete_event_registration" href="<?php echo wp_nonce_url(add_query_arg('delete_event_registration', $registration->ID), 'delete_event_registration'); ?>"><span><?php _e('Delete', 'wp-event-manager-registrations'); ?></span></a>
		<input type="hidden" name="old_registration_status" value="<?php echo $registration->post_status; ?>" />
		<input type="hidden" name="registration_id" value="<?php echo absint($registration->ID); ?>" />
		<button type="submit" class="wpem-theme-button" name="wp_event_manager_edit_registration" value="<?php esc_attr_e('Save changes', 'wp-event-manager-registrations'); ?>"><?php esc_attr_e('Save changes', 'wp-event-manager-registrations'); ?></button>
		<?php wp_nonce_field('edit_event_registration'); ?>
	</div>
</form>