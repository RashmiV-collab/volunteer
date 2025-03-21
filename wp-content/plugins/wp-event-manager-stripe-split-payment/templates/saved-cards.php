<h2 id="saved-cards" style="margin-top:40px;"><?php _e( 'Saved cards', 'wp-event-manager-stripe-split-payment' ); ?></h2>
<table class="shop_table">
	<thead>
		<tr>
			<th><?php esc_html_e( 'Card', 'wp-event-manager-stripe-split-payment' ); ?></th>
			<th><?php esc_html_e( 'Expires', 'wp-event-manager-stripe-split-payment' ); ?></th>
			<th></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( $cards as $card ) :
			if ( 'card' !== $card->object ) {
				continue;
			}

			$is_default_card = $card->id === $default_card ? true : false;
		?>
		<tr>
            <td><?php printf( __( '%s card ending in %s', 'wp-event-manager-stripe-split-payment' ), $card->brand, $card->last4 ); ?>
            	<?php if ( $is_default_card ) echo '<br />' . __( '(Default)', 'wp-event-manager-stripe-split-payment' ); ?>
            </td>
            <td><?php printf( __( 'Expires %s/%s', 'wp-event-manager-stripe-split-payment' ), $card->exp_month, $card->exp_year ); ?></td>
			<td>
                <form action="" method="POST">
                    <?php wp_nonce_field ( 'stripe_del_card' ); ?>
                    <input type="hidden" name="stripe_delete_card" value="<?php echo esc_attr( $card->id ); ?>">
                    <input type="submit" class="button" value="<?php esc_attr_e( 'Delete card', 'wp-event-manager-stripe-split-payment' ); ?>">
                </form>

                <?php if ( ! $is_default_card ) { ?>
	                <form action="" method="POST" style="margin-top:10px;">
	                    <?php wp_nonce_field ( 'stripe_default_card' ); ?>
	                    <input type="hidden" name="stripe_default_card" value="<?php echo esc_attr( $card->id ); ?>">
	                    <input type="submit" class="button" value="<?php esc_attr_e( 'Make Default', 'wp-event-manager-stripe-split-payment' ); ?>">
	                </form>
                <?php } ?>
			</td>
		</tr>
		<?php endforeach; ?>
	</tbody>
</table>
