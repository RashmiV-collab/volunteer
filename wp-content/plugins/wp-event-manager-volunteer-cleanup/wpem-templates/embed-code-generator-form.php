<form class="event-manager-form wpem-form-wrapper ">
	<fieldset class="wpem-form-group wpem-location-field">
		<label for="widget_location"><?php _e( 'Location', 'wp-event-manager-embeddable-event-widget' ); ?></label>
		<div class="field">
			<input type="text" id="widget_location" class="input-text" placeholder="<?php _e( 'Optionally choose a location to search', 'wp-event-manager-embeddable-event-widget' ); ?>" />
			<p style="color:red;font-size:12px"><?php _e( '(Please enter a ZIP code or address. If you enter a distance in miles below, it will list nearby events near this location. Otherwise, it will provide events with a similar location.)', 'wp-event-manager-embeddable-event-widget' ); ?></p>
		</div>
	</fieldset>
	<fieldset class="wpem-form-group wpem-custom-field">
		<label for="widget_event_distance"><?php _e( 'Distance(Miles)', 'wp-event-manager-embeddable-event-widget' ); ?></label>
		<div class="field">
			<select data-placeholder="<?php _e( 'Distance', 'wp-event-manager-embeddable-event-widget' ); ?>" id="widget_distance"  class="event-manager-chosen-select">
				<option value="">Within</option>
				<option value="5">5</option>
				<option value="10">10</option>
				<option value="15">15</option>
				<option value="25">25</option>
				<option value="50">50</option>
				<option value="100">100</option>
			</select>
		</div>
	</fieldset>
	<fieldset class="wpem-form-group wpem-display-count-field">
		<label for="widget_per_page"><?php _e( 'How many events do you want to display?', 'wp-event-manager-embeddable-event-widget' ); ?></label>
		<div class="field">
			<input type="text" id="widget_per_page" class="input-text" value="5" />
		</div>
	</fieldset>
	<fieldset class="wpem-form-group wpem-pagination-field">
		<label for="widget_pagination"><?php _e( 'Display a "Show More Cleanups" Button', 'wp-event-manager-embeddable-event-widget' ); ?></label>
		<div class="field">
			<input type="checkbox" id="widget_pagination" class="input-checkbox" />
		</div>
	</fieldset>
	<?php if ( get_option( 'event_manager_enable_categories' ) && wp_count_terms( 'event_listing_category' ) > 0 ) : ?>
		<fieldset class="wpem-form-group wpem-categories-field">
			<label for="widget_categories"><?php _e( 'Categories', 'wp-event-manager-embeddable-event-widget' ); ?></label>
			<div class="field">
				<?php
					wp_enqueue_script( 'wp-event-manager-term-multiselect' );

					event_manager_dropdown_selection( array(
						'taxonomy'     => 'event_listing_category',
						'hierarchical' => 1,
						'name'         => 'widget_categories',
						'orderby'      => 'name',
						'hide_empty'   => false,
						'placeholder'  => __( 'Any category', 'wp-event-manager-embeddable-event-widget' )
					) );
				?>
			</div>
		</fieldset>
	<?php endif; ?>
	<fieldset class="wpem-form-group wpem-keyword-field">
		<label for="widget_keyword"><?php _e( 'Keyword', 'wp-event-manager-embeddable-event-widget' ); ?></label>
		<div class="field">
			<input type="text" id="widget_keyword" class="input-text" placeholder="<?php _e( 'Optionally choose a keyword to search', 'wp-event-manager-embeddable-event-widget' ); ?>" />
		</div>
	</fieldset>
	<!--<fieldset class="wpem-form-group wpem-event-type-field">
		<label for="widget_event_type"><?php _e( 'Event Type', 'wp-event-manager-embeddable-event-widget' ); ?></label>
		<div class="field">
			<select data-placeholder="<?php _e( 'Any event type', 'wp-event-manager-embeddable-event-widget' ); ?>" id="widget_event_type" multiple="multiple" class="event-manager-chosen-select">
				<?php
					$terms = get_event_listing_types();
					foreach ( $terms as $term ) {
						echo '<option value="' . esc_attr( $term->slug ) . '">' . esc_html( $term->name ) . '</option>';
					}
				?>
			</select>
		</div>
		<input type="hidden" id="widget_user_id" value="<?php echo get_current_user_id(); ?>" />
	</fieldset>-->
	<fieldset class="wpem-form-group wpem-event-hosting">
		<label for="widget_event_hosting"><?php _e( 'Show only cleanups I am hosting?', 'wp-event-manager-embeddable-event-widget' ); ?></label>
		<div class="field">
			<input type="checkbox" id="widget_event_hosting" class="input-checkbox" />
		</div>
	</fieldset>
	<p>
		<input type="button" class="wpem-theme-button" id="widget-get-code" value="<?php _e( 'Get Widget Embed Code', 'wp-event-manager-embeddable-event-widget' ); ?>" />
	</p>
	<div id="widget-code-wrapper">
		<div id="widget-code-preview">
			<h2><?php _e( 'Preview', 'wp-event-manager-embeddable-event-widget' ); ?></h2>
		</div>
		<div id="widget-code-content">
			<h2><?php _e( 'Code', 'wp-event-manager-embeddable-event-widget' ); ?></h2>
			<div class="wpem-code-preview-block">
				<textarea readonly="readonly" id="widget-code" rows="10"></textarea>
				<button type="button" class="wpem-copy-text-button" onclick="myFunction()"><i class="wpem-icon-copy"></i> <span id="copy_text_btn"><?php _e( 'Copy to clipboard', 'wp-event-manager-embeddable-event-widget' ); ?></span></button>
			</div>			
		</div>		
	</div>
</form>


<script>
function myFunction() {
	/* Get the text field */
	var copyText = document.getElementById("widget-code");

	/* Select the text field */
	copyText.select();
	copyText.setSelectionRange(0, 99999); /*For mobile devices*/

	/* Copy the text inside the text field */
	document.execCommand("copy");

	document.getElementById("copy_text_btn").innerHTML = "<?php _e( 'Copied!', 'wp-event-manager-embeddable-event-widget' ); ?>";
}
</script>