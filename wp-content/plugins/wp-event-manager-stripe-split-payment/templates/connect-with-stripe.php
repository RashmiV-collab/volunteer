<?php if( $seller_connected == 0 ){
$image_url = WPEM_STRIPE_SPLIT_PAYMENT_PLUGIN_URL. '/assets/images/blue-on-light.png';
?>
<div class="wpem-stripe-shortcode-container">
  <div class="wpem-row wpem-stripe-shortcode-wrapper">
    <div class="wpem-col-md-3 wpem-col-sm-12">
      <div class="wpem-stripe-img">
        <img src="<?php echo WPEM_STRIPE_SPLIT_PAYMENT_PLUGIN_URL; ?>/assets/images/stripe-logo.png" alt="Connect with stripe">
      </div>
    </div>
    <div class="wpem-col-md-6 wpem-col-sm-12">
      <div class="wpem-stripe-info">
        <h3 class="wpem-heading-text"><?php _e( 'Use Stripe Payments', 'wp-event-manager-stripe-split-payment' );?></h3> 
        <p class="wpem-stripe-desc"><?php _e( 'Connect your Stripe account to take payments through Stripe', 'wp-event-manager-stripe-split-payment' );?></p>
      </div>
    </div>
    <div class="wpem-col-md-3 wpem-col-sm-12">
      <div class="wpem-stripe-button">
        <a href="<?php echo $stripe_connect_url; ?>" class="wpem-theme-button"><span><?php _e( 'Connect', 'wp-event-manager-stripe-split-payment' );?></span></a>
      </div>
    </div>
  </div>
</div>

<?php } else if( $seller_connected == 1 ) { ?>
<div class="wpem-stripe-shortcode-container">
  <div class="wpem-row wpem-stripe-shortcode-wrapper">
    <div class="wpem-col-md-3 wpem-col-sm-12">
      <div class="wpem-stripe-img">
        <img src="<?php echo WPEM_STRIPE_SPLIT_PAYMENT_PLUGIN_URL; ?>/assets/images/stripe-logo.png" alt="Connect with stripe">
      </div>
    </div>
    <div class="wpem-col-md-6 wpem-col-sm-12">
      <div class="wpem-stripe-info">
        <h3 class="wpem-heading-text"><?php _e( 'Use Stripe Payments', 'wp-event-manager-stripe-split-payment' );?></h3> 
        <p class="wpem-stripe-desc"><?php _e( 'Connect your Stripe account to take payments through Stripe', 'wp-event-manager-stripe-split-payment' );?></p>
      </div>
    </div>
    <div class="wpem-col-md-3 wpem-col-sm-12">
      <div class="wpem-stripe-button">
        <form action="" method="POST">
          <button type="submit" class="wpem-theme-button" id="disconnect_stripe" name="disconnect_stripe">
            <?php _e( 'Disconnect', 'wp-event-manager-stripe-split-payment');?>
          </button>
        </form>
      </div>
    </div>
  </div>
  </div>
<?php } ?>

