<?php

if (!defined('ABSPATH'))
{
    exit;
}

use Stripe\Token;

/**
 * WPEM_Stripe_Split_Payment_Customer class.
 *
 * Represents a Stripe Customer.
 */
class WPEM_Stripe_Split_Payment_Customer {

    /**
     * Stripe customer ID
     * @var string
     */
    private $id = '';

    /**
     * WP User ID
     * @var integer
     */
    private $user_id = 0;

    /**
     * Data from API
     * @var array
     */
    private $customer_data = array();

    /**
     * Constructor
     * @param integer $user_id
     */
    public function __construct($customer_id = 0)
    {
        WPEM_Stripe_Split_Payment::log("WPEM_Stripe_Split_Payment_Customer __construct...");
        $this->set_id($customer_id);
    }

    /**
     * Get Stripe customer ID.
     * @return string
     */
    public function get_id()
    {
        WPEM_Stripe_Split_Payment::log("WPEM_Stripe_Split_Payment_Customer get_id...");
        return $this->id;
    }

    /**
     * Set Stripe customer ID.
     * Clean variables using sanitize_text_field. Arrays are cleaned recursively. Non-scalar values are ignored.
     * @param [type] $id [description]
     */
    public function set_id($id)
    {
        WPEM_Stripe_Split_Payment::log("WPEM_Stripe_Split_Payment_Customer set_id...");
        $this->id = wc_clean($id);
    }

    /**
     * Store data from the Stripe API about this customer
     */
    public function set_customer_data($data)
    {
        WPEM_Stripe_Split_Payment::log("WPEM_Stripe_Split_Payment_Customer set_customer_data...");
        $this->customer_data = $data;
    }

    /**
     * Get data from the Stripe API about this customer
     */
    public function get_customer_data()
    {
        WPEM_Stripe_Split_Payment::log("WPEM_Stripe_Split_Payment_Customer get_customer_data... ");

        if (empty($this->customer_data) && $this->get_id() && false === ( $this->customer_data = get_transient('stripe_customer_' . $this->get_id()) ))
        {
            $response = WPEM_Stripe_Split_Payment_API::request(array(), 'customers/' . $this->get_id());

            if (!is_wp_error($response))
            {
                $this->set_customer_data($response);
                set_transient('stripe_customer_' . $this->get_id(), $response, HOUR_IN_SECONDS * 48);
            }
        }
        return $this->customer_data;
    }

    /**
     * Get default card/source
     * @return string
     */
    public function get_default_card()
    {
        WPEM_Stripe_Split_Payment::log("WPEM_Stripe_Split_Payment_Customer get_default_card... ");
        $data   = $this->get_customer_data();
        $source = '';

        if ($data)
        {
            $source = $data->default_source;
        }

        return $source;
    }

    /**
     * Create a customer via API.
     * @param object $order
     * @param string $stripe_token
     * @return WP_Error|int
     */
    public function add_customer($order, $stripe_token)
    {
        WPEM_Stripe_Split_Payment::log("add_customer request: " . ' stripe_token:' . $stripe_token);


        if ($stripe_token )
        {
            $args = array(
                'email'       => $order->get_billing_email(),
                'description' => 'Customer: ' . $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                'name'        => $order->get_billing_first_name(),
                'address' => [
                              'line1' => $order->get_billing_address_1(),
                              'postal_code' => $order->get_billing_postcode(),
                              'city' => $order->get_billing_city(),
                              'state' => $order->get_billing_state(),
                              'country' => $order->get_billing_country(),
                            ],


                'source'      => $stripe_token,
                'expand[]'    => 'default_source'
            );

            $response = WPEM_Stripe_Split_Payment_API::request($args, 'customers');

            if (is_wp_error($response))
            {
                return $response;
            }
            elseif (empty($response->id))
            {
                return new WP_Error('stripe_error', __('Could not create Stripe customer.', 'wp-event-manager-stripe-split-payment'));
            }
            elseif (!empty($response->id))
            {
                // Store the ID on the user account
                if(is_user_logged_in())
                update_user_meta(get_current_user_id(), '_stripe_customer_id', $response->id);

                // Store the ID in the order
                update_post_meta($order->get_id(), '_stripe_customer_id', $response->id);

                $this->set_id($response->id);
                $this->clear_cache();
                $this->set_customer_data($response);

                do_action('wpem_stripe_split_add_customer', $args, $response);

                return $response->id;
            }
        }


        return new WP_Error('error', __('Unable to create stripe customer', 'wp-event-manager-stripe-split-payment'));
    }

