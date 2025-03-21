<?php
$instance = vgse_custom_tables();
?>
<p><?php _e('Thank you for installing our plugin.', $instance->textname); ?></p>

<?php
$steps = array();

$sheets = apply_filters('vg_sheet_editor/custom_tables/welcome_sheets', $GLOBALS['wpse_custom_tables_sheet']->get_prop('post_type'));
$sheets_buttons = '';

$all = apply_filters('vg_sheet_editor/custom_tables/welcome_sheets_all', array(
	'post_types' => $GLOBALS['wpse_custom_tables_sheet']->get_prop('post_type'),
	'labels' => $GLOBALS['wpse_custom_tables_sheet']->get_prop('post_type_label')
		));
if (is_array($all['post_types'])) {
	foreach ($all['post_types'] as $index => $sheet) {
		if (in_array($sheet, $sheets)) {
			$sheets_buttons .= '<br><a href="' . esc_url(VGSE()->helpers->get_editor_url($sheet)) . '" class="button">Edit ' . $all['labels'][$index] . '</a>';
		} else {
			$sheets_buttons .= '<br>' . $all['labels'][$index] . '. <a href="' . esc_url(VGSE()->get_buy_link('sheet-locked-column-' . $sheet)) . '" >Premium</a>';
		}
	}
}
if (empty($sheets_buttons)) {
	$steps['open_editor'] = '<p>' . __('We could not find any custom tables in your database.', $instance->textname) . '</p>';
} else {
	$steps['open_editor'] = '<p>' . sprintf(__('We have generated spreadsheets for the custom tables below. Just click on the table you want to edit:  %s', $instance->textname), $sheets_buttons) . '</p>';
}

include VGSE_DIR . '/views/free-extensions-for-welcome.php';
$steps['free_extensions'] = $free_extensions_html;

$steps = apply_filters('vg_sheet_editor/custom_tables/welcome_steps', $steps);

if (!empty($steps)) {
	echo '<ol class="steps">';
	foreach ($steps as $key => $step_content) {
		if (empty($step_content)) {
			continue;
		}
		?>
		<li><?php echo $step_content; ?></li>		
		<?php
	}

	echo '</ol>';
}	