<?php

if (!defined('ABSPATH'))
{
    exit;
}

use Stripe\Stripe;
use Stripe\Transfer;
use Stripe\Account;
use Stripe\Charge;
use Stripe\Token;
use Stripe\Customer;

/**
 * WPEM_Stripe_Split_Payment_API class.
 *
 * Communicates with Stripe API.
 */
class WPEM_Stripe_Split_Payment_API {

    /**
     * Stripe API Endpoint
     */
    const STRIPE_END_POINT = 'https://api.stripe.com/v1/';

    /**
     * Secret API Key.
     * @var string
     */
    private static $secret_key = '';

    /**
     *
     * Client API Id
     */
    private static $client_id = '';

    /**
     *
     * Post Author Id
     */
    private static $connected_stripe_account_id = '';

    /**
     *
     * Woocommerce Order Id
     */
    private static $order_id = 0;

    /**
     * Set secret API Key.
     * @param string $key
     */
    public static function set_secret_key($secret_key)
    {
        self::$secret_key = $secret_key;

        Stripe::setApiKey($secret_key);
        Stripe::setApiVersion("2016-07-06");
    }

    /**
     * Set client API Id.
     * @param string $id
     */
    public static function set_client_id($client_id)
    {
        self::$client_id = $client_id;
    }

    /**
     * Set client API Id.
     * @param string $id
     */
    public static function set_connected_stripe_account_id($connected_stripe_account_id)
    {
        self::$connected_stripe_account_id = $connected_stripe_account_id;
    }

    /**
     * Set woocommerce order id.
     * @param int $id
     */
    public static function set_woocommerce_order_id($order_id)
    {
        self::$order_id = $order_id;
    }

    /**
     * Get secret key.
     * @return string
     */
    public static function get_secret_key()
    {
        if (!self::$secret_key)
        {
            $options = get_option('woocommerce_wpem_stripe_settings');

            if (isset($options['stripe_testmode'], $options['stripe_test_secretkey'], $options['stripe_live_secretkey']))
            {
                self::set_secret_key('yes' === $options['stripe_testmode'] ? $options['stripe_test_secretkey'] : $options['stripe_live_secretkey'] );
            }
        }
        return self::$secret_key;
    }

    /**
     * Is stripe connect enabled.
     * @return true | false
     */
    public static function is_stripe_connect_enabled()
    {
        $stripe_connect_enabled = false;
        if (self::$secret_key)
        {
            $options = get_option('woocommerce_wpem_stripe_settings');

            if (isset($options['stripe_connect']))
            {
                if ('yes' == $options['stripe_connect'] || $options['stripe_connect'] == true)
                    $stripe_connect_enabled = true;
            }
        }
        return $stripe_connect_enabled;
    }

    /**
     * Is stripe logging enabled.
     * @return true | false
     */
    public static function is_stripe_logging_enabled()
    {
        $stripe_logging_enabled = false;
        $options                = get_option('woocommerce_wpem_stripe_settings');
        if (isset($options['stripe_logging']))
        {
            if ($options['stripe_logging'] == 'yes')
                $stripe_logging_enabled = true;
        }
        return $stripe_logging_enabled;
    }

    /**
     * Get client id.
     * @return string
     */
    public static function get_client_id()
    {
        if (!self::$client_id)
        {
            $options = get_option('woocommerce_wpem_stripe_settings');

            if (isset($options['stripe_testmode'], $options['stripe_test_clientid'], $options['stripe_live_clientid']))
            {
                self::set_secret_key('yes' === $options['stripe_testmode'] ? $options['stripe_test_clientid'] : $options['stripe_live_clientid'] );
            }
        }
        return self::$client_id;
    }

    /**
     * Get connected stripe account id
     * @return string
     */
    public static function get_connected_stripe_account_id()
    {
        return self::$connected_stripe_account_id;
    }

