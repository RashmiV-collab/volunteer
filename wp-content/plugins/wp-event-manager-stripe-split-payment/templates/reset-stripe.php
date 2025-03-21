<?php $current_user = wp_get_current_user(); ?>
<h2 id="reset-stripe" class="reset_stripe_title" >
	<?php echo __( 'Reset All Card from this website', 'wp-event-manager-stripe-split-payment' ); ?>
</h2>
<table class="shop_table" id="reset-stripe-table">	
	<tbody>		
		<tr>
			<td colspan=2> <?php _e( 'Reset your saved card data','wp-event-manager-stripe-split-payment' ); ?> </td>			
			<td>					
					<input type="submit" id="reset_card_data_stripe_id" data-element="<?php echo $current_user->ID; ?>"  class="button" value="<?php _e( 'Reset Card', 'wp-event-manager-stripe-split-payment' ); ?>">				
			</td>
		</tr>		
	</tbody>
</table>
