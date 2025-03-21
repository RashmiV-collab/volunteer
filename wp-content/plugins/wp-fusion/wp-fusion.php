<?php

/**
 * Plugin Name: WP Fusion
 * Description: WP Fusion connects your website to your CRM or marketing automation tool, with support for dozens of CRMs and 100+ WordPress plugins.
 * Plugin URI: https://wpfusion.com/
 * Version: 3.41.1
 * Author: Very Good Plugins
 * Author URI: https://verygoodplugins.com/
 * Text Domain: wp-fusion
 *
 * WC requires at least: 3.0
 * WC tested up to: 7.2.0
 * Elementor tested up to: 3.10.0
 * Elementor Pro tested up to: 3.10.1
 */

/**
 * @copyright Copyright (c) 2018. All rights reserved.
 *
 * @license   Released under the GPL license http://www.opensource.org/licenses/gpl-license.php
 *
 * **********************************************************************
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * **********************************************************************
 */

define( 'WP_FUSION_VERSION', '3.41.1' );

// deny direct access.
if ( ! function_exists( 'add_action' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

/**
 * Main WP_Fusion class.
 *
 * @since 1.0.0
 */
final class WP_Fusion {

	/** Singleton *************************************************************/

	/**
	 * @var WP_Fusion The one true WP_Fusion
	 * @since 1.0
	 */
	private static $instance;


	/**
	 * Contains all active integrations classes
	 *
	 * @since 3.0
	 */
	public $integrations;


	/**
	 * Manages configured CRMs
	 *
	 * @since 2.0
	 * @since 3.40 No longer in use, maintained for backwards compatibility.
	 *
	 * @var   WPF_CRM_Base
	 */
	public $crm_base;


	/**
	 * Access to the currently selected CRM.
	 *
	 * @var crm
	 * @since 2.0
	 */
	public $crm;


	/**
	 * Handler for AJAX and and asynchronous functions
	 *
	 * @var crm
	 * @since 2.0
	 */
	public $ajax;


	/**
	 * Handler for batch processing
	 *
	 * @var batch
	 * @since 3.0
	 */
	public $batch;


	/**
	 * Logging and diagnostics class
	 *
	 * @var logger
	 * @since 3.0
	 */
	public $logger;


	/**
	 * User handler - registration, sync, and updates
	 *
	 * @var WPF_User
	 * @since 2.0
	 */
	public $user;


	/**
	 * Stores configured admin meta boxes and other admin interfaces
	 *
	 * @var WPF_Admin_Interfaces
	 * @since 2.0
	 */
	public $admin_interfaces;


	/**
	 * Handles restricted content and redirects
	 *
	 * @var WPF_Access_Control
	 * @since 3.12
	 */
	public $access;


	/**
	 * Handles auto login sessions
	 *
	 * @var WPF_Auto_Login
	 * @since 3.12
	 */
	public $auto_login;


	/**
	 * Handles lead source tracking
	 *
	 * @var WPF_Lead_Sources
	 * @since 3.30.4
	 */
	public $lead_source_tracking;


	/**
	 * The settings instance variable
	 *
	 * @var WPF_Settings
	 * @since 1.0
	 */
	public $settings;


	/**
	 * Main WP_Fusion Instance
	 *
	 * Ensures that only one instance of WP_Fusion exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @since 1.0
	 *
	 * @static var array $instance
	 * @return WP_Fusion The one true WP_Fusion
	 */

	public static function instance() {

		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof WP_Fusion ) ) {

			self::$instance = new WP_Fusion();

			self::$instance->setup_constants();
			self::$instance->check_install();
			self::$instance->init_includes();

			// Create settings.
			self::$instance->settings = new WPF_Settings();
			self::$instance->logger   = new WPF_Log_Handler();
			self::$instance->batch    = new WPF_Batch();

			// Integration modules are stored here for easy access, for
			// example wp_fusion()->integrations->{'woocommerce'}->process_order( $order_id );.

			self::$instance->integrations = new stdClass();

			// Load the CRM modules.
			add_action( 'plugins_loaded', array( self::$instance, 'init_crm' ) );

			// Only useful if a CRM is selected.
			if ( self::$instance->settings->get( 'connection_configured' ) ) {

				self::$instance->includes();

				if ( is_admin() ) {
					self::$instance->admin_interfaces = new WPF_Admin_Interfaces();
				}

				self::$instance->user                 = new WPF_User();
				self::$instance->lead_source_tracking = new WPF_Lead_Source_Tracking();
				self::$instance->access               = new WPF_Access_Control();
				self::$instance->auto_login           = new WPF_Auto_Login();
				self::$instance->ajax                 = new WPF_AJAX();

				add_action( 'plugins_loaded', array( self::$instance, 'integrations_includes' ), 10 ); // This has to be 10 for Elementor.
				add_action( 'after_setup_theme', array( self::$instance, 'integrations_includes_theme' ) );

				add_action( 'init', array( self::$instance, 'init' ), 0 );

			}

			if ( self::$instance->is_full_version() ) {
				add_action( 'init', array( self::$instance, 'updater' ) );
				add_action( 'plugins_loaded', array( self::$instance, 'load_textdomain' ) );
			}

			register_deactivation_hook( __FILE__, array( self::$instance, 'deactivate' ) );

		}

		return self::$instance;

	}

	/**
	 * Throw error on object clone
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @access protected
	 * @return void
	 */

	public function __clone() {
		// Cloning instances of the class is forbidden
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'wp-fusion' ), WP_FUSION_VERSION );
	}

	/**
	 * Disable unserializing of the class
	 *
	 * @access protected
	 * @return void
	 */

	public function __wakeup() {
		// Unserializing instances of the class is forbidden
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'wp-fusion' ), WP_FUSION_VERSION );
	}

	/**
	 * Setup plugin constants
	 *
	 * @access private
	 * @return void
	 */

	private function setup_constants() {

		if ( ! defined( 'WPF_MIN_WP_VERSION' ) ) {
			define( 'WPF_MIN_WP_VERSION', '4.0' );
		}

		if ( ! defined( 'WPF_MIN_PHP_VERSION' ) ) {
			define( 'WPF_MIN_PHP_VERSION', '5.6' );
		}

		if ( ! defined( 'WPF_DIR_PATH' ) ) {
			define( 'WPF_DIR_PATH', plugin_dir_path( __FILE__ ) );
		}

		if ( ! defined( 'WPF_PLUGIN_PATH' ) ) {
			define( 'WPF_PLUGIN_PATH', plugin_basename( __FILE__ ) );
		}

		if ( ! defined( 'WPF_DIR_URL' ) ) {
			define( 'WPF_DIR_URL', plugin_dir_url( __FILE__ ) );
		}

		if ( ! defined( 'WPF_STORE_URL' ) ) {
			define( 'WPF_STORE_URL', 'https://wpfusion.com' );
		}

		if ( ! defined( 'WPF_EDD_ITEM_ID' ) ) {
			define( 'WPF_EDD_ITEM_ID', '4541' );
		}

	}

	/**
	 * Fires when WP Fusion is deactivated.
	 *
	 * @since 3.38.31
	 */
	public function deactivate() {

		$timestamp = wp_next_scheduled( 'wpf_background_process_cron' );

		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'wpf_background_process_cron' );
		}

	}

	/**
	 * Check min PHP version
	 *
	 * @access private
	 * @return bool
	 */

	private function check_install() {

		if ( ! version_compare( phpversion(), WPF_MIN_PHP_VERSION, '>=' ) ) {
			add_action( 'admin_notices', array( self::$instance, 'php_version_notice' ) );
		}

		if ( ! $this->is_full_version() ) {

			if ( ! function_exists( 'is_plugin_active' ) ) {
				require_once ABSPATH . '/wp-admin/includes/plugin.php';
			}

			// If the full version has been installed, deactivate this one.
			if ( is_plugin_active( 'wp-fusion/wp-fusion.php' ) ) {
				add_action( 'admin_notices', array( self::$instance, 'full_version_notice' ) );
				deactivate_plugins( plugin_basename( __FILE__ ) );
			}
		}

	}


	/**
	 * Defines default supported plugin integrations
	 *
	 * @access public
	 * @return array Integrations
	 */

	public function get_integrations() {

		return apply_filters(
			'wpf_integrations',
			array(
				'edd'                           => 'Easy_Digital_Downloads',
				'edd-recurring'                 => 'EDD_Recurring',
				'gravity-forms'                 => 'GFForms',
				'formidable-forms'              => 'FrmFormsController',
				'woocommerce'                   => 'WooCommerce',
				'woo-subscriptions'             => 'WC_Subscriptions_Product', // works for both Woo Subscriptions and Woo Payments.
				'woo-memberships'               => 'WC_Memberships',
				'woo-bookings'                  => 'WC_Bookings',
				'woo-coupons'                   => 'WC_Smart_Coupons',
				'woo-deposits'                  => 'WC_Deposits',
				'woo-addons'                    => 'WC_Product_Addons',
				'ultimate-member-1x'            => 'UM_API',
				'ultimate-member'               => 'UM',
				'userpro'                       => 'userpro_api',
				'acf'                           => 'ACF',
				'acf'                           => 'acf',
				'learndash'                     => 'SFWD_LMS',
				'wpep'                          => 'WPEP\Controller',
				'sensei'                        => 'WooThemes_Sensei',
				'bbpress'                       => 'bbPress',
				'contact-form-7'                => 'wpcf7',
				'membermouse'                   => 'MemberMouse',
				'memberpress'                   => 'MeprBaseCtrl',
				'buddypress'                    => 'BuddyPress',
				'buddyboss-access-control'      => 'BB_Access_Control_Abstract',
				'pmpro'                         => 'MemberOrder',
				'restrict-content-pro'          => 'RCP_Capabilities',
				'lifterlms'                     => 'LifterLMS',
				's2member'                      => 'c_ws_plugin__s2member_utilities',
				'affiliate-wp'                  => 'Affiliate_WP',
				'wp-job-manager'                => 'WP_Job_Manager',
				'user-meta'                     => 'UserMeta\\SupportModel',
				'simple-membership'             => 'SimpleWpMembership',
				'badgeos'                       => 'BadgeOS',
				'tribe-tickets'                 => 'Tribe__Tickets__Main',
				'tribe-events'                  => 'Tribe__Events__Main',
				'wishlist-member'               => 'WishListMember',
				'cred'                          => 'CRED_CRED',
				'mycred'                        => 'myCRED_Core',
				'learnpress'                    => 'LearnPress',
				'courseware'                    => 'WPCW_Requirements',
				'gamipress'                     => 'GamiPress',
				'peepso'                        => 'PeepSo',
				'profilepress'                  => 'ProfilePress_Dir',
				'beaver-builder'                => 'FLBuilder',
				'elementor'                     => 'Elementor\\Frontend',
				'elementor-forms'               => 'ElementorPro\Modules\Forms\Classes\Integration_Base',
				'elementor-popups'              => 'ElementorPro\\Plugin',
				'wplms'                         => 'BP_Course_Component',
				'profile-builder'               => 'WPPB_Add_General_Notices',
				'accessally'                    => 'AccessAlly',
				'wpml'                          => 'SitePress',
				'divi'                          => 'ET_Builder_Plugin',
				'weglot'                        => 'WeglotWP\\Bootstrap_Weglot',
				'wp-complete'                   => 'WPComplete',
				'wpforms'                       => 'WPForms',
				'popup-maker'                   => 'Popup_Maker',
				'wpforo'                        => 'wpforo\\wpforo',
				'give'                          => 'Give',
				'ninja-forms'                   => 'NF_Abstracts_Action',
				'advanced-ads'                  => 'Advanced_Ads',
				'clean-login'                   => 'CleanLogin',
				'private-messages'              => 'Private_Messages',
				'coursepress'                   => 'CoursePress',
				'event-espresso'                => 'EE_Base',
				'fooevents'                     => 'FooEvents',
				'convert-pro'                   => 'Cp_V2_Loader',
				'woo-memberships-teams'         => 'WC_Memberships_For_Teams_Loader',
				'woo-wholesale-lead'            => 'WooCommerce_Wholesale_Lead_Capture',
				'caldera-forms'                 => 'Caldera_Forms',
				'wp-affiliate-manager'          => 'WPAM_Plugin',
				'wcff'                          => 'Wcff',
				'gtranslate'                    => 'GTranslate',
				'tutor-lms'                     => 'tutor_lms',
				'translatepress'                => 'TRP_Translate_Press',
				'edd-software-licensing'        => 'EDD_Software_Licensing',
				'cartflows'                     => 'Cartflows_Loader',
				'memberium'                     => 'memb_getLoggedIn',
				'uncanny-groups'                => 'uncanny_learndash_groups\\InitializePlugin',
				'salon-booking'                 => 'SLN_Plugin',
				'cpt-ui'                        => 'cptui_load_ui_class',
				'ahoy'                          => 'Ahoy',
				'wppizza'                       => 'WPPIZZA',
				'users-insights'                => 'USIN_Manager',
				'e-signature'                   => 'WP_E_Digital_Signature',
				'fluent-forms'                  => 'FluentForm\Framework\Foundation\Bootstrap',
				'toolset-forms'                 => 'CRED_Main',
				'toolset-types'                 => 'Types_Autoloader',
				'wp-event-manager'              => 'WP_Event_Manager_Registrations',
				'gravityview'                   => 'GravityView_Plugin',
				'facetwp'                       => 'FacetWP',
				'share-logins-pro'              => 'codexpert\Share_Logins_Pro\Plugin',
				'bp-account-deactivator'        => 'BP_Account_Deactivator',
				'wp-ultimo'                     => 'WP_Ultimo',
				'edd-custom-prices'             => 'edd_cp_has_custom_pricing',
				'oxygen'                        => 'oxygen_vsb_register_condition',
				'woo-request-a-quote'           => 'Addify_Request_For_Quote',
				'wcs-att'                       => 'WCS_ATT',
				'refer-a-friend'                => 'WPGens_RAF',
				'simple-pay'                    => 'SimplePay\Core\SimplePay',
				'wcs-gifting'                   => 'WCS_Gifting',
				'events-manager'                => 'EM_Object',
				'wp-members'                    => 'wpmem_init',
				'woo-shipment-tracking'         => 'WC_Shipment_Tracking',
				'pods'                          => 'Pods',
				'beaver-themer'                 => 'FLThemeBuilderLoader',
				'woo-appointments'              => 'WC_Appointments',
				'wp-crowdfunding'               => 'WPCF\Crowdfunding',
				'modern-events-calendar'        => 'MEC',
				'woo-points-rewards'            => 'WC_Points_Rewards',
				'wp-remote-users-sync'          => 'wprus_run',
				'yith-vendors'                  => 'YITH_Vendors',
				'buddyboss-iap'                 => 'bbapp_iap',
				'buddyboss-app-segment'         => 'bbapp',
				'members'                       => 'Members_Plugin',
				'piotnet-forms'                 => 'Piotnetforms',
				'ontrapages'                    => 'ONTRApage',
				'ld-group-registration'         => 'LdGroupRegistration\Includes\Ld_Group_Registration',
				'woofunnels'                    => 'WFOCU_Core',
				'tickera'                       => 'TC',
				'ws-form'                       => 'WS_Form',
				'upsell'                        => 'upsell',
				'restropress'                   => 'RestroPress',
				'if-so'                         => 'if_so',
				'eventon'                       => 'EventON',
				'login-with-ajax'               => 'LoginWithAjax',
				'download-monitor'              => 'WP_DLM',
				'simply-schedule-appointments'  => 'Simply_Schedule_Appointments',
				'woo-payment-plans'             => 'WC_Payment_Plans',
				'jet-engine'                    => 'Jet_Engine',
				'if-menu'                       => 'If_Menu',
				'user-menus'                    => 'JP_User_Menus',
				'armember'                      => 'ARMemberlite',
				'solid-affiliate'               => 'SolidAffiliate\Main',
				'slicewp'                       => 'SliceWP',
				'metabox'                       => 'RWMB_Core',
				'acf-frontend'                  => 'Front_End_Admin',
				'wppayform'                     => 'WPPayForm\App\App',
				'wp-booking-system'             => 'WP_Booking_System',
				'wpbakery'                      => 'Vc_Manager',
				'buddyboss-app-access-group'    => 'BuddyBossApp\AccessControls\Integration_Abstract',
				'holler-box'                    => 'Holler_Box',
				'subscriptions-for-woocommerce' => 'Subscriptions_For_Woocommerce',
				'thrive-apprentice'             => 'TVA_Const',
				'thrive-automator-trigger'      => 'Thrive\Automator\Admin',
				'breakdance'                    => 'Breakdance\DynamicData\DynamicDataController',
				'studiocart'                    => 'NCS_Cart',
				'surecart'                      => 'SureCartAppCore\AppCore\AppCoreServiceProvider',
				'yith-woocommerce-booking'      => 'yith_wcbk_init',
				'woo-gravity-forms-addons'      => 'WC_GFPA_Main',
				'pretty-links'                  => 'prli_autoloader',
				'thirsty-affiliates'            => 'ThirstyAffiliates',
				'wp-all-import'                 => 'PMXI_Plugin',
				'object-sync-for-salesforce'    => 'Object_Sync_Salesforce',
				'woo-product-options'           => 'Barn2\Plugin\WC_Product_Options\Plugin',
			)
		);

	}

	/**
	 * Defines default supported theme integrations
	 *
	 * @access public
	 * @return array Integrations
	 */

	public function get_integrations_theme() {

		return apply_filters(
			'wpf_integrations_theme',
			array(
				'divi'                  => 'et_setup_theme',
				'memberoni'             => 'memberoni_llms_theme_support',
				'acf'                   => 'acf', // For ACF bundled with Memberoni or other themes.
				'bricks'                => 'Bricks\Theme',
				'thrive-api-connection' => 'Thrive_Dash_List_Manager',
			)
		);

	}

	/**
	 * Defines supported CRMs
	 *
	 * @access private
	 * @return array CRMS
	 */

	public function get_crms() {

		return apply_filters(
			'wpf_crms',
			array(
				'infusionsoft'     => 'WPF_Infusionsoft_iSDK',
				'activecampaign'   => 'WPF_ActiveCampaign',
				'ontraport'        => 'WPF_Ontraport',
				'drip'             => 'WPF_Drip',
				'convertkit'       => 'WPF_ConvertKit',
				'agilecrm'         => 'WPF_AgileCRM',
				'salesforce'       => 'WPF_Salesforce',
				'mautic'           => 'WPF_Mautic',
				'intercom'         => 'WPF_Intercom',
				// 'aweber'         => 'WPF_AWeber',
				'mailerlite'       => 'WPF_MailerLite',
				'capsule'          => 'WPF_Capsule',
				'zoho'             => 'WPF_Zoho',
				'kartra'           => 'WPF_Kartra',
				'userengage'       => 'WPF_UserEngage',
				'convertfox'       => 'WPF_ConvertFox',
				'salesflare'       => 'WPF_Salesflare',
				// 'vtiger'         => 'WPF_Vtiger',
				'flexie'           => 'WPF_Flexie',
				'tubular'          => 'WPF_Tubular',
				'maropost'         => 'WPF_Maropost',
				'mailchimp'        => 'WPF_MailChimp',
				'sendinblue'       => 'WPF_SendinBlue',
				'hubspot'          => 'WPF_HubSpot',
				'platformly'       => 'WPF_Platformly',
				'drift'            => 'WPF_Drift',
				'staging'          => 'WPF_Staging',
				'autopilot'        => 'WPF_Autopilot',
				'customerly'       => 'WPF_Customerly',
				'copper'           => 'WPF_Copper',
				'nationbuilder'    => 'WPF_NationBuilder',
				'groundhogg'       => 'WPF_Groundhogg',
				'mailjet'          => 'WPF_Mailjet',
				'sendlane'         => 'WPF_Sendlane',
				'getresponse'      => 'WPF_GetResponse',
				'mailpoet'         => 'WPF_MailPoet',
				'klaviyo'          => 'WPF_Klaviyo',
				'birdsend'         => 'WPF_BirdSend',
				'zerobscrm'        => 'WPF_ZeroBSCRM',
				'mailengine'       => 'WPF_MailEngine',
				'klick-tipp'       => 'WPF_KlickTipp',
				'sendfox'          => 'WPF_SendFox',
				'quentn'           => 'WPF_Quentn',
				// 'loopify'        => 'WPF_Loopify',
				'wp-erp'           => 'WPF_WP_ERP',
				'engagebay'        => 'WPF_EngageBay',
				'fluentcrm'        => 'WPF_FluentCRM',
				'growmatik'        => 'WPF_Growmatik',
				'highlevel'        => 'WPF_HighLevel',
				'emercury'         => 'WPF_Emercury',
				'fluentcrm-rest'   => 'WPF_FluentCRM_REST',
				'pulsetech'        => 'WPF_PulseTechnologyCRM',
				'autonami'         => 'WPF_Autonami',
				'bento'            => 'WPF_Bento',
				'dynamics-365'     => 'WPF_Dynamics_365',
				'groundhogg-rest'  => 'WPF_Groundhogg_REST',
				'moosend'          => 'WPF_MooSend',
				'constant-contact' => 'WPF_Constant_Contact',
				'pipedrive'        => 'WPF_Pipedrive',
				'engage'           => 'WPF_Engage',
			)
		);

	}

	/**
	 * Include required files
	 *
	 * @access private
	 * @return void
	 */

	private function init_includes() {

		// Functions.
		require_once WPF_DIR_PATH . 'includes/functions.php';

		// Settings.
		require_once WPF_DIR_PATH . 'includes/admin/class-staging-sites.php';
		require_once WPF_DIR_PATH . 'includes/admin/class-settings.php';
		require_once WPF_DIR_PATH . 'includes/admin/logging/class-log-handler.php';
		require_once WPF_DIR_PATH . 'includes/admin/class-batch.php';

		// CRM base class.
		require_once WPF_DIR_PATH . 'includes/crms/class-base.php';

		if ( is_admin() ) {
			require_once WPF_DIR_PATH . 'includes/admin/class-notices.php';
			require_once WPF_DIR_PATH . 'includes/admin/admin-functions.php';
			require_once WPF_DIR_PATH . 'includes/admin/class-upgrades.php';
		}

		// Plugin updater.

		if ( $this->is_full_version() ) {
			include WPF_DIR_PATH . 'includes/admin/class-updater.php';
		} else {
			require_once WPF_DIR_PATH . 'includes/admin/class-lite-helper.php';
		}

	}

	/**
	 * Includes classes applicable for after the connection is configured
	 *
	 * @access private
	 * @return void
	 */

	private function includes() {

		require_once WPF_DIR_PATH . 'includes/class-user.php';
		require_once WPF_DIR_PATH . 'includes/class-lead-source-tracking.php';
		require_once WPF_DIR_PATH . 'includes/class-ajax.php';
		require_once WPF_DIR_PATH . 'includes/class-access-control.php';
		require_once WPF_DIR_PATH . 'includes/class-auto-login.php';
		require_once WPF_DIR_PATH . 'includes/admin/gutenberg/class-gutenberg.php';
		require_once WPF_DIR_PATH . 'includes/admin/class-admin-interfaces.php';

		// Shortcodes.
		if ( ! is_admin() && self::$instance->settings->get( 'connection_configured' ) ) {
			require_once WPF_DIR_PATH . 'includes/class-shortcodes.php';
		}

		// Admin bar tools.
		if ( ! is_admin() && self::$instance->settings->get( 'enable_admin_bar' ) ) {
			require_once WPF_DIR_PATH . 'includes/admin/class-admin-bar.php';
		}

		// Incoming webhooks handler.
		if ( $this->is_full_version() ) {
			require_once WPF_DIR_PATH . 'includes/integrations/class-forms-helper.php';
			require_once WPF_DIR_PATH . 'includes/class-api.php';
		}

	}



	/**
	 * Initialize the CRM object based on the currently configured options
	 *
	 * @return object CRM Interface
	 */
	public function init_crm() {

		self::$instance->crm      = new WPF_CRM_Base();
		self::$instance->crm_base = self::$instance->crm; // backwards compatibility with pre 3.40 integrations.

		do_action( 'wpf_crm_loaded', self::$instance->crm );

		return self::$instance->crm;

	}

	/**
	 * Fires when WP Fusion has loaded.
	 *
	 * When developing addons, use this hook to initialize any functionality
	 * that depends on WP Fusion.
	 *
	 * @since 3.37.14
	 *
	 * @link  https://wpfusion.com/documentation/actions/wp_fusion_init/
	 */
	public function init() {

		/**
		 * Init CRM.
		 *
		 * Indicates that the CRM has been set up and allows accessing the CRM
		 * or modifying it by reference.
		 *
		 * @since 3.37.14
		 * @since 3.38.24 CRM is now passed by reference because it's cooler.
		 *
		 * @param WPF_* object  The CRM class.
		 */

		do_action_ref_array( 'wp_fusion_init_crm', array( &self::$instance->crm ) );

		/**
		 * Init.
		 *
		 * WP Fusion is ready.
		 *
		 * @since 3.37.14
		 *
		 * @link  https://wpfusion.com/documentation/actions/wp_fusion_init/
		 */

		do_action( 'wp_fusion_init' );
	}

	/**
	 * Includes plugin integrations after all plugins have loaded
	 *
	 * @access private
	 * @return void
	 */

	public function integrations_includes() {

		// Integrations base.
		require_once WPF_DIR_PATH . 'includes/integrations/class-base.php';

		// Integrations autoloader.

		foreach ( wp_fusion()->get_integrations() as $filename => $dependency_class ) {

			$filename = sanitize_file_name( $filename );

			if ( class_exists( $dependency_class ) || function_exists( $dependency_class ) ) {

				if ( file_exists( WPF_DIR_PATH . 'includes/integrations/class-' . $filename . '.php' ) ) {
					require_once WPF_DIR_PATH . 'includes/integrations/class-' . $filename . '.php';
				}
			}
		}

	}

	/**
	 * Includes theme integrations after all theme has loaded
	 *
	 * @access private
	 * @return void
	 */

	public function integrations_includes_theme() {

		// Integrations base.
		require_once WPF_DIR_PATH . 'includes/integrations/class-base.php';

		// Integrations autoloader.
		foreach ( wp_fusion()->get_integrations_theme() as $filename => $dependency_class ) {

			$filename = sanitize_file_name( $filename );

			if ( class_exists( $dependency_class ) || function_exists( $dependency_class ) ) {

				if ( file_exists( WPF_DIR_PATH . 'includes/integrations/class-' . $filename . '.php' ) ) {
					require_once WPF_DIR_PATH . 'includes/integrations/class-' . $filename . '.php';
				}
			}
		}

	}

	/**
	 * Load internationalization files
	 *
	 * @access public
	 * @return void
	 */

	public function load_textdomain() {

		load_plugin_textdomain( 'wp-fusion', false, 'wp-fusion/languages' );

	}


	/**
	 * Check to see if this is WPF Lite or regular
	 *
	 * @access public
	 * @return bool
	 */

	public function is_full_version() {

		$integrations = $this->get_integrations();

		if ( ! empty( $integrations ) ) {
			return true;
		} else {
			return false;
		}

	}

	/**
	 * Set up EDD updater
	 *
	 * @access public
	 * @return void
	 */

	public function updater() {

		// To support auto-updates, this needs to run during the wp_version_check cron job for privileged users.
		$doing_cron = defined( 'DOING_CRON' ) && DOING_CRON;

		if ( ! current_user_can( 'manage_options' ) && ! $doing_cron ) {
			return;
		}

		$license_key    = $this->settings->get( 'license_key' );
		$license_status = $this->settings->edd_check_license( $license_key );

		if ( 'valid' === $license_status ) {

			// setup the updater
			$edd_updater = new WPF_Plugin_Updater(
				WPF_STORE_URL,
				__FILE__,
				array(
					'version'   => WP_FUSION_VERSION,      // current version number.
					'license'   => $license_key,           // license key.
					'item_name' => 'WP Fusion',            // name of this plugin.
					'author'    => 'Very Good Plugins',    // author of this plugin.
				)
			);

		} elseif ( 'error' === $license_status ) {

			global $pagenow;

			if ( 'plugins.php' === $pagenow ) {

				add_action( 'after_plugin_row_' . WPF_PLUGIN_PATH, array( self::$instance, 'wpf_update_message_error' ), 10, 3 );

			}
		} else {

			global $pagenow;

			if ( 'plugins.php' === $pagenow ) {

				add_action( 'after_plugin_row_' . WPF_PLUGIN_PATH, array( self::$instance, 'wpf_update_message' ), 10, 3 );

			}
		}

	}

	/**
	 * Display update message
	 *
	 * @access public
	 * @return void
	 */

	public function wpf_update_message( $plugin_file, $plugin_data, $status ) {

		echo '<tr class="plugin-update-tr active">';
		echo '<td colspan="4" class="plugin-update colspanchange">';
		echo '<div class="update-message notice inline notice-warning notice-alt wpf-update-message">';
		echo '<p>Your WP Fusion License key is currently inactive or expired. <a href="' . get_admin_url() . './options-general.php?page=wpf-settings#setup">Activate your license key</a> or <a href="https://wpfusion.com/" target="_blank">purchase a license</a> to enable automatic updates and support.</p>';
		echo '</div>';
		echo '</td>';
		echo '</tr>';

	}

	/**
	 * Display license check error message
	 *
	 * @access public
	 * @return void
	 */

	public function wpf_update_message_error( $plugin_file, $plugin_data, $status ) {

		echo '<tr class="plugin-update-tr active">';
		echo '<td colspan="4" class="plugin-update colspanchange">';
		echo '<div class="update-message notice inline notice-warning notice-alt">';
		echo '<p>WP Fusion is unable to contact the update servers. Your web host may be running outdated software. Please <a href="https://wpfusion.com/support/contact/" target="_blank">contact support</a> for additional assistance.</p>';
		echo '</div>';
		echo '</td>';
		echo '</tr>';

	}

	/**
	 * Returns error message and deactivates plugin when error returned.
	 *
	 * @access public
	 * @return mixed error message.
	 */

	public function php_version_notice() {

		echo '<div class="notice notice-error">';
		echo '<p>';
		printf( esc_html__( 'Heads up! WP Fusion requires at least PHP version %1$s in order to function properly. You are currently using PHP version %2$s. Please update your version of PHP, or contact your web host for assistance.', 'wp-fusion' ), esc_html( WPF_MIN_PHP_VERSION ), esc_html( phpversion() ) );
		echo '</p>';
		echo '</div>';

	}


	/**
	 * Display a warning when the full version of WPF is active
	 *
	 * @access public
	 * @return mixed error message.
	 */
	public function full_version_notice() {

		echo '<div class="notice notice-error">';
		echo '<p>';
		esc_html_e( 'Heads up: It looks like you\'ve installed the full version of WP Fusion. We have deactivated WP Fusion Lite for you, and copied over all your settings. You can go ahead and delete the WP Fusion Lite plugin 🙂', 'wp-fusion' );
		echo '</p>';
		echo '</div>';

	}



}


/**
 * The main function responsible for returning the one true WP Fusion
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $wpf = wp_fusion(); ?>
 *
 * @return object The one true WP Fusion Instance
 */

if ( ! function_exists( 'wp_fusion' ) ) {

	function wp_fusion() {
		return WP_Fusion::instance();
	}

	// Get WP Fusion running.
	wp_fusion();

}