    /**
     * Get woocommerce order id.
     * @param int $id
     */
    public static function get_woocommerce_order_id()
    {
        return self::$order_id;
    }

    /**
     * Send the request to Stripe's API
     *
     * @param array $request
     * @param string $api
     * @return array|WP_Error
     */
    public static function request($request, $api = 'charges', $method = 'POST')
    {

        try
        {
            if ($api == 'charges' )
            {
                //we are using Charging directly
                //https://stripe.com/docs/connect/payments-fees#creating-payments
                //get current logged user's stripe account which is already existed with seller's stripe account
                $customer_id = is_user_logged_in() ? get_user_meta(get_current_user_id(), '_stripe_customer_id', true) : 0;

                $connected_arg = [];
                
                if(self::get_connected_stripe_account_id())
                  $connected_arg['stripe_account'] =  self::get_connected_stripe_account_id(); // id of the connected account

                if (self::get_connected_stripe_account_id() && is_user_logged_in())
                {
                    //get saved customer on platform's stripe account (eventflavour's Stripe account)
                    //for mor info: https://stripe.com/docs/connect/shared-customers
                    $response = Token::create(array("customer" => $customer_id), $connected_arg);

                    WPEM_Stripe_Split_Payment::log("response of token : " . print_r($response, true));
                }
                else
                {

                    $card_number = isset($_POST['wpem_stripe-card-number']) ? $_POST['wpem_stripe-card-number'] : '';
                    $card_cvc = isset($_POST['wpem_stripe-card-cvc']) ? $_POST['wpem_stripe-card-cvc'] : '';

                    $card_exp = isset($_POST['wpem_stripe-card-expiry']) ? explode('/', $_POST['wpem_stripe-card-expiry']) : '';
                    $card_month = isset($card_exp[0]) ? trim($card_exp[0]) : '';
                    $card_year = isset($card_exp[1]) ? trim($card_exp[1]) : '';
                    $billing_first_name = isset($_POST['billing_first_name'] ) ? $_POST['billing_first_name'] : ''; 
                    $billing_last_name = isset($_POST['billing_last_name'] ) ? $_POST['billing_last_name'] : ''; 
                    
                    $token_args = array(
                                        "card" => array(
                                            'number'    => $card_number,
                                            'exp_month' => number_format($card_month),
                                            'exp_year'  => number_format($card_year),
                                            'cvc'       => $card_cvc,
                                            'name'      => $billing_first_name . ' ' . $billing_last_name,
                                            'address_line1'     => isset($_POST['billing_address_1']) ? $_POST['billing_address_1'] : '',
                                            'address_line2'     => isset($_POST['billing_address_2']) ? $_POST['billing_address_2'] : '',
                                            'address_city'      => isset($_POST['billing_city']) ? $_POST['billing_city'] : '',
                                            'address_state'     => isset($_POST['billing_state']) ? $_POST['billing_state'] : '',
                                            'address_zip'       => isset($_POST['billing_postcode']) ? $_POST['billing_postcode'] : '',
                                            'address_country'   => isset($_POST['billing_country']) ? $_POST['billing_country'] : '',
                                        )
                                    );


                    //get saved customer on platform's stripe account (eventflavour's Stripe account)
                    //for mor info: https://stripe.com/docs/connect/shared-customers
                    $response = Token::create($token_args);

                    WPEM_Stripe_Split_Payment::log("response of token  card static: " . print_r($response, true));
                }


                //add application fee or other parameter                            
                $request = apply_filters('wpem_stripe_api_request_body', $request, $api, WPEM_Stripe_Split_Payment_API::get_woocommerce_order_id());

                //replace source with fresh token of shared customer       
                $request['source'] = $response->id;
                
                //remove customer parameter because this customer is not exist on seller account,it is only exist on platform account                            
                unset($request['customer']);

                // Create the charge with Stripe
                $response = Charge::create($request, $connected_arg);

                WPEM_Stripe_Split_Payment::log("Charges Request response : " . print_r($response, true));

                $parsed_response = $response;

                // //WPEM_Stripe_Split_Payment::log( "Convert response into array : " . print_r( $response ->__toArray(true),true ) );
                //WPEM_Stripe_Split_Payment::log( "Response as object : " . print_r( $response ,true) );
            }
            elseif ( $api == 'refund' )
            {
                $refund  = \Stripe\Refund::create($request, $connected_arg);

                $parsed_response = $refund;
            }
            elseif ($api == 'customers')
            {
                $customer = \Stripe\Customer::create($request);

                $parsed_response = $customer;
            }
            else
            {
                $customer_id = is_user_logged_in() ? get_user_meta(get_current_user_id(), '_stripe_customer_id', true) : 0;

                WPEM_Stripe_Split_Payment::log("else charge condition in request function");
                //$request['charge'] = self::get_connected_stripe_account_id();
                //$request[] = array('stripe_account' => $customer_id);

                WPEM_Stripe_Split_Payment::log("connected account id" . $customer_id );

                $body = apply_filters('wpem_stripe_api_request_body', $request, $api, WPEM_Stripe_Split_Payment_API::get_woocommerce_order_id());

                $response = wp_safe_remote_post(
                    self::STRIPE_END_POINT . $api, array(
                    'method'     => $method,
                    'headers'    => array(
                        'Authorization'  => 'Basic ' . base64_encode(self::get_secret_key() . ':'),
                        'Stripe-Version' => '2016-07-06'  //when new version will come then need to update here
                    ),
                    'body'       => $body,
                    'timeout'    => 70,
                    'sslverify'  => false,
                    'user-agent' => 'WooCommerce ' . WC()->version
                        )
                );

                if (is_wp_error($response) || empty($response['body']))
                {
                    WPEM_Stripe_Split_Payment::log( "Error Response: " . print_r( $response, true ) );
                    return new WP_Error('stripe_error', __('There was a problem connecting to the payment gateway.', 'wp-event-manager-stripe-split-payment'));
                }
                $parsed_response = json_decode($response['body']);
            }


            // Handle response
            if (!empty($parsed_response->error))
            {
                if (!empty($parsed_response->error->param))
                {
                    $code = $parsed_response->error->param;
                }
                elseif (!empty($parsed_response->error->code))
                {
                    $code = $parsed_response->error->code;
                }
                else
                {
                    $code = 'stripe_error';
                }
                return new WP_Error($code, $parsed_response->error->message);
            }
            else
            {
                return $parsed_response;
            }
        }
        catch (\Stripe\Error\Card $e)
        {
          
            // Since it's a decline, \Stripe\Error\Card will be caught
            $body  = $e->getJsonBody();
            $error = $body['error'];

            print('Status is:' . $e->getHttpStatus() . "\n");
            print('Type is:' . $error['type'] . "\n");
            print('Code is:' . $error['code'] . "\n");
            // param is '' in this case
            print('Param is:' . $error['param'] . "\n");
            print('Message is:' . $error['message'] . "\n");

            return new WP_Error("Error Response - Card: ", $error['message']);
        }
        catch (\Stripe\Error\RateLimit $e)
        {
            // Too many requests made to the API too quickly
            $error = $e->getMessage();
            return new WP_Error("Error Response - RateLimit: ", $error);
        }
        catch (\Stripe\Error\InvalidRequest $e)
        {
            // Invalid parameters were supplied to Stripe's API
            $error = $e->getMessage();
            return new WP_Error("Error Response - InvalidRequest: ", $error);
        }
        catch (\Stripe\Error\Authentication $e)
        {
            // Authentication with Stripe's API failed
            // (maybe you changed API keys recently)
            $error = $e->getMessage();
            return new WP_Error("Error Response - Authentication: ", $error);
        }
        catch (\Stripe\Error\ApiConnection $e)
        {
            // Network communication with Stripe failed
            $error = $e->getMessage();
            return new WP_Error("Error Response - ApiConnection: ", $error);
        }
        catch (\Stripe\Error\Base $e)
        {
            // Display a very generic error to the user, and maybe send
            // yourself an email
            $error = $e->getMessage();
            return new WP_Error("Error Response - Base: ", $error);
        }
        catch (Exception $e)
        {
            // Something else happened, completely unrelated to Stripe
            $error = $e->getMessage();
            return new WP_Error("Error Response: ", $error);
        }
    }