    /**
     * Add a card for this stripe customer.
     * If customer not exist then also create customer too and then add card.
     * @param object $order
     * @param string $token
     * @param int $customer_id
     * @param bool $retry
     * @return WP_Error|int
     */
    public function add_card($order, $stripe_token, $customer_id, $retry = true)
    {

        WPEM_Stripe_Split_Payment::log("add_card request: " . ' customer_id:' . $customer_id . ' stripe_token:' . $stripe_token);

        if ($stripe_token)
        {
            //check if same card or any other already exist or not
            //this we will use in future if we need it.
            /*
              $response=Token::retrieve($stripe_token);

              if ( !is_wp_error( $response ) )
              {
              WP_WC_Gateway_Stripe::log( "found old card: card->id: " . $response->card->id );

              if(isset($response->card) && $response->card!=null)
              {

              $this->clear_cache();


              do_action( 'woocommerce_stripe_add_card', $customer_id, $stripe_token, $response );

              return $response->card->id;
              }
              }

             */


            $response = WPEM_Stripe_Split_Payment_API::request(
                        array('source' => $stripe_token),
                        'customers/' . $customer_id . '/sources'
                      );


            WPEM_Stripe_Split_Payment::log("Response as card(source) : " . print_r($response, true));


            if (is_wp_error($response))
            {
                if ('customer' === $response->get_error_code() && $retry)
                {
                    $this->add_customer($order, $stripe_token);
                    return $this->add_card($order, $stripe_token, $customer_id, false);
                }
                else
                {
                    return $response;
                }
            }
            elseif (empty($response->id))
            {
                return new WP_Error('error', __('Unable to add card', 'wp-event-manager-stripe-split-payment'));
            }
            elseif (!empty($response->id))
            {

                // Add token to WooCommerce
                //below filter already doing this so we do not need to do here. filter defined at wpem-stripe-split-payment-settings.php
                //add_filter( 'woocommerce_get_customer_payment_tokens', array( $this, 'woocommerce_get_customer_payment_tokens' ), 10, 3 );
                /*
                  if ( class_exists( 'WC_Payment_Token_CC' ) )
                  {
                  $token = new WC_Payment_Token_CC();
                  $token->set_token( $response->id );
                  $token->set_gateway_id( 'stripe' );
                  $token->set_card_type( strtolower( $response->brand ) );
                  $token->set_last4( $response->last4 );
                  $token->set_expiry_month( $response->exp_month  );
                  $token->set_expiry_year( $response->exp_year );
                  $token->set_user_id( get_current_user_id() );
                  $token->save();
                  }
                 */

                $this->clear_cache();

                do_action('woocommerce_stripe_add_card', $customer_id, $stripe_token, $response);

                return $response->id;
            }
        }
        return new WP_Error('error', __('Unable to add card', 'wp-event-manager-stripe-split-payment'));
    }

    /**
     * Get a customers saved cards using their Stripe ID. Cached.
     *
     * @param  string $customer_id
     * @return array
     */
    public function get_cards()
    {
        WPEM_Stripe_Split_Payment::log("WPEM_Stripe_Split_Payment_Customer get_cards... ");
        $cards = array();

        if ($this->get_id() && false === ( $cards = get_transient('stripe_cards_' . $this->get_id()) ))
        {
            $response = WPEM_Stripe_Split_Payment_API::request(array(
                        'limit' => 100,
                        'object'=> 'card'
                            ), 'customers/' . $this->get_id() . '/sources', 'GET');

            if (is_wp_error($response))
            {
                return array();
            }

            if (is_array($response->data))
            {
                $cards = $response->data;
            }

            set_transient('stripe_cards_' . $this->get_id(), $cards, HOUR_IN_SECONDS * 48);
        }

        return $cards;
    }

    /**
     * Delete a card from stripe.
     * @param string $card_id
     */
    public function delete_card($card_id)
    {

        WPEM_Stripe_Split_Payment::log("delete_card... ");

        $response = WPEM_Stripe_Split_Payment_API::request(array(), 'customers/' . $this->get_id() . '/sources/' . sanitize_text_field($card_id), 'DELETE');

        $this->clear_cache();

        if (!is_wp_error($response))
        {
            do_action('wc_stripe_delete_card', $this->get_id(), $response);

            return true;
        }

        return false;
    }

    /**
     * Set default card in Stripe
     * @param string $card_id
     */
    public function set_default_card($card_id)
    {
        WPEM_Stripe_Split_Payment::log("set_default_card... ");

        $response = WPEM_Stripe_Split_Payment_API::request(array(
                    'default_source' => sanitize_text_field($card_id),
                        ), 'customers/' . $this->get_id(), 'POST');

        $this->clear_cache();

        if (!is_wp_error($response))
        {
            do_action('wc_stripe_set_default_card', $this->get_id(), $response);

            return true;
        }

        return false;
    }

    /**
     * Deletes caches for this users cards.
     */
    public function clear_cache()
    {
        WPEM_Stripe_Split_Payment::log("clear_cache... ");

        delete_transient('stripe_cards_' . $this->get_id());
        delete_transient('stripe_customer_' . $this->get_id());
        $this->customer_data = array();
    }

}
