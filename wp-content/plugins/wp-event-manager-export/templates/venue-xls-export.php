<div id="wpem-export-modal-popup-venue-xls" class="wpem-modal" role="dialog" aria-labelledby="<?php _e('Venue XLS Export','wp-event-manager-export');?>">
	<div class="wpem-modal-content-wrapper">
		<div class="wpem-modal-header">
			<div class="wpem-modal-header-title">
				<h3 class="wpem-modal-header-title-text"><?php _e('Venue XLS Export','wp-event-manager-export');?></h3>
			</div>
			<div class="wpem-modal-header-close">
				<a href="javascript:void(0);" class="wpem-modal-close">x</a>
			</div>
		</div>

		<div class="wpem-modal-content">
			<a id="venue_xls_default" href="?venue_xls_default&user_id=<?php echo get_current_user_id(); ?>&file_type=xls" class="wpem-theme-button">
				<span>
					<i class="wpem-icon-download3"></i> 
					<?php _e('Default', 'wp-event-manager-export'); ?>
				</span>
			</a>
	 		<a id="custom_venue_xls" href="#" class="wpem-theme-button">
				<span>
					<?php _e('Custom', 'wp-event-manager-export'); ?> 
					<i class="wpem-icon-arrow-down "></i>
				</span>
			</a>

			<form action="" method="post" class="wpem-form-wrapper" id="custom_venue_xls_form" style="display:none;">

				<div class="wpem-form-group">
				 	<fieldset class="wpem-form-group fieldset-venue_fields">
						<label><?php _e('Venue Fields','wp-event-manager-export');?><span class="require-field">*</span></label>
						<div class="field">
							<select class="event-manager-select-chosen" id="event_manager_export_xls_venue_fields" name="event_manager_export_xls_venue_fields[]" multiple="multiple" required="true">
					 			<?php
					 			foreach($venue_fields as $form_guoups => $form_fields){
									foreach ($form_fields as $key => $field){
										if(in_array($key, $default_venue_fields)){
						 					echo '<option selected value="'.esc_attr($key).'" >'. $field['label'] . '</option>';	
						 				}else{
						 					echo '<option value="'.esc_attr($key).'" >'. $field['label'] . '</option>';
						 				}
									}			
								} ?>
					 		</select>
							 <input type="hidden" name="event_manager_custom_export_xls_venue_fields" id="event_manager_custom_export_xls_venue_fields" />
							 <input type="hidden" name="export_file_type" id="export_file_type" value="xls" />
						</div>
					</fieldset>
				</div>

			 	<div class="wpem-form-footer">
					<button type="submit" id ="download_venues_custom" name="download_venues_custom" value="<?php echo get_current_user_id();?>" class="wpem-theme-button">
						<i class="wpem-icon-download3"></i> 
						<?php _e('Download Custom Xls', 'wp-event-manager-export'); ?>
					</button>
				</div>
			</form>
		</div>
	</div>
	<a href="javascript:void(0);"><div class="wpem-modal-overlay"></div></a>	
</div>