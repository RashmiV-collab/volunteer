<?php wp_enqueue_script('wp-event-manager-multiselect'); 

wp_register_script( 'chosen', EVENT_MANAGER_PLUGIN_URL . '/assets/js/jquery-chosen/chosen.jquery.min.js', array( 'jquery' ), '1.1.0', true );
wp_register_script( 'wp-event-manager-multiselect', EVENT_MANAGER_PLUGIN_URL . '/assets/js/multiselect.min.js', array( 'jquery', 'chosen' ), EVENT_MANAGER_VERSION, true );
wp_enqueue_style( 'chosen', EVENT_MANAGER_PLUGIN_URL . '/assets/css/chosen.css' ); ?>

<?php
if($key == 'attendee_information_fields'){
    $line = array();
    echo '<ul>';
    foreach ($field['options'] as $option_key => $value) :       
        ?>
        <input type="hidden" name="attendee_information_fields[]" id="<?php echo esc_attr($option_key); ?>" value="<?php echo esc_attr($option_key); ?>"> 
        <?php
       echo '<li>'.$value.'</li>';
    endforeach; 
        echo '</ul>';
}else{
    ?>
    <select multiple="multiple" name="<?php echo esc_attr(isset($field['name']) ? $field['name'] : $key); ?>[]" id="<?php echo esc_attr($key); ?>" class="event-manager-multiselect" data-no_results_text="<?php _e('No results match', 'wp-event-manager'); ?>" attribute="<?php echo esc_attr(isset($field['attribute']) ? $field['attribute'] : ''); ?>" data-multiple_text="<?php _e('Select Some Options', 'wp-event-manager'); ?>">
	<?php foreach ($field['options'] as $option_key => $value) : ?>
		<option value="<?php echo esc_attr($option_key); ?>" <?php if (!empty($field['value']) && is_array($field['value'])) selected(in_array($option_key, $field['value']), true); ?>><?php echo esc_html($value); ?></option>
	<?php endforeach; ?>
</select>
    <?php
}

?>
<?php if (!empty($field['description'])) : ?>
	<small class="description">
		<?php printf ($field['description']); ?>
	</small>
<?php endif; ?>