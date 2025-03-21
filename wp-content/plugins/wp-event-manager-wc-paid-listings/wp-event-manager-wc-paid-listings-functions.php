<?php
/**
 * Output a select input box.
 *
 * @param array $field Data about the field to render.
 */
function wc_wp_select_multiple( $field ) {
	global $thepostid, $post;

	$thepostid = empty( $thepostid ) ? $post->ID : $thepostid;
	$field     = wp_parse_args(
		$field, array(
			'class'             => 'select short',
			'style'             => '',
			'wrapper_class'     => '',
			'value'             => get_post_meta( $thepostid, $field['id'], true ),
			'name'              => $field['id'],
			'desc_tip'          => false,
			'custom_attributes' => array(),
		)
	);

	$wrapper_attributes = array(
		'class' => $field['wrapper_class'] . " form-field {$field['id']}_field",
	);

	$label_attributes = array(
		'for' => $field['id'],
	);

	$field_attributes          = (array) $field['custom_attributes'];
	$field_attributes['style'] = $field['style'];
	$field_attributes['id']    = $field['id'];
	$field_attributes['name']  = $field['name'];
	$field_attributes['class'] = $field['class'];

	if(isset($field['value']) && empty($field['value'])){
		$field['value'] = [];
	}

	$tooltip     = ! empty( $field['description'] ) && false !== $field['desc_tip'] ? $field['description'] : '';
	$description = ! empty( $field['description'] ) && false === $field['desc_tip'] ? $field['description'] : '';?>

	<p <?php echo wc_implode_html_attributes( $wrapper_attributes ); // WPCS: XSS ok. ?>>
		<label <?php echo wc_implode_html_attributes( $label_attributes ); // WPCS: XSS ok. ?>><?php echo wp_kses_post( $field['label'] ); ?></label>
		<?php if ( $tooltip ) :
			echo wc_help_tip( $tooltip ); // WPCS: XSS ok. 
		endif; ?>
		<select multiple="multiple" <?php echo wc_implode_html_attributes( $field_attributes ); // WPCS: XSS ok. ?>>
			<?php
			foreach ( $field['options'] as $key => $value ) {
				$selected = in_array( $key, $field['value'] ) ? "selected" : "";
				echo '<option value="' . esc_attr( $key ) . '"' . $selected . '>' . esc_html( $value ) . '</option>';
			} ?>
		</select>
		<?php if ( $description ) : ?>
			<span class="description"><?php echo wp_kses_post( $description ); ?></span>
		<?php endif; ?>
	</p>
	<script type="text/javascript">
		jQuery(function(){
			jQuery("#<?php echo $field['id']; ?>").select2();
		});
	</script>
	<?php
}