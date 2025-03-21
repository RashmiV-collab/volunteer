<?php

if (!defined('ABSPATH'))
{
    exit;
}

class WPEM_Stripe_Split_Payment_Gateway extends WC_Payment_Gateway {

    /**
     * WP_Event_Manager_Sell_Tickets_WC_Settings_Tab_Fees  the class and hooks required actions & filters.
     *
     */
    public function __construct()
    {
        $this->id                   = 'wpem_stripe';  //at db table, wp_options, new option_name entery will save 'woocommerce_wpem_stripe_settings' and inside this array all settings will save.
        $this->method_title         = __('WPEM Stripe Split Payment', 'wp-event-manager-stripe-split-payment');
        $this->method_description   = __('WPEM Stripe Split Payment works by adding credit card fields on the checkout and then sending the details to Stripe for verification.', 'wp-event-manager-stripe-split-payment');
        $this->icon                 = WPEM_STRIPE_SPLIT_PAYMENT_PLUGIN_URL . '/assets/images/stripe.png';
        $this->has_fields           = true;
        $this->view_transaction_url = 'https://dashboard.stripe.com/payments/%s';
        $this->supports             = array(
            'products',
            'refunds',
            'subscriptions',
            'subscription_cancellation',
            'subscription_reactivation',
            'subscription_suspension',
            'subscription_amount_changes',
            'subscription_payment_method_change', // Subs 1.n compatibility
            'subscription_payment_method_change_customer',
            'subscription_payment_method_change_admin',
            'subscription_date_changes',
            'multiple_subscriptions',
            'pre-orders',
            'tokenization'
        );


        //Load the form fields
        $this->init_form_fields();

        //Load the settings of the woocommerce
        $this->init_settings();

        //A basic set of settings for your gateway would consist of enabled, title and description:
        //enable/disabled
        $this->enabled     = $this->get_option('enabled');
        //Title & Description will show at checkout page
        $this->title       = $this->get_option('title');
        $this->description = $this->get_option('description');


        $this->stripe_testmode = $this->get_option('stripe_testmode') === "yes";
        //if enabled, stripe connect
        $this->stripe_connect  = $this->get_option('stripe_connect') === "yes";

        //API Details
        $this->stripe_test_clientid       = $this->get_option('stripe_test_clientid');
        $this->stripe_test_publishablekey = $this->get_option('stripe_test_publishablekey');
        $this->stripe_test_secretkey      = $this->get_option('stripe_test_secretkey');
        $this->stripe_live_clientid       = $this->get_option('stripe_live_clientid');
        $this->stripe_live_publishablekey = $this->get_option('stripe_live_publishablekey');
        $this->stripe_live_secretkey      = $this->get_option('stripe_live_secretkey');

        //Email Transaction Receipt
        $this->stripe_receipt_email    = $this->get_option('stripe_receipt_email');
        
        //if enabled, users will be able to pay with a saved card during checkout. Card details are saved on Stripe servers, not on your store.
        $this->stripe_saved_cards      = $this->get_option('stripe_saved_cards') === "yes";
        
        //Enable Shipping Address, Require the user to enter their shipping address during checkout.
        $this->stripe_shipping_address = $this->get_option('stripe_shipping_address');

        $this->stripe_logging = 'yes' === $this->get_option('stripe_logging');

        if ($this->stripe_testmode == 'yes')
        {
            $this->description .= ' ' . sprintf(__('TEST MODE ENABLED. In test mode, you can use the card number 4242424242424242 with any CVC and a valid expiration date or check the documentation "<a href="%s" target="_blank">Testing Stripe</a>" for more card numbers.', 'wp-event-manager-stripe-split-payment'), 'https://stripe.com/docs/testing');
            $this->description = trim($this->description);

            //overrite for test mode , payment url
            $this->view_transaction_url = 'https://dashboard.stripe.com/test/payments/%s';
        }

        if ($this->stripe_testmode == "yes" || $this->stripe_testmode == true)
        {
            WPEM_Stripe_Split_Payment_API::set_secret_key($this->stripe_test_secretkey);
            WPEM_Stripe_Split_Payment_API::set_client_id($this->stripe_test_clientid);
        }
        else
        {
            WPEM_Stripe_Split_Payment_API::set_secret_key($this->stripe_live_secretkey);
            WPEM_Stripe_Split_Payment_API::set_client_id($this->stripe_live_clientid);
        }

        //hook
        add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));
        //add_action('admin_notices', array($this, 'stripe_admin_notices'));


        // Save settings
        if (is_admin())
        {
            // Versions over 2.0
            // Save our administration options. Since we are not going to be doing anything special
            // we have not defined 'process_admin_options' in this class so the method in the parent
            // class will be used instead
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        }

        add_action('woocommerce_order_status_on-hold_to_processing', array($this, 'capture_payment'));
        add_action('woocommerce_order_status_on-hold_to_completed', array($this, 'capture_payment'));
        add_action('woocommerce_order_status_on-hold_to_cancelled', array($this, 'cancel_payment'));
        add_action('woocommerce_order_status_on-hold_to_refunded', array($this, 'cancel_payment'));
        add_filter('woocommerce_get_customer_payment_tokens', array($this, 'woocommerce_get_customer_payment_tokens'), 10, 3);
        add_action('woocommerce_payment_token_deleted', array($this, 'woocommerce_payment_token_deleted'), 10, 2);
        add_action('woocommerce_payment_token_set_default', array($this, 'woocommerce_payment_token_set_default'));
        add_filter('woocommerce_payment_complete_order_status', array($this, 'woocommerce_autocomplete_paid_orders'), 10, 2); //10,2 imp

        add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
    }

    /**
     * Initialise Stripe Gateway Settings Form Fields
     */
    public function init_form_fields()
    {
        $this->form_fields = apply_filters('wpem_stripe_split_payment_settings', array(
            'confinguration_section_title' => array(
                'title'       => __('Configure the stripe settings', 'wp-event-manager-stripe-split-payment'),
                'type'        => 'title',
                'description' => __('Please configure the requested settings.', 'wp-event-manager-stripe-split-payment')
            ),
            'enabled'                      => array(
                'title'       => __('Enable/Disable', 'wp-event-manager-stripe-split-payment'),
                'label'       => __('Enable Stripe', 'wp-event-manager-stripe-split-payment'),
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'no'
            ),
            'title'                        => array(
                'title'       => __('Stripe Gateway Title', 'wp-event-manager-stripe-split-payment'),
                'type'        => 'text',
                'description' => __('Title of Stripe Gateway will shown on checkout page.', 'wp-event-manager-stripe-split-payment'),
                'default'     => __('Credit card (Stripe)', 'wp-event-manager-stripe-split-payment')
            ),
            
            'display_section_title'        => array(
                'title'       => __('Enter your display details settings', 'wp-event-manager-stripe-split-payment'),
                'type'        => 'title',
                'description' => __('Enter All requested information given below.', 'wp-event-manager-stripe-split-payment')
            ),
            'description'                  => array(
                'title'       => __('Description', 'wp-event-manager-stripe-split-payment'),
                'type'        => 'textarea',
                'description' => __('Description of Stripe Gateway on checkout page.', 'wp-event-manager-stripe-split-payment'),
                'default'     => __('Pay with your credit card via Stripe.', 'wp-event-manager-stripe-split-payment')
            ),
            'api_section_title'            => array(
                'title'       => __('Enter your API Details', 'wp-event-manager-stripe-split-payment'),
                'type'        => 'title',
                'description' => __('Enter All requested information given below.', 'wp-event-manager-stripe-split-payment')
            ),
            'stripe_testmode'              => array(
                'title'       => __('Test mode', 'wp-event-manager-stripe-split-payment'),
                'label'       => __('Enable Test Mode', 'wp-event-manager-stripe-split-payment'),
                'type'        => 'checkbox',
                'description' => __('This will enable the test mode of Stripe using test API keys.', 'wp-event-manager-stripe-split-payment'),
                'default'     => 'yes'
            ),
            'stripe_test_clientid'         => array(
                'title'       => __('Test Client ID', 'wp-event-manager-stripe-split-payment'),
                'type'        => 'text',
                'description' => sprintf( __( 'Get your Client ID from your <a href="%s" target="_blank">Stripe account</a>.', 'wp-event-manager-stripe-split-payment' ), 'https://dashboard.stripe.com/' ),
                'default'     => ''
            ),
            'stripe_test_publishablekey'   => array(
                'title'       => __('Test Publishable Key', 'wp-event-manager-stripe-split-payment'),
                'type'        => 'text',
                'description' => sprintf( __( 'Get your Publishable keys from your <a href="%s" target="_blank">Stripe account</a>.', 'wp-event-manager-stripe-split-payment' ), 'https://dashboard.stripe.com/' ),
                'default'     => ''
            ),
            'stripe_test_secretkey'        => array(
                'title'       => __('Test Secret Key', 'wp-event-manager-stripe-split-payment'),
                'type'        => 'text',
                'description' => sprintf( __( 'Get your Secret keys from your <a href="%s" target="_blank">Stripe account</a>.', 'wp-event-manager-stripe-split-payment' ), 'https://dashboard.stripe.com/' ),
                'default'     => ''
            ),            
            'stripe_live_clientid'         => array(
                'title'       => __('Live Client ID', 'wp-event-manager-stripe-split-payment'),
                'type'        => 'text',
                'description' => sprintf( __( 'Get your Client ID from your <a href="%s" target="_blank">Stripe account</a>.', 'wp-event-manager-stripe-split-payment' ), 'https://dashboard.stripe.com/' ),
                'default'     => ''
            ),
            'stripe_live_publishablekey'   => array(
                'title'       => __('Live Publishable Key', 'wp-event-manager-stripe-split-payment'),
                'type'        => 'text',
                'description' => sprintf( __( 'Get your Publishable keys from your <a href="%s" target="_blank">Stripe account</a>.', 'wp-event-manager-stripe-split-payment' ), 'https://dashboard.stripe.com/' ),
                'default'     => ''
            ),
            'stripe_live_secretkey'        => array(
                'title'       => __('Live Secret Key', 'wp-event-manager-stripe-split-payment'),
                'type'        => 'text',
                'description' => sprintf( __( 'Get your Secret keys from your <a href="%s" target="_blank">Stripe account</a>.', 'wp-event-manager-stripe-split-payment' ), 'https://dashboard.stripe.com/' ),
                'default'     => ''
            ),            
            'other_section_title'          => array(
                'title'       => __('Configure the other settings', 'wp-event-manager-stripe-split-payment'),
                'type'        => 'title',
                'description' => __('Please configure the requested settings.', 'wp-event-manager-stripe-split-payment')
            ),
            /*'stripe_saved_cards'           => array(
                'title'       => __('Saved cards', 'wp-event-manager-stripe-split-payment'),
                'label'       => __('Enable saved cards', 'wp-event-manager-stripe-split-payment'),
                'type'        => 'checkbox',
                'description' => __('If enabled, users will be able to pay with a saved card during checkout. Card details are saved on Stripe servers, not on your store.', 'wp-event-manager-stripe-split-payment'),
                'default'     => 'no'
            ),
            'stripe_receipt_email'         => array(
                'title'       => __('Enable stripe receipt email', 'wp-event-manager-stripe-split-payment'),
                'type'        => 'checkbox',
                'label'       => __('Enable receipt email from Stripe (Active If Checked)', 'wp-event-manager-stripe-split-payment'),
                'description' => __('If enabled, will send stripe receipt email to billing email in live mode only.', 'wp-event-manager-stripe-split-payment'),
                'desc_tip'    => false,
                'default'     => 'yes',
            ),*/
            'stripe_shipping_address'      => array(
                'title'       => __('Enable Shipping Address', 'wp-event-manager-stripe-split-payment'),
                'type'        => 'checkbox',
                'label'       => __('Enable sending shipping address to stripe (Active If Checked)', 'wp-event-manager-stripe-split-payment'),
                'description' => __('If enabled, will send shipping address to stripe.', 'wp-event-manager-stripe-split-payment'),
                'desc_tip'    => false,
                'default'     => 'no',
            ),
            'stripe_logging'               => array(
                'title'       => __('Logging', 'wp-event-manager-stripe-split-payment'),
                'label'       => __('Log debug messages', 'wp-event-manager-stripe-split-payment'),
                'type'        => 'checkbox',
                'description' => __('Save debug messages to the WooCommerce System Status log.', 'wp-event-manager-stripe-split-payment'),
                'default'     => 'no',
                'desc_tip'    => false,
            )
        ));
    }

    /**
     * Load Stripe Scripts.
     *    
     */
    public function payment_scripts()
    {
        //check it is woocommerce checkout page
        if (!is_checkout())
        {
            return;
        }

        wp_enqueue_script('stripe', 'https://js.stripe.com/v2/', '', '2.0', true);
        //wp_enqueue_script('stripe', 'https://js.stripe.com/v3/', '', '3.0', true);
        wp_enqueue_script('wpem_stripe', WPEM_STRIPE_SPLIT_PAYMENT_PLUGIN_URL . '/assets/js/stripe.min.js', array('jquery-payment', 'stripe', 'wc-credit-card-form'), WPEM_STRIPE_SPLIT_PAYMENT_VERSION, true);

        $stripe_params = array(
            'key'                  => ( $this->stripe_testmode == 'yes' || $this->stripe_testmode == true ) ? $this->stripe_test_publishablekey : $this->stripe_live_publishablekey,
            'i18n_terms'           => __('Please accept the terms and conditions first', 'wp-event-manager-stripe-split-payment'),
            'i18n_required_fields' => __('Please fill in required checkout fields first', 'wp-event-manager-stripe-split-payment'),
        );


        // If we're on the pay page we need to pass stripe.js the address of the order.
        if (is_checkout_pay_page() && isset($_GET['order']) && isset($_GET['order_id']))
        {
            $order_key = urldecode($_GET['order']);
            $order_id  = absint($_GET['order_id']);
            $order     = new WC_Order($order_id);

            if ($order->get_id() == $order_id && $order->order_key == $order_key)
            {
                $stripe_params['billing_first_name'] = $order->get_billing_first_name();
                $stripe_params['billing_last_name']  = $order->get_billing_last_name();
                $stripe_params['billing_address_1']  = $order->get_billing_address_1();
                $stripe_params['billing_address_2']  = $order->get_billing_address_2();
                $stripe_params['billing_state']      = $order->get_billing_state();
                $stripe_params['billing_city']       = $order->get_billing_city();
                $stripe_params['billing_postcode']   = $order->get_billing_postcode();
                $stripe_params['billing_country']    = $order->get_billing_country();
            }
        }
        wp_localize_script('wpem_stripe', 'wc_stripe_params', apply_filters('wc_stripe_params', $stripe_params));
    }


    /**
     * Load admin scripts.
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function admin_scripts() {
        if ( 'woocommerce_page_wc-settings' !== get_current_screen()->id ) {
            return;
        }

        wp_register_script( 'wpem-admin-stripe-settings', WPEM_STRIPE_SPLIT_PAYMENT_PLUGIN_URL . '/assets/js/admin-stripe-settings.js', array( 'jquery' ), WPEM_STRIPE_SPLIT_PAYMENT_VERSION, true);
        
        wp_enqueue_script( 'wpem-admin-stripe-settings');
}


    /*
     * Check if SSL is enabled, keys are added, then notify user
     */

    public function stripe_admin_notices()
    {

        if ('no' == $this->enabled)
        {
            return;
        }

        // Show message if enabled and FORCE SSL is disabled and WordpressHTTPS plugin is not detected   
        if (( function_exists('wc_site_is_https') && !wc_site_is_https() ) && ( 'no' === get_option('woocommerce_force_ssl_checkout') && !class_exists('WordPressHTTPS') ))
        //if( 'yes' != $this->stripe_testmode && ( 'no' == get_option( 'woocommerce_force_ssl_checkout' ) && ! class_exists( 'WordPressHTTPS' ) )  ) 
        {
            echo '<div class="error"><p>' . sprintf(__('Stripe is enabled, but the <a href="%s">force SSL option</a> is disabled; your checkout may not be secure! Please enable SSL and ensure your server has a valid SSL certificate - Stripe will only work in test mode.', 'wp-event-manager-stripe-split-payment'), admin_url('admin.php?page=wc-settings&tab=checkout')) . '</p></div>';
        }

        // Check required fields for test mode
        if ($this->stripe_testmode == 'yes' || $this->stripe_testmode == true)
        {
            if (!$this->stripe_test_secretkey)
            {
                echo '<div class="error"><p>' . sprintf(__('Stripe error: Please enter your test secret key <a href="%s">here</a>', 'wp-event-manager-stripe-split-payment'), admin_url('admin.php?page=wc-settings&tab=checkout')) . '</p></div>';
                return;
            }
            elseif (!$this->stripe_test_publishablekey)
            {
                echo '<div class="error"><p>' . sprintf(__('Stripe error: Please enter your test publishable key <a href="%s">here</a>', 'wp-event-manager-stripe-split-payment'), admin_url('admin.php?page=wc-settings&tab=checkout')) . '</p></div>';
                return;
            }
            elseif (!$this->stripe_test_clientid && $this->stripe_connect == true)
            {
                echo '<div class="error"><p>' . sprintf(__('Stripe error: Please enter your test client id <a href="%s">here</a>', 'wp-event-manager-stripe-split-payment'), admin_url('admin.php?page=wc-settings&tab=checkout')) . '</p></div>';
                return;
            }
            // Simple check for duplicate keys
            elseif ($this->stripe_test_secretkey == $this->stripe_test_publishablekey)
            {
                echo '<div class="error"><p>' . sprintf(__('Stripe error: Your test secret and publishable keys match. Please check.', 'wp-event-manager-stripe-split-payment'), admin_url('admin.php?page=wc-settings&tab=checkout')) . '</p></div>';
                return;
            }
        }
        // Check required fields for live mode
        else
        {
            if (!$this->stripe_live_secretkey)
            {
                echo '<div class="error"><p>' . sprintf(__('Stripe error: Please enter your live secret key <a href="%s">here</a>', 'wp-event-manager-stripe-split-payment'), admin_url('admin.php?page=wc-settings&tab=checkout')) . '</p></div>';
                return;
            }
            elseif (!$this->stripe_live_publishablekey)
            {
                echo '<div class="error"><p>' . sprintf(__('Stripe error: Please enter your live publishable key <a href="%s">here</a>', 'wp-event-manager-stripe-split-payment'), admin_url('admin.php?page=wc-settings&tab=checkout')) . '</p></div>';
                return;
            }
            elseif (!$this->stripe_live_clientid && $this->stripe_connect == true)
            {
                echo '<div class="error"><p>' . sprintf(__('Stripe error: Please enter your live client id <a href="%s">here</a>', 'wp-event-manager-stripe-split-payment'), admin_url('admin.php?page=wc-settings&tab=checkout')) . '</p></div>';
                return;
            }
            // Simple check for duplicate keys
            elseif ($this->stripe_live_secretkey == $this->stripe_live_publishablekey)
            {
                echo '<div class="error"><p>' . sprintf(__('Stripe error: Your live secret and publishable keys match. Please check.', 'wp-event-manager-stripe-split-payment'), admin_url('admin.php?page=wc-settings&tab=checkout')) . '</p></div>';
                return;
            }
        }
    }

    /**
     * get_icon function.
     *
     * @access public
     * @return string
     */
    public function get_icon()
    {

        $style = version_compare(WC()->version, '2.6', '>=') ? 'style="margin-left: 0.3em"' : '';
        $icon  = '';

        $icon .= '<img src="' . esc_url(WPEM_STRIPE_SPLIT_PAYMENT_PLUGIN_URL . '/assets/images/stripe.png') . '" alt="Stripe Gateway" ' . $style . '/>';

        return apply_filters('woocommerce_gateway_icon', $icon, $this->id);
    }

    /*
     * Get selected card logo image.
     */

    public function get_selected_card_logo_image($type)
    {
        $ext        = version_compare(WC()->version, '2.6', '>=') ? '.svg' : '.png';
        $image_type = strtolower($type);
        return WC_HTTPS::force_https_url(WPEM_STRIPE_SPLIT_PAYMENT_PLUGIN_URL . '/assets/images/' . $image_type . $ext);
    }

    /*
     * Get Description
     */

    public function get_description()
    {
        return apply_filters('woocommerce_gateway_description', wpautop(wptexturize(trim($this->stripe_description))), $this->id);
    }

    /*
     * Get client ip address, which can use inside metadata value.
     */

    public function get_client_ip()
    {
        $ipaddress = '';
        if (isset($_SERVER['HTTP_CLIENT_IP']))
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if (isset($_SERVER['HTTP_X_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        else if (isset($_SERVER['HTTP_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        else if (isset($_SERVER['HTTP_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        else if (isset($_SERVER['REMOTE_ADDR']))
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        else
            $ipaddress = 'UNKNOWN';
        return $ipaddress;
    }

    /**
     * Check if this gateway is enabled or available for use. If available then and then it will show at checkout page.
     */
    public function is_available()
    {

        if ($this->enabled == "yes")
        {

            if (!$this->stripe_testmode && is_checkout() && !is_ssl())
            {
                return false;
            }

            if (!in_array(get_woocommerce_currency(), apply_filters('stripe_woocommerce_supported_currencies', array('AED', 'ALL', 'ANG', 'ARS', 'AUD', 'AWG', 'BBD', 'BDT', 'BIF', 'BMD', 'BND', 'BOB', 'BRL', 'BSD', 'BWP', 'BZD', 'CAD', 'CHF', 'CLP', 'CNY', 'COP', 'CRC', 'CVE', 'CZK', 'DJF', 'DKK', 'DOP', 'DZD', 'EGP', 'ETB', 'EUR', 'FJD', 'FKP', 'GBP', 'GIP', 'GMD', 'GNF', 'GTQ', 'GYD', 'HKD', 'HNL', 'HRK', 'HTG', 'HUF', 'IDR', 'ILS', 'INR', 'ISK', 'JMD', 'JPY', 'KES', 'KHR', 'KMF', 'KRW', 'KYD', 'KZT', 'LAK', 'LBP', 'LKR', 'LRD', 'MAD', 'MDL', 'MNT', 'MOP', 'MRO', 'MUR', 'MVR', 'MWK', 'MXN', 'MYR', 'NAD', 'NGN', 'NIO', 'NOK', 'NPR', 'NZD', 'PAB', 'PKR', 'PLN', 'PYG', 'QAR', 'RUB', 'SAR', 'SBD', 'SCR', 'SEK', 'SGD', 'SHP', 'SLL', 'SOS', 'STD', 'SVC', 'SZL', 'THB', 'TOP', 'TTD', 'TWD', 'TZS', 'UAH', 'UGX', 'USD', 'UYU', 'UZS', 'VND', 'VUV', 'WST', 'XAF', 'XOF', 'XPF', 'YER', 'ZAR', 'AFN', 'AMD', 'AOA', 'AZN', 'BAM', 'BGN', 'CDF', 'GEL', 'KGS', 'LSL', 'MGA', 'MKD', 'MZN', 'RON', 'RSD', 'RWF', 'SRD', 'TJS', 'TRY', 'XCD', 'ZMW'))))
            {
                return false;
            }


            if ('yes' == $this->stripe_testmode || $this->stripe_testmode == true)
            {
                if (empty($this->stripe_test_publishablekey) || empty($this->stripe_test_secretkey))
                {
                    return false;
                }
            }
            else
            {
                if (empty($this->stripe_live_publishablekey) || empty($this->stripe_live_secretkey))
                {
                    return false;
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Payment form on checkout page
     */
    public function payment_fields()
    {
        if ($this->description)
        {
            $description = apply_filters('wc_stripe_description', wpautop(wp_kses_post($this->description)));
        }
        $stripe_customer = new WPEM_Stripe_Split_Payment_Customer(get_current_user_id());
        $cards           = $stripe_customer->get_cards();

        $user = wp_get_current_user();
        if (get_current_user_id())
        {
            $user_email = get_user_meta(get_current_user_id(), 'billing_email', true);
            $user_email = $user_email ? $user_email : $user->user_email;
        }
        else
        {
            $user_email = '';
        }

        
        $WC_Payment_Gateway_CC = new WC_Payment_Gateway_CC();
        $display_tokenization  = $this->supports('tokenization') && is_checkout() && $this->stripe_saved_cards && get_current_user_id();


        get_event_manager_template('payment-details.php', array('id'                    => esc_attr($this->id),
            'description'           => $description,
            'cards'                 => $cards,
            'stripe_saved_cards'    => $this->stripe_saved_cards,
            'display'               => 0,
            'email'                 => esc_attr($user_email),
            'amount'                => esc_attr($this->get_stripe_amount(WC()->cart->total)),
            'currency'              => esc_attr(strtolower(get_woocommerce_currency())),
            'label'                 => __('Confirm and Pay', 'wp-event-manager-stripe-split-payment'),
            'name'                  => sprintf(__('%s', 'wp-event-manager-stripe-split-payment'), get_bloginfo('name')),
            'WC_Payment_Gateway_CC' => $WC_Payment_Gateway_CC,
            'display_tokenization'  => $display_tokenization,
                ), 'wp-event-manager-stripe-split-payment', WPEM_STRIPE_SPLIT_PAYMENT_PLUGIN_DIR . '/templates/');
    }

    /*
     * This method will  use at payment-details.php template.
     *
     */

    public function field_name($name)
    {
        //return $this->supports('tokenization') ? '' : ' name="' . esc_attr($this->id . '-' . $name) . '" ';
        return ' name="' . esc_attr($this->id . '-' . $name) . '" ';
    }

    /**
     * Process the payment
     * Now for the most important part of the gateway � handling payment and processing the order. 
     * Process_payment also tells WC where to redirect the user, and this is done with a returned array.
     * As you can see, its job is to:
     * 
      -Get and update the order being processed
      -Reduce stock and empty the cart
      -Return success and redirect URL (in this case the thanks page)

      Cheque gives the order On-Hold status since the payment cannot be verified automatically.
      If, however, you are building a direct gateway, then you can complete the order here instead.
      Rather than using update_status when an order is paid, you should us payment_complete:
     * 
     *  source : card token or card
     */
    public function process_payment($order_id, $retry = true, $force_customer = false)
    {

        WPEM_Stripe_Split_Payment::log("process_payment...");

        try
        {
            $order = wc_get_order($order_id);


            //get product owner id , seller stripe account id
            $items = $order->get_items();
            if (!empty($items))
            {
                foreach ($items as $item)
                {

                    $post_author_id = get_post_field('post_author', $item['product_id']);
                    WPEM_Stripe_Split_Payment::log("post author:" . $post_author_id);

                    //get seller's stripe account id which belong to current ordered products
                    $stripe_user_id = get_user_meta($post_author_id, '_stripe_user_id', true);

                    WPEM_Stripe_Split_Payment::log("_stripe_user_id:" . $stripe_user_id);
                    WPEM_Stripe_Split_Payment_API::set_connected_stripe_account_id($stripe_user_id);

                    //get post parent id, can use in future if we need it
                    //$post_parent_id=get_post_meta($item['product_id'], 'event_id', true);  

                    WPEM_Stripe_Split_Payment_API::set_woocommerce_order_id($order_id);
                    break;
                }
            }

            $source = $this->get_source($order, $force_customer);

            if (empty($source->source) && empty($source->customer))
            {
                $error_msg = __('Please enter your card details to make a payment.', 'wp-event-manager-stripe-split-payment');
                $error_msg .= ' ' . __('Developers: Please make sure that you are including jQuery and there are no JavaScript errors on the page.', 'wp-event-manager-stripe-split-payment');
                throw new Exception($error_msg);
            }

            // Store source to order meta
            $this->save_source($order, $source);

            // Handle payment
            if ($order->get_total() > 0)
            {

                if ($order->get_total() * 100 < 50)
                {
                    throw new Exception(__('Sorry, the minimum allowed order total is 0.50 to use this payment method.', 'wp-event-manager-stripe-split-payment'));
                }

                WPEM_Stripe_Split_Payment::log("Info: Begin processing payment for order $order_id for the amount of {$order->get_total()}");

                // Make the charges request to stripe api
                $response = WPEM_Stripe_Split_Payment_API::request($this->generate_payment_request($order, $source));

                if (is_wp_error($response))
                {
                    // Customer param wrong? The user may have been deleted on stripe's end. Remove customer_id. Can be retried without.
                    if ('customer' === $response->get_error_code() && $retry)
                    {
                        error_log('inside process_payment customer condition');
                        //delete_user_meta( get_current_user_id(), '_stripe_customer_id' );
                        return $this->process_payment($order_id, false, $force_customer);
                    }
                    throw new Exception("process_payment:" . print_r($response, true));
                }

                // Process valid response
                $this->process_response($response, $order);
            }
            else
            {
                //This ensures stock reductions are made, and the status is changed to the correct value.
                $order->payment_complete();
            }

            // Remove cart
            WC()->cart->empty_cart();

            // Return thank you page redirect
            return array(
                'result'   => 'success',
                'redirect' => $this->get_return_url($order)
            );
        }
        catch (Exception $e)
        {
            //If payment fails, you should throw an error and return null:
            wc_add_notice($e->getMessage(), 'error');
            WC()->session->set('refresh_totals', true);
            WPEM_Stripe_Split_Payment::log(sprintf(__('Error: %s', 'wp-event-manager-stripe-split-payment'), $e->getMessage()));
            return;
        }
    }

    /**
     * Generate the request for the payment.
     * More about $order object details : wp-content\plugins\woocommerce\includes\abstracts\abstract-wc-order.php
     * @param  WC_Order $order
     * @param  object $source
     * @return array()
     */
    public function generate_payment_request($order, $source)
    {
        WPEM_Stripe_Split_Payment::log("generate_payment_request...");

        $post_data                = array();
        $post_data['currency']    = strtolower($order->get_currency() ? $order->get_currency() : get_woocommerce_currency());
        $post_data['amount']      = $this->get_stripe_amount($order->get_total(), $post_data['currency']);
        $post_data['description'] = sprintf(__('%s - Order %s', 'wp-event-manager-stripe-split-payment'), wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES), $order->get_order_number());
        $post_data['capture']     = 'true';

        //A set of key/value pairs that you can attach to a charge object. It can be useful for storing additional information about the charge in a structured format.
        $post_data['metadata'] = array(
            'Order #'                         => $order->get_order_number(),
            'Total Tax'                       => $order->get_total_tax(),
            'Total Shipping'                  => $order->get_total_shipping(),
            'Customer IP'                     => $this->get_client_ip(),
            'Platform - WP User(Customer) ID' => $order->get_user_id(),
            'Billing Email'                   => $order->get_billing_email(),
            'address'                         => $order->get_billing_address_1(),
            'name'                            => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            'phone'                           => $order->get_billing_phone(),  //shipping phone is billing phone so we have to use billing phone

        );

        //shipping information is required for indian customer
        // currently we will pass shipping by default in future this we will improve
          $post_data['shipping'] = array(
                'address' => array(
                    'line1'       => $order->get_billing_address_1(),
                    'line2'       => $order->get_billing_address_2(),
                    'city'        => $order->get_billing_city(),
                    'state'       => $order->get_billing_state(),
                    'country'     => $order->get_billing_country(),
                    'postal_code' => $order->get_billing_postcode()
                ),
                'name'    => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                'phone'   => $order->get_billing_phone(),  //shipping phone is billing phone so we have to use billing phone


            );


        if (!empty($order->get_billing_email()) && apply_filters('wpem_stripe_send_stripe_receipt', false))
        {
            //if receipt email allow then send to stripe
            if ('yes' == $this->stripe_receipt_email)
                $post_data['receipt_email'] = $order->get_billing_email();
        }

        //if shipping address enabled from admin settings
        if ('yes' == $this->stripe_shipping_address)
        {

            $post_data['shipping'] = array(
                'address' => array(
                    'line1'       => $order->get_shipping_address_1(),
                    'line2'       => $order->get_shipping_address_2(),
                    'city'        => $order->get_shipping_city(),
                    'state'       => $order->get_shipping_state(),
                    'country'     => $order->get_shipping_country(),
                    'postal_code' => $order->get_shipping_postcode()
                ),
                'name'    => $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name(),
                'phone'   => $order->get_billing_phone()  //shipping phone is billing phone so we have to use billing phone
            );
        }

        $post_data['expand[]'] = 'balance_transaction';

        //customer id something like cus_*****
        if (!empty($source->customer))
        {
            $post_data['customer'] = $source->customer;
        }

        //source something like card_*******
        if ($source->source)
        {
            $post_data['source'] = $source->source;
        }

        //stripe_token something like tok_xxxxxxxxxxxxxxx
        return $post_data;
    }

    /**
     * Get payment source. This can be a new token or existing card.
     * @param  $order object
     * @param  bool $force_customer Should we force customer creation?
     * @return object
     */
    public function get_source($order, $force_customer = false)
    {
        WPEM_Stripe_Split_Payment::log("get_source...");

        $user_id      = get_current_user_id();
        $customer_id  = is_user_logged_in() ? get_user_meta($user_id, '_stripe_customer_id', true) : 0;
        if (!$customer_id || !is_string($customer_id))
            $customer_id  = 0;
        $stripe_token = isset($_POST['stripe_token']) ? wc_clean($_POST['stripe_token']) : '';
        $card_id      = isset($_POST['stripe_card_id']) ? wc_clean($_POST['stripe_card_id']) : '';

        $stripe_customer = new WPEM_Stripe_Split_Payment_Customer($customer_id);
        $stripe_source   = false;

        // Pay by a saved card
        if ($card_id !== 'new' && $card_id && $customer_id)
        {
            $stripe_source = $card_id;
        }
        // If not using a saved card, we need a token
        elseif (empty($stripe_token))
        {
            $error_msg = __('Please enter your card details to make a payment.', 'wp-event-manager-stripe-split-payment');
            $error_msg .= ' ' . __('Developers: Please make sure that you are including jQuery and there are no JavaScript errors on the page.', 'wp-event-manager-stripe-split-payment');
            throw new Exception($error_msg);
        }
        // Use token
        else
        {

            //\Stripe\Customer::retrieve('cus_FrTBJOSTrx9ihu');

            $customer_exists = WPEM_Stripe_Split_Payment_API::request(array(), 'customers/' . $customer_id, 'GET');

            WPEM_Stripe_Split_Payment::log("customer exists... ");

            WPEM_Stripe_Split_Payment::log("else condition... ");
            // Save token if logged in
            if (( is_user_logged_in() && $this->stripe_saved_cards ) || $force_customer || is_wp_error($customer_exists))
            {
                WPEM_Stripe_Split_Payment::log("else condition saved card... ");



                //if logged user is not customer of stripe then just add this user as customer of stripe.
                if (empty($customer_id))
                {
                    WPEM_Stripe_Split_Payment::log("else condition ! customer_id... ");
                    $customer_id = $stripe_customer->add_customer($order, $stripe_token);
                    if (is_wp_error($customer_id))
                    {
                        throw new Exception($customer_id->get_error_message());
                    }
                }
                else
                {
                    WPEM_Stripe_Split_Payment::log("else condition ! customer_id else... ");
                    $card_id = $stripe_customer->add_card($order, $stripe_token, $customer_id);
                    if (is_wp_error($card_id))
                    {
                        throw new Exception($card_id->get_error_message());
                    }
                    $stripe_source = $card_id;
                }
            }
            else
            {
                WPEM_Stripe_Split_Payment::log("else condition out side... ");
                $stripe_source = $stripe_token;
            }
        }
        WPEM_Stripe_Split_Payment::log("else condition whole... ");
        return (object) array(
                    'customer' => $customer_id,
                    'source'   => $stripe_source
        );
    }

    /**
     * Get payment source from an order. This could be used in the future for
     * a subscription as an example, therefore using the current user ID would
     * not work - the customer won't be logged in :)
     *
     * Not using 2.6 tokens for this part since we need a customer AND a card
     * token, and not just one.
     *
     * @param object $order
     * @return object
     */
    public function get_order_source($order = null)
    {
        $stripe_customer = new WPEM_Stripe_Split_Payment_Customer();
        $stripe_source   = false;
        $token_id        = false;

        if ($order)
        {
            if ($meta_value = get_post_meta($order->get_id(), '_stripe_customer_id', true))
            {
                $stripe_customer->set_id($meta_value);
            }
            if ($meta_value = get_post_meta($order->get_id(), '_stripe_card_id', true))
            {
                $stripe_source = $meta_value;
            }
        }

        return (object) array(
                    'token_id' => $token_id,
                    'customer' => $stripe_customer ? $stripe_customer->get_id() : false,
                    'source'   => $stripe_source,
        );
    }

    /**
     * Save source to order.
     */
    public function save_source($order, $source)
    {
        // Store source in the order
        if ($source->customer)
        {
            update_post_meta($order->get_id(), '_stripe_customer_id', $source->customer);
        }
        if ($source->source)
        {
            update_post_meta($order->get_id(), '_stripe_card_id', $source->source);
        }
    }

    /**
     * Store extra meta data for an order from a Stripe Response.
     */
    public function process_response($response, $order)
    {
        WPEM_Stripe_Split_Payment::log("Processing response...");

        // Store charge data
        update_post_meta($order->get_id(), '_stripe_charge_id', $response->id);
        update_post_meta($order->get_id(), '_stripe_charge_captured', $response->captured ? 'yes' : 'no' );

        // Store other data such as fees
        if (isset($response->balance_transaction) && isset($response->balance_transaction->fee))
        {
            $fee = number_format($response->balance_transaction->fee / 100, 2, '.', '');
            //update_post_meta( $order->id, 'Stripe Fee', $fee );
            update_post_meta($order->get_id(), '_stripe_fee', $fee);
            //update_post_meta( $order->id, 'Net Revenue From Stripe', $order->get_total() - $fee );
            update_post_meta($order->get_id(), '_net_revenue_from_stripe', $order->get_total() - $fee);
        }

        //paid true if the charge succeeded, or was successfully authorized for later capture.
        //If the charge was created without capturing, this boolean represents whether or not it is still uncaptured or has since been captured.
        if ($response->paid || $response->captured)
        {

            $timestamp = date('Y-m-d H:i:s A e', $response->created);
            if ($response->source->object == "card")
            {
                $order->add_order_note(__('Charge ' . $response->status . ' at ' . $timestamp . ',Charge ID=' . $response->id . ',Card=' . $response->source->brand . ' : ' . $response->source->last4 . ' : ' . $response->source->exp_month . '/' . $response->source->exp_year, 'wp-event-manager-stripe-split-payment'));
            }

            if ($response->captured)
            {
                // Payment complete
                $order->payment_complete($response->id);

                add_post_meta($order->get_id(), '_stripe_charge_status', 'charge_auth_captured');

                // Add order note
                $order->add_order_note(sprintf(__('Stripe charge captured complete (Charge ID: %s)', 'wp-event-manager-stripe-split-payment'), $response->id));
            }
            else
            {

                if ($order->has_status(array('pending', 'failed')))
                {
                    $order->reduce_order_stock();
                }

                add_post_meta($order->get_id(), '_transaction_id', $response->id, true);
                add_post_meta($order->get_id(), '_stripe_charge_status', 'charge_auth_only');

                // Add order note
                $order->add_order_note(sprintf(__('Stripe charge authorized complete (Charge ID: %s)', 'wp-event-manager-stripe-split-payment'), $response->id));

                // Mark as on-hold
                $order->update_status('on-hold', sprintf(__('Stripe charge authorized (Charge ID: %s). Process order to take payment, or cancel to remove the pre-authorization.', 'wp-event-manager-stripe-split-payment'), $response->id));

                WPEM_Stripe_Split_Payment::log("Successful auth: $response->id");
            }
        }
        else
        {
            // Add order note
            $order->add_order_note(__('Charge ' . $response->status, 'wp-event-manager-stripe-split-payment'));
            wc_add_notice($response->status, $notice_type = 'error');
        }


        WPEM_Stripe_Split_Payment::log("Response:".print_r($response,true));

        return $response;
    }

    /**
     * Get Stripe amount to pay
     * @return float
     */
    public function get_stripe_amount($total, $currency = '')
    {
        if (!$currency)
        {
            $currency = get_woocommerce_currency();
        }
        switch (strtoupper($currency))
        {
            // Zero decimal currencies
            case 'BIF' :
            case 'CLP' :
            case 'DJF' :
            case 'GNF' :
            case 'JPY' :
            case 'KMF' :
            case 'KRW' :
            case 'MGA' :
            case 'PYG' :
            case 'RWF' :
            case 'VND' :
            case 'VUV' :
            case 'XAF' :
            case 'XOF' :
            case 'XPF' :
                $total = absint($total);
                break;
            default :
                $total = round($total, 2) * 100; // In cents
                break;
        }
        return $total;
    }

    /**
     * Add payment method via account screen.
     * We don't store the token locally, but to the Stripe API.
     * @since 3.0.0
     */
    public function add_payment_method()
    {
        if (empty($_POST['stripe_token']) || !is_user_logged_in())
        {
            wc_add_notice(__('There was a problem adding the card.', 'wp-event-manager-stripe-split-payment'), 'error');
            return;
        }

        $stripe_customer = new WPEM_Stripe_Split_Payment_Customer(get_current_user_id());
        $result          = $stripe_customer->add_card(wc_clean($_POST['stripe_token']));

        if (is_wp_error($result))
        {
            throw new Exception($result->get_error_message());
        }

        return array(
            'result'   => 'success',
            'redirect' => wc_get_endpoint_url('payment-methods'),
        );
    }

    /**
     * Refund a charge
     * When proccess refund from woocommerce, admin panel then this method will call.
     * @param  int $order_id
     * @param  float $amount
     * @return bool
     */
    public function process_refund($order_id, $amount = null, $reason = '')
    {
        $order = wc_get_order($order_id);

        if (!$order || !$order->get_transaction_id())
        {
            return false;
        }

        $source = $this->get_order_source($order);

        $request = array(
            'charge'                 => $order->get_transaction_id(),
            "refund_application_fee" => true,
        );


        $response = WPEM_Stripe_Split_Payment_API::request($request, 'refund');
        WPEM_Stripe_Split_Payment::log("Error in refund response: " . print_r($response, true));


        // $body = array();
        // if ( ! is_null( $amount ) ) 
        // {
        // 	$body['amount']	= $this->get_stripe_amount( $amount );
        // }
        // if ( $reason ) {
        // 	$body['metadata'] = array(
        // 			'reason'	=> $reason,
        // 	);
        // }
        // WPEM_Stripe_Split_Payment::log( "Info: Beginning refund for order-id $order_id for the amount of {$amount}" );
        // $response = WPEM_Stripe_Split_Payment_API::request( $body, 'refund' . $order->get_transaction_id() . '/refunds' );

        if (is_wp_error($response))
        {
            WPEM_Stripe_Split_Payment::log("Error: " . $response->get_error_message());
            return $response;
        }
        elseif (!empty($response->id))
        {
            $refund_message = sprintf(__('Refunded %s - Refund ID: %s - Reason: %s', 'wp-event-manager-stripe-split-payment'), wc_price($response->amount / 100), $response->id, $reason);
            $order->add_order_note($refund_message);
            WPEM_Stripe_Split_Payment::log("Success: " . html_entity_decode(strip_tags($refund_message)));
            return true;
        }
    }

    /**
     * Capture payment when the order is changed from on-hold to complete or processing
     *
     * @param  int $order_id
     */
    public function capture_payment($order_id)
    {
        WPEM_Stripe_Split_Payment::log("capture_payment.... ");

        $order = wc_get_order($order_id);

        //check if payment method is stripe then and then allow to proceed further.
        if ('wpem_stripe' === $order->payment_method)
        {
            $charge   = get_post_meta($order_id, '_stripe_charge_id', true);
            $captured = get_post_meta($order_id, '_stripe_charge_captured', true);

            if ($charge && 'no' === $captured)
            {
                $result = WPEM_Stripe_Split_Payment_API::request(array(
                            'amount'   => $order->get_total() * 100,
                            'expand[]' => 'balance_transaction'
                                ), 'charges/' . $charge . '/capture');

                if (is_wp_error($result))
                {
                    $order->add_order_note(__('Unable to capture charge!', 'wp-event-manager-stripe-split-payment') . ' ' . $result->get_error_message());
                }
                else
                {
                    $order->add_order_note(sprintf(__('Stripe charge complete (Charge ID: %s)', 'wp-event-manager-stripe-split-payment'), $result->id));
                    update_post_meta($order->get_id(), '_stripe_charge_captured', 'yes');

                    // Store other data such as fees
                    update_post_meta($order->get_id(), 'Stripe Payment ID', $result->id);

                    if (isset($result->balance_transaction) && isset($result->balance_transaction->fee))
                    {
                        update_post_meta($order->get_id(), 'Stripe Fee', number_format($result->balance_transaction->fee / 100, 2, '.', ''));
                        update_post_meta($order->get_id(), 'Net Revenue From Stripe', ( $order->order_total - number_format($result->balance_transaction->fee / 100, 2, '.', '')));
                    }
                }
            }
        }
    }

    /**
     * Cancel pre-auth on refund/cancellation
     *
     * @param  int $order_id
     */
    public function cancel_payment($order_id)
    {
        WPEM_Stripe_Split_Payment::log("cancel_payment.... ");
        $order = wc_get_order($order_id);

        //check if payment method is stripe then and then allow to proceed further.
        if ('wpem_stripe' === $order->payment_method)
        {
            $charge = get_post_meta($order_id, '_stripe_charge_id', true);

            if ($charge)
            {
                $result = WPEM_Stripe_Split_Payment_API::request(array(
                            'amount' => $order->get_total() * 100,
                                ), 'charges/' . $charge . '/refund');

                if (is_wp_error($result))
                {
                    $order->add_order_note(__('Unable to refund charge!', 'wp-event-manager-stripe-split-payment') . ' ' . $result->get_error_message());
                }
                else
                {
                    $order->add_order_note(sprintf(__('Stripe charge refunded (Charge ID: %s)', 'wp-event-manager-stripe-split-payment'), $result->id));
                    delete_post_meta($order->get_id(), '_stripe_charge_captured');
                    delete_post_meta($order->get_id(), '_stripe_charge_id');
                }
            }
        }
    }

    /**
     * Gets saved tokens from API if they don't already exist in WooCommerce.
     * @param array $tokens
     * @return array
     */
    public function woocommerce_get_customer_payment_tokens($tokens, $customer_id, $gateway_id)
    {
        WPEM_Stripe_Split_Payment::log("woocommerce_get_customer_payment_tokens.... ");
        if (is_user_logged_in() && 'wpem_stripe' === $gateway_id && class_exists('WC_Payment_Token_CC'))
        {
            $stripe_customer = new WPEM_Stripe_Split_Payment_Customer($customer_id);
            $stripe_cards    = $stripe_customer->get_cards();
            $stored_tokens   = array();

            foreach ($tokens as $token)
            {
                $stored_tokens[] = $token->get_token();
            }

            foreach ($stripe_cards as $card)
            {
                if (!in_array($card->id, $stored_tokens))
                {
                    $token                    = new WC_Payment_Token_CC();
                    $token->set_token($card->id);
                    $token->set_gateway_id('wpem_stripe');
                    $token->set_card_type(strtolower($card->brand));
                    $token->set_last4($card->last4);
                    $token->set_expiry_month($card->exp_month);
                    $token->set_expiry_year($card->exp_year);
                    $token->set_user_id($customer_id);
                    $token->save();
                    $tokens[$token->get_id()] = $token;
                }
            }
        }
        return $tokens;
    }

    /**
     * Delete token from Stripe
     */
    public function woocommerce_payment_token_deleted($token_id, $token)
    {
        WPEM_Stripe_Split_Payment::log("woocommerce_payment_token_deleted.... ");
        if ('wpem_stripe' === $token->get_gateway_id())
        {
            $stripe_customer = new WPEM_Stripe_Split_Payment_Customer(get_current_user_id());
            $stripe_customer->delete_card($token->get_token());
        }
    }

    /**
     * Set as default in Stripe
     */
    public function woocommerce_payment_token_set_default($token_id)
    {

        $token = WC_Payment_Tokens::get($token_id);
        if ('wpem_stripe' === $token->get_gateway_id())
        {
            $stripe_customer = new WPEM_Stripe_Split_Payment_Customer(get_current_user_id());
            $stripe_customer->set_default_card($token->get_token());
        }
    }

    /**
     * Autocomplete only Paid Orders (WC 2.2+)
     */
    public function woocommerce_autocomplete_paid_orders($order_status, $order_id)
    {

        $order = wc_get_order($order_id);
        if ($order_status == 'processing' && ( $order->get_status() == 'on-hold' || $order->get_status() == 'pending' || $order->get_status() == 'failed' ))
        {
            return 'completed';
        }
        return $order_status;
    }

}

$GLOBALS['wpem_stripe_split_payment_gateway'] = new WPEM_Stripe_Split_Payment_Gateway();
