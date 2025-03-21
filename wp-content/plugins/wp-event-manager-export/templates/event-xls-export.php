<div id="wpem-export-xls-modal-popup" class="wpem-modal" role="dialog" aria-labelledby="<?php _e('Event XLS Export','wp-event-manager-export');?>">
	<div class="wpem-modal-content-wrapper">
		<div class="wpem-modal-header">
			<div class="wpem-modal-header-title">
				<h3 class="wpem-modal-header-title-text"><?php _e('Event XLS Export','wp-event-manager-export');?></h3>
			</div>
			<div class="wpem-modal-header-close">
				<a href="javascript:void(0);" class="wpem-modal-close">x</a>
			</div>
		</div>
 
		<div class="wpem-modal-content">
			<a id="event_xls_default" href="?event_xls_default&user_id=<?php echo get_current_user_id(); ?>&file_type=xls" class="wpem-theme-button">
				<span>
					<i class="wpem-icon-download3"></i> 
					<?php _e('Default', 'wp-event-manager-export'); ?>
				</span>
			</a>
	 		<a id="event_xls_custome" href="#" class="wpem-theme-button">
				<span>
					<?php _e('Custom', 'wp-event-manager-export'); ?> 
					<i class="wpem-icon-arrow-down "></i>
				</span>
			</a>

			<form action="" method="post" class="wpem-form-wrapper" id="custom_events_xls_form" style="display:none;">
				<div class="wpem-form-group">
				 	<fieldset class="wpem-form-group fieldset-event_fields">
						<label for="event_name"><?php _e('Events','wp-event-manager-export');?></label>
						<div class="field">
							<select class="event-manager-select-chosen export_events" id="event_manager_export_xls_events" name="event_manager_export_xls_events[]" multiple="multiple">
					 			<?php
					 			foreach($events as $key => $event){
						 			echo '<option value="'.esc_attr($event->ID).'" >'. $event->post_title . '</option>';	
								} ?>
					 		</select>
							<input type="hidden" name="event_manager_custom_export_xls_events" id="event_manager_custom_export_xls_events" />
						</div>
					</fieldset>
				</div>
				
				<div class="wpem-form-group">

				 	<fieldset class="wpem-form-group fieldset-event_fields">
						<label for="event_title"><?php _e('Event Fields','wp-event-manager-export');?> <span class="require-field">*</span></label>
						<div class="field">
							<select class="event-manager-select-chosen" id="event_manager_export_xls_event_fields" name="event_manager_export_xls_event_fields[]" multiple="multiple" required="true">
					 			<?php
					 			foreach($event_fields as $form_guoups => $form_fields){
									foreach ($form_fields as $key => $field){
										if(in_array($key, $default_event_fields)){
						 					echo '<option selected value="'.esc_attr($key).'" >'. $field['label'] . '</option>';	
						 				}else{
						 					echo '<option value="'.esc_attr($key).'" >'. $field['label'] . '</option>';
						 				}
									}			
								} ?>
					 			<option selected value="view_count" ><?php _e('View Count','wp-event-manager-export');?></label></option>';
					 		</select>
							<input type="hidden" name="event_manager_custom_xls_export_fields" id="event_manager_custom_xls_export_fields" />
							<input type="hidden" name="export_file_type" id="export_file_type" value="xls" />
						</div>
					</fieldset>
				</div>

			 	<div class="wpem-form-footer">
					<button type="submit" id ="download_events_custom" name="download_events_custom" value="<?php echo get_current_user_id();?>" class="wpem-theme-button">
						<i class="wpem-icon-download3"></i> 
						<?php _e('Download Custom Xls', 'wp-event-manager-export'); ?>
					</button>
				</div>
			</form>
		</div>
	</div>
	<a href="javascript:void(0);"><div class="wpem-modal-overlay"></div></a>	
</div>