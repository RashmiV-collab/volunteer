<!--Save changes modal-->
<div class="remodal export-csv-modal" data-remodal-id="export-csv-modal" data-remodal-options="closeOnOutsideClick: false, hashTracking: false">

	<div class="modal-content">
		<?php
		$is_not_supported = apply_filters('vg_sheet_editor/export/is_not_supported', null, $post_type);
		if (!is_null($is_not_supported)) {
			$message = ( is_string($is_not_supported)) ? $is_not_supported : __('The export feature is not compatible with your website. Make sure WordPress and all the plugins and themes are up to date.');
			?>

			<h3><?php _e('Export to CSV', VGSE()->textname); ?></h3>
			<p><?php echo wp_kses_post($message); ?></p>
			<button data-remodal-action="confirm" class="remodal-cancel"><?php _e('Cancel', VGSE()->textname); ?></button>

		<?php } else {
			?>
			<?php do_action('vg_sheet_editor/export/before_form', $post_type); ?>
			<form class="export-csv-form vgse-modal-form " action="<?php echo admin_url('admin-ajax.php'); ?>" method="POST">
				<h3><?php _e('Export to CSV', VGSE()->textname); ?></h3>

				<div class="fields-to-export">
					<div class="field-wrap">
						<label><?php _e('What columns do you want to export?', VGSE()->textname); ?></label>
						<select name="export_columns[]" required data-placeholder="<?php _e('Select column...', VGSE()->textname); ?>" class="select2 export-columns" multiple>
							<option></option>
							<?php
							$this->render_wp_fields_export_options($post_type);
							?>
						</select>
						<br/>
						<button class="select-active button"><?php _e('Select active columns', VGSE()->textname); ?></button> 
						<button class="select-all button"><?php _e('Select all', VGSE()->textname); ?></button> 
						<button class="unselect-all button"><?php _e('Unselect  all', VGSE()->textname); ?></button>
					</div>

					<?php if (empty(VGSE()->options['enable_simple_mode'])) { ?>
						<div class="field-wrap">
							<label><?php _e('Which rows do you want to export?', VGSE()->textname); ?></label>
							<select class="wpse-select-rows-options">
								<option value="current_search"><?php _e('All the rows from my current search', VGSE()->textname); ?></option>
								<option value="selected"><?php _e('Rows that I selected manually with the checkbox', VGSE()->textname); ?></option>
							</select>
						</div>

						<div class="field-wrap">
							<label class="excel-compatibility-container"><?php _e('What app will you use to edit this file? (optional)', VGSE()->textname); ?><br>
								<select name="add_excel_separator_flag">
									<option value=""><?php _e('--', VGSE()->textname); ?></option>
									<option value=""><?php _e('Microsoft Excel (Office 365)', VGSE()->textname); ?></option>
									<option value="yes"><?php _e('Other versions of Microsoft Excel', VGSE()->textname); ?></option>
									<option value=""><?php _e('Google Sheets', VGSE()->textname); ?></option>
									<option value=""><?php _e('Other', VGSE()->textname); ?></option>
								</select>
							</label>
						</div>
					<?php } ?>
					<?php if (current_user_can('manage_options')) { ?>

						<div class="field-wrap">
							<label class="save-for-later-container"><?php _e('Name of this export (optional)', VGSE()->textname); ?> <a href="#" class="tipso tipso_style" data-tipso="<?php _e('We will save the current search query and export settings, and you can execute this export with one click in the future using the dropdown in the export menu', VGSE()->textname); ?>">( ? )</a></label>
							<input type="text"  name="save_for_later_name">
						</div>
					<?php } ?>
				</div>

				<?php do_action('vg_sheet_editor/export/before_response', $post_type); ?>
				<div class="export-response">

				</div>

				<p class="export-actions"><a href="#" class="button pause-export button-secondary" data-action="pause"><i class="fa fa-pause"></i> <?php _e('Pause', VGSE()->textname); ?></a></p>

				<input type="hidden" value="vgse_export_csv" name="action">
				<input type="hidden" value=" <?php echo $nonce; ?>" name="nonce">
				<input type="hidden" value="<?php echo $post_type; ?>" name="post_type">
				<button type="submit" class="remodal-confirm vgse-trigger-export"><?php _e('Start new export', VGSE()->textname); ?></button>
				<button data-remodal-action="confirm" class="remodal-cancel"><?php _e('Cancel', VGSE()->textname); ?></button>

			</form>
		<?php } ?>
	</div>								
</div>