    /**
     * Connecting to stripe's users with API
     *
     * The Stripe endpoint needs to at least receive two parameters: response_type, with a value of code. Your client_id.
     * You�ll likely also want to provide the scope. 
     * This parameter dictates what your platform will be able to do on behalf of the connected account.
     * The options are read_write and read_only, with read_only being the default.
     *
     * For an analytics platform, read_only is appropriate; if you need to perform charges on behalf of the connected user, you will need to request read_write scope instead.      
     * 
     * If user successfully connected then it will return authorization code as response,otherwise error.
     * @param string $user_id
     * @param string $client_id
     * @param string $scope
     * @return $url
     */
    public static function connect_with_stripe($user_id, $client_id, $scope = 'read_only')
    {
        //WPEM_Stripe_Split_Payment::log( "connect_with_stripe request with user_id:" . $user_id . ' client_id:' . $client_id.' scope:'. $scope );
        // Show OAuth link
        $authorize_request_body = array(
            'response_type' => 'code',
            'scope'         => $scope,
            'client_id'     => $client_id,
            'state'         => $user_id
        );
        $url                    = 'https://connect.stripe.com/oauth/authorize?' . http_build_query($authorize_request_body);

        return $url;
    }

    /**
     * After the user has connected to stripe, get authorization credentials.
     *
     * The user is now connected to your platform. You�ll want to store all of the returned information in your database for later use.
     * When the user arrives at Stripe, they�ll be prompted to allow or deny the connection to your platform, and will then be sent to your redirect_uri page. 
      In the URL, we�ll pass along an authorization code.

      Stripe will return a response containing the authentication credentials for the user:
      {
      "token_type": "bearer",
      "stripe_publishable_key": PUBLISHABLE_KEY,
      "scope": "read_write",
      "livemode": false,
      "stripe_user_id": USER_ID,
      "refresh_token": REFRESH_TOKEN,
      "access_token": ACCESS_TOKEN
      }

      If there was a problem, we�ll instead return an error:
      {
      "error": "invalid_grant",
      "error_description": "Authorization code does not exist: AUTHORIZATION_CODE"
      }
     *
     * 	
     * @param string $client_id
     * @param string $secret_key
     * @param string $code
     * @return array $response
     */
    public static function get_authorization_credentials($secret_key, $client_id, $code)
    {
        WPEM_Stripe_Split_Payment::log("get_authorization_credentials request with secret_key:" . $secret_key . ' client_id:' . $client_id . ' code:' . $code);

        $token_request_body = array(
            'grant_type'    => 'authorization_code',
            'client_secret' => $secret_key,
            'code'          => $code,
            'client_id'     => $client_id
        );

        $request = curl_init('https://connect.stripe.com/oauth/token');
        curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($request, CURLOPT_POST, true);
        curl_setopt($request, CURLOPT_POSTFIELDS, http_build_query($token_request_body));
        curl_setopt($request, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($request, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($request, CURLOPT_VERBOSE, true);

        // TODO: Additional error handling
        $responseCode = curl_getinfo($request, CURLINFO_HTTP_CODE);
        $response     = json_decode(curl_exec($request), true);
        curl_close($request);

        WPEM_Stripe_Split_Payment::log("Response: " . print_r($response, true));
        return $response;
    }

}

$GLOBALS['wpem_stripe_split_payment_api'] = new WPEM_Stripe_Split_Payment_API();
