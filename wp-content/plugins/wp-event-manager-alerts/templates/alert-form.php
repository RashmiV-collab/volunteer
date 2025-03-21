<form method="post" class="wpem-form-wrapper event-manager-form">
	<?php do_action('event_manager_alert_form_fields_start'); ?>
	<fieldset class="wpem-form-group">
		<label for="alert_name" class="wpem-form-label-text"><?php _e('Alert Name', 'wp-event-manager-alerts'); ?></label>
		<div class="field">
			<input type="text" name="alert_name" value="<?php echo esc_html($alert_name); ?>" id="alert_name" class="input-text" placeholder="<?php _e('Enter a name for your alert', 'wp-event-manager-alerts'); ?>" />
		</div>
	</fieldset>
	<fieldset class="wpem-form-group">
		<label for="alert_keyword" class="wpem-form-label-text"><?php _e('Keyword', 'wp-event-manager-alerts'); ?></label>
		<div class="field">
			<input type="text" name="alert_keyword" value="<?php echo esc_attr($alert_keyword); ?>" id="alert_keyword" class="input-text" placeholder="<?php _e('Optionally add a keyword to match events against', 'wp-event-manager-alerts'); ?>" />
		</div>
	</fieldset>
	<fieldset class="wpem-form-group">
		<label for="alert_location" class="wpem-form-label-text"><?php _e('Location', 'wp-event-manager-alerts'); ?></label>
		<div class="field">
			<input type="text" name="alert_location" value="<?php echo esc_attr($alert_location); ?>" id="alert_location" class="input-text" placeholder="<?php _e('Optionally define a location to search against', 'wp-event-manager-alerts'); ?>" />
		</div>
	</fieldset>
	<?php if(get_option('event_manager_enable_categories') && wp_count_terms('event_listing_category') > 0) : ?>
		<fieldset class="wpem-form-group">
			<label for="alert_cats" class="wpem-form-label-text"><?php _e('Event Categories', 'wp-event-manager-alerts'); ?></label>
			<div class="field">
				<select name="alert_cats[]" data-placeholder="<?php _e('Choose an Event Category', 'wp-event-manager-alerts'); ?>" id="alert_cats" multiple="multiple" class="event-manager-chosen-select">
					<?php
						$terms = get_event_listing_categories();
						foreach ($terms as $term)
							echo '<option value="' . esc_attr($term->term_id) . '" ' . selected(in_array($term->term_id, $alert_cats), true, false) . '>' . esc_html($term->name) . '</option>';
					?>
				</select>
			</div>
		</fieldset>
	<?php endif; ?> 
	<?php if(taxonomy_exists('event_listing_tag') && !is_wp_error (wp_count_terms('event_listing_tag')) && wp_count_terms('event_listing_tag') > 0) : ?>
		<fieldset class="wpem-form-group">
			<label for="alert_tags" class="wpem-form-label-text"><?php _e('Event Tags', 'wp-event-manager-alerts'); ?></label>
			<div class="field">
				<?php
					wp_enqueue_script('wp-event-manager-term-multiselect');

					event_manager_dropdown_selection(array(
						'taxonomy'     => 'event_listing_tag',
						'hierarchical' => 0,
						'name'         => 'alert_tags',
						'orderby'      => 'name',
						'selected'     => !empty($alert_tags) ? $alert_tags : '',
						'hide_empty'   => false,
						'placeholder'  => __('Choose an Event Tag', 'wp-event-manager-alerts')
					));
				?>
			</div>
		</fieldset>
	<?php endif; ?>
	<fieldset class="wpem-form-group">
		<label for="alert_event_type" class="wpem-form-label-text"><?php _e('Event Types', 'wp-event-manager-alerts'); ?></label>
		<div class="field">
			<select name="alert_event_type[]" data-placeholder="<?php _e('Choose an Event Type', 'wp-event-manager-alerts'); ?>" id="alert_event_type" multiple="multiple" class="event-manager-chosen-select">
				<?php
					$terms = get_event_listing_types();
					foreach ($terms as $term)
						echo '<option value="' . esc_attr($term->term_id) . '" ' . selected(in_array($term->term_id, $alert_event_type), true, false) . '>' . esc_html($term->name) . '</option>';
				?>
			</select>
		</div>
	</fieldset>
	<fieldset class="wpem-form-group">
		<label for="alert_frequency" class="wpem-form-label-text"><?php _e('Email Frequency', 'wp-event-manager-alerts'); ?></label>
		<div class="field">
			<select name="alert_frequency" id="alert_frequency">
				<option value="daily" <?php selected($alert_frequency, 'daily'); ?>><?php _e('Daily', 'wp-event-manager-alerts'); ?></option>
				<option value="weekly" <?php selected($alert_frequency, 'weekly'); ?>><?php _e('Weekly', 'wp-event-manager-alerts'); ?></option>
				<option value="fortnightly" <?php selected($alert_frequency, 'fortnightly'); ?>><?php _e('Fortnightly', 'wp-event-manager-alerts'); ?></option>
			</select>
		</div>
	</fieldset>
	<?php do_action('event_manager_alert_form_fields_end'); ?>
	<div class="wpem-form-footer">
		<?php wp_nonce_field('event_manager_alert_actions'); ?>
		<input type="hidden" name="alert_id" value="<?php echo absint($alert_id); ?>" />
		<input type="submit" name="submit-event-alert" class="wpem-theme-button" value="<?php _e('Save alert', 'wp-event-manager-alerts'); ?>" />
	</div>
</form>