<form method="post" class="wpem-form-wrapper event-manager-form dcm-test">
	<?php do_action('event_manager_alert_form_fields_start'); ?>
	<fieldset class="wpem-form-group">
		<label for="alert_name" class="wpem-form-label-text"><?php _e( 'Alert Name', 'wp-event-manager-alerts' ); ?></label>
		<div class="field">
			<input type="text" name="alert_name" value="<?php echo esc_html( $alert_name ); ?>" id="alert_name" class="input-text" placeholder="<?php _e( 'Enter a name for your alert', 'wp-event-manager-alerts' ); ?>" />
		</div>
	</fieldset>
	
	<fieldset class="wpem-form-group">
		<label for="alert_location" class="wpem-form-label-text"><?php _e( 'Zip Code', 'wp-event-manager-alerts' ); ?></label>
		<div class="field">
			<input type="text" name="alert_location" value="<?php echo esc_attr( $alert_location ); ?>" id="alert_location" class="input-text" placeholder="<?php _e( 'US Zip Code Only', 'wp-event-manager-alerts' ); ?>" onkeypress="validateInput(event)"/>
		</div>
	</fieldset>
	<!-- -------- -->
	<input type="hidden" name="alert_keyword" value="" id="alert_keyword" class="input-text"/>
	<input type="hidden" name="alert_event_type[]" value=""/>
	<!-- -------- -->
	<fieldset class="wpem-form-group">
		<label for="alert_frequency" class="wpem-form-label-text"><?php _e( 'Email Frequency', 'wp-event-manager-alerts' ); ?></label>
		<div class="field">
		<?php /*<select name="alert_frequency" id="alert_frequency">
				<option value="weekly" <?php selected( $alert_frequency, 'weekly' ); ?>><?php _e( 'Weekly', 'wp-event-manager-alerts' ); ?></option>
				<option value="fortnightly" <?php selected( $alert_frequency, 'fortnightly' ); ?>><?php _e( 'Fortnightly', 'wp-event-manager-alerts' ); ?></option>
			</select> */ 
			?>
			<?php _e( 'Weekly', 'wp-event-manager-alerts' ); ?>
		</div>
	</fieldset>
	<?php do_action('event_manager_alert_form_fields_end'); ?>
	<div class="wpem-form-footer">
		<?php wp_nonce_field( 'event_manager_alert_actions' ); ?>
		<input type="hidden" name="alert_id" value="<?php echo absint( $alert_id ); ?>" />
		<input type="submit" name="submit-event-alert" class="wpem-theme-button" value="<?php _e( 'Save alert', 'wp-event-manager-alerts' ); ?>" />
	</div>
</form>