<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Esign_licenses extends WP_E_Model {

    public static $approveme_url     = "https://www.approveme.com/";
    public static $approveme_item_id = '2660';

    public function __construct() {
        parent::__construct();
    }

    public static function wpRemoteRequest($params, $url = false) {

        if (!$url) {
            $url = self::$approveme_url;
        }

        $https = $http = $url;

        if (wp_http_supports(array('ssl'))) {
            $https = set_url_scheme($https, 'https');
        }

        $response = wp_remote_post($https, $params);

        if (is_wp_error($response)) {
            error_log(__FILE__ . " WP E-Signature license activation error " . $response->get_error_code() . " : " . $response->get_error_message());
            $response = wp_remote_post($http, $params);
        }

        if (is_wp_error($response)) {
            error_log(__FILE__ . " WP E-Signature license activation error " . $response->get_error_code() . " : " . $response->get_error_message());
        }

        return $response;
    }

    public static function is_license_active() {

        $result = self::check_license();

        if (!is_object($result)) {
            return false;
        }

        if ($result->license == "valid" || $result->license == 'inactive') { // Inactive means it's a valid license, just not on any sites
            return true;
        } else {
            return false;
        }
    }

    public static function getLicenseStatus(){

        global $esigLicenseStatus;
        if(is_null($esigLicenseStatus))
        {
           $esigLicenseStatus = WP_E_Sig()->setting->get_generic("esig_wp_esignature_license_active");
        }
        return $esigLicenseStatus;
    }

    public static function is_valid_license() {
        if (self::getLicenseStatus() == "valid") {
            return true;
        } 
        return false;
    }

    public static function is_license_valid() {

        if (self::getLicenseStatus() == "valid") {
            return true;
        }
        return false;
    }

    public static function is_business_license() {

        $licenseType = self::get_license_type();
        if ($licenseType == 'business-license') {
            return true;
        } elseif ($licenseType == "Business License") {
            return true;
        } else {
            return false;
        }
    }

    public static function get_site_url() {
        return home_url('', 'http');
    }

    public static function get_license_key() {

        global $esigLicenseKey ;
        if (is_null($esigLicenseKey)) {
            $esigLicenseKey = WP_E_Sig()->setting->get_generic("esig_wp_esignature_license_key");
        }
        return $esigLicenseKey;

    }

    public static function get_license_name() {
        return WP_E_Sig()->setting->get_generic("esig_wp_esignature_license_name");
    }

    public static function get_license_type() {
        return WP_E_Sig()->setting->get_generic("esig_wp_esignature_license_type");
    }

    public static function get_serverLicense_type($addonList)
    {
        return  esigget("license_type", esigget("license_info",$addonList));
    }

    public static function get_expire_date() {
        return WP_E_Sig()->setting->get_generic("esig_wp_esignature_license_expires");
    }

    public static function check_license() {
        global $esign_licenses_check_license;

        // Allows us to only make one HTTP request per page load.
        if (!is_null($esign_licenses_check_license)) {

            return $esign_licenses_check_license;
        }

        $cached_data = json_decode(WP_E_Sig()->setting->get_generic('esig_license_info'));
        if (!empty($cached_data) && current_time('timestamp') < $cached_data->timeout) {
            $esign_licenses_check_license = $cached_data->license_info;
        } else { 
            $api_params = array(
                'edd_action' => 'check_license',
                'item_id' => self::$approveme_item_id,
                'item_name' => self::get_license_name(),
                'license' => self::get_license_key(),
                'url' => self::get_site_url(),
            );

            $request = self::wpRemoteRequest(array('timeout' => 15, 'sslverify' => false, 'body' => $api_params));

            if (!is_wp_error($request)) {

                $esign_licenses_check_license = $esign_licenses_check_license = json_decode(wp_remote_retrieve_body($request));

                // Account for a non-entered license key
                if ( is_null( $esign_licenses_check_license ) ) {
                    return false;
                }

                $cache_data = array(
                    'license_info' => $esign_licenses_check_license,
                    'timeout' => current_time('timestamp') + DAY_IN_SECONDS, // Let's just check license info once a day to save the server
                );

                WP_E_Sig()->setting->set_generic('esig_license_info', json_encode($cache_data));

                $allowed_status = array('active', 'valid');
                $esig_status = in_array($esign_licenses_check_license->license, $allowed_status) ? 'valid' : 'invalid';
                WP_E_Sig()->setting->set_generic("esig_wp_esignature_license_active", $esig_status);
            } else {
                $esign_licenses_check_license = false;
            }
        }

        return $esign_licenses_check_license;
    }

    public static function invalidLicense() {
        $license_info = self::check_license();
        $invalid_status = array('revoked', 'expired');
        if (in_array($license_info->license, $invalid_status)) {
            return true;
        }
        return false;
    }

    public static function latestVersion() {

        if ($esig_license_check = wp_cache_get('esig_latest_version_info', 'esig_latest_version_info')) {
            return $esig_license_check;
        }

        $api_params = array(
            'edd_action' => 'get_version',
            'item_id' => self::$approveme_item_id,
            'item_name' => self::get_license_name(),
            'license' => self::get_license_key(),
            'url' => self::get_site_url(),
        );

        $request = self::wpRemoteRequest(array('timeout' => 15, 'sslverify' => false, 'body' => $api_params));

        if (!is_wp_error($request)) {
            $request = json_decode(wp_remote_retrieve_body($request));
            // setting cache
            wp_cache_set('esig_latest_version_info', $request, 'esig_latest_version_info');
        } else {
            return false;
        }

        return $request;
    }

    public static function esig_super_admin($echo = false) {
        $super_admin_id = WP_E_Sig()->user->esig_get_super_admin_id();
        $user_details = WP_E_Sig()->user->getUserByWPID($super_admin_id);
        if ($echo)
            echo $user_details->first_name;

        return $user_details->first_name;
    }

    public static function get_strip_license_key($status) {

        $valid_status = array('valid', 'active');

        $license_key  = self::get_license_key();
        if ( in_array( $status, $valid_status ) && ! empty( $license_key ) ) {
            return str_repeat( '*', ( strlen( $license_key ) - 4 ) ) . substr( $license_key, -4, 4 );

        } else {
            return false;
        }
    }

    public static function is_readonly($status) {
        if ($status == "valid") {
            return "readonly";
        } else {
            return false;
        }
    }

    public static function get_renew_button() {

        return '<a href="' . self::get_renewal_link() . '" class="esig-btn-pro esig-renew-button" target="_blank">Renew Your License</a>';
    }

    public static function get_renewal_link() {
        return self::$approveme_url . 'checkout/?edd_license_key=' . self::get_license_key() . '&download_id=' . self::$approveme_item_id;
    }

    public static function get_license_form($result) {


        $license = esigget("license",$result);
        $expires = esigget("expires",$result);
        $html = '';

        $button_text = '';
        $licenseKey = self::get_license_key();
        if ( ! empty( $licenseKey ) && ($license == "valid" || $license == "inactive" || $license == "active" ) ) {

            $html .= '<tr>
        <th><label for="license_key" id="license_key_label"> ' . __('License Status', 'esign') . '</label></th>
        <td> <span class="license-active-status">  ' . __('ACTIVE', 'esign') . ' </span> &nbsp;&nbsp;&nbsp;–&nbsp;&nbsp;  ' . __('You are receiving updates.', 'esign') . ' </td><tr>';

            //$html .='<tr><td colspan="2"><span class="license-active-msg"> active msg</span> </td></tr>' ;
            $button_text = ' <input type="submit" class="button-appme button" name="esig_wp_esignature_license_key_deactivate" value="Deactivate License">';
        } elseif ($license == "expired") {

            $html .= '<tr><td colspan="2"><div class="esig-add-on-block esig-pro-pack open">

                <h3>' . __('URGENT: Your License has Expired!', 'esign') . '</h3>
                <p style="display:block;"> ' . __('A valid WP E-Signature license is required for access to critical updates and support. Without a license you are putting you and your signers at risk. To protect yourself and your documents please update your license.', 'esign') . '</p>
                ' . self::get_renew_button() . '
            </div><div class="license-expired-msg"> <strong>' . __('Your are not receiving critical updates.  WP E-Signature license expired on ', 'esign') . '' . date(get_option('date_format'), strtotime($expires)) . '.</strong></div></td></tr>';

            $html .= '<tr>
        <th><label for="license_key" id="license_key_label"> ' . __('License Status', 'esign') . '</label></th>
        <td> <span class="license-inactive-status">' . strtoupper($license) . '</span>' . __(' - You are ', 'esign') . '<strong>' . __('not', 'esign') . '</strong> ' . __('receiving updates and your signers are currently at risk!', 'esign') . '</td><tr>';



            $button_text = ' <input type="submit" class="button-appme button" name="esig_wp_esignature_license_key_activate" value="Activate License">';
        } else {

            $html .= '<tr><td colspan="2"><div class="license-inactive-msg"> ' . __('Please enter your valid', 'esign') . ' <a href="https://www.approveme.com/wp-e-signature">' . __('WP E-Signature', 'esign') . '</a> ' . __('license below.  If you forgot your license you can login to', 'esign') . ' <a href="https://www.approveme.com/profile">' . __('your account here', 'esign') . '</a> ' . __('or', 'esign') . ' <a href="https://www.approveme.com/email-limited-pricing/">' . __('purchase a license', 'esign') . '</a> ' . __('here.', 'esign') . '</div></td></tr>';

                // sets no license when there is no license status returns from server.
            $licensStatus =($license)? $license : "No License" ;

            $html .= '<tr>
        <th><label for="license_key" id="license_key_label"> ' . __('License Status', 'esign') . '</label></th>
        <td> <span class="license-inactive-status">' . strtoupper($licensStatus) . '</span>' . __(' - You are not receiving updates and your signers are currently at risk!', 'esign') . '</td><tr>';


            $button_text = ' <input type="submit" class="button-appme button" name="esig_wp_esignature_license_key_activate" value="Activate License">';
        }


        $html .= '<tr class="esig-settings-wrap">
        <th><label for="license_key" id="license_key_label"> License Key <span class="description"> (required)</span></label></th>
        <td><input type="text" name="esig_wp_esignature_license_key' . '" id="first_name" value="' . self::get_strip_license_key($license) . '" class="regular-text" ' . self::is_readonly($license) . ' /> ' . $button_text . '</tr>';

        if ($license == "valid") {
            if ($expires == 'lifetime') {
                $html .= __('<tr><td colspan="3">You are awesome! You have a forever license with no expiration.  </td></tr>', 'esign');
            } else {
                $html .= sprintf(__('<tr><td colspan="3">Your e-signature license will expire on %s </td></tr>', 'esign'), $expires);
            }
        }

        echo $html;
    }

}
