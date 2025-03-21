<?php

/**
 * Set e-signature cookie 
 * @param type $name
 * @param type $value
 * @param type $expire
 * @param type $secure
 */
if (!function_exists('esig_setcookie')) {

    function esig_setcookie($name, $value, $expire = 0, $secure = false) {
        if (!headers_sent()) {
            setcookie($name, $value, time() + $expire, COOKIEPATH, COOKIE_DOMAIN);
        } elseif (defined('WP_DEBUG') && WP_DEBUG) {
            headers_sent($file, $line);
        }
    }

}

/**
 * Unset esignature cookie 
 * @param type $name
 * @param type $secure
 */
if (!function_exists('esig_unsetcookie')) {

    function esig_unsetcookie($name, $cookiepath = false, $secure = false) {
        // Clear cookie
        if ($cookiepath) {
            setcookie($name, null, time() - YEAR_IN_SECONDS, $cookiepath);
        } else {
            setcookie($name, null, time() - YEAR_IN_SECONDS);
        }
    }

}

/**
 * Get User IP
 *
 * Returns the IP address of the current visitor
 *
 * @since 1.4.0
 * @return string $ip User's IP address
 */
if (!function_exists('esig_get_ip')) {

    function esig_get_ip() {

        $ip = '127.0.0.1';

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            //check ip from share internet
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {

            if (strpos($_SERVER['HTTP_X_FORWARDED_FOR'], ',') !== false) {

                $ipList = array_values(array_filter(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])));
                $clientIp = current($ipList);
                if (filter_var($clientIp, FILTER_VALIDATE_IP)) {
                    $ip = $clientIp;
                }
            } else {
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            }
            //to check ip is pass from proxy

        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return apply_filters('esig_get_ip', $ip);
    }

}


if (!function_exists('ESIG_GET')) {

    function ESIG_GET($key, $array = false) {

        $value = isset($_GET[$key]) ? $_GET[$key] : null;

        if ($array && is_array($value)) {
            return array_map(function ($element) {
                return htmlspecialchars(strip_tags($element));
            }, $value);
        }

        if (!$array && !is_array($value) && $value) {
            return htmlspecialchars(strip_tags($value));
        }

        return false;
    }

}

if (!function_exists('ESIG_POST')) {

    function ESIG_POST($key, $array = false) {
       
        $value = isset($_POST[$key]) ? $_POST[$key] : null;
        
        // Sanitize the value
        $sanitized_value = '';

        if (is_array($value)) {
            $sanitized_value = array_map('sanitize_text_field', $value);
        } else {
            $sanitized_value = sanitize_text_field($value);
        }

        // Array sanitizing
        if (is_array($sanitized_value)) {

            array_walk($sanitized_value, function (&$value, $key) {
                $value = sanitize_text_field($value);
            });

            return $sanitized_value;
        }

        if($sanitized_value) {
           return wp_kses_post($sanitized_value);
        }

        return false;
    }

}

if (!function_exists('esigRequest')) {

    function esigRequest($key) {
        $data = isset($_REQUEST[$key]) ? $_REQUEST[$key] : null;
        // if data is array then sanitize it
        if(is_array($data))
        {
            return array_map(function ($element) {
                return sanitize_text_field(htmlspecialchars(strip_tags($element)));
            }, $data);
        }

        return sanitize_text_field(htmlspecialchars(strip_tags($data)));
    }

}


if (!function_exists('esigget')) {

    function esigget($name, $array = null) {

        if (!isset($array)) {
            // check for wpesign encoding in get method . 
            if(ESIG_GET("wpesig"))
            {
                 $esigData =  WP_E_Invite::urlDecode(ESIG_GET("wpesig"));
                
                 if(is_array($esigData))
                 {
                    if (isset($esigData[$name])) {
                        return sanitize_text_field(wp_unslash($esigData[$name]));
                    } 
                 }
            }
            return ESIG_GET($name);
        }

        if (is_array($array)) {
            if (isset($array[$name])) {
                return wp_unslash($array[$name]);
            }
            return false;
        }

        if (is_object($array)) {
            if (isset($array->$name)) {
                return wp_unslash($array->$name);
            }
            return false;
        }

        return false;
    }

}



if (!function_exists('ESIG_SEARCH_GET')) {

    function ESIG_SEARCH_GET($key) {
        $data = isset($_REQUEST[$key]) ? $_REQUEST[$key] : null;
        // data is null return false
        if (!$data) {
            return false;
        }
        return sanitize_text_field(htmlspecialchars(strip_tags($data)));
    }

}

if (!function_exists('esigpost')) {

    function esigpost($key, $array = false) {
        return ESIG_POST($key, $array);
    }

}

if (!function_exists('esig_unslash')) {

    function esig_unslash($result) {
        return esc_attr(wp_unslash($result));
    }

}

if (!function_exists('has_esig_shortcode')) {

    function has_esig_shortcode($page_id) {

        $post = get_post($page_id);
        if (!isset($post)) {
            return false;
        }
        //$content = apply_filters('the_content', $post->post_content); 
        if (!has_shortcode($post->post_content, "wp_e_signature_sad") && !has_shortcode($post->post_content, "wp_e_signature")) {
            return false;
        }
        return true;
    }

}

if (!function_exists('esig_is_plugin_active')) {

    function esig_is_plugin_active($plugin) {
        $network_active = false;
        if (is_multisite()) {
            $plugins = get_site_option('active_sitewide_plugins');
            if (isset($plugins[$plugin])) {
                $network_active = true;
            }
        }
        return in_array($plugin, get_option('active_plugins')) || $network_active;
    }

}

if (!function_exists('esig_create_nonce')) {

    function esig_create_nonce($nonce) {
        return wp_create_nonce('esig-nonce-all' . $nonce);
    }

}

if (!function_exists('esig_verify_nonce')) {

    function esig_verify_nonce($value, $nonce) {

        // wp nonce verification does not work for non logged in user with page cache and woocommerce. 
        if (function_exists('wc')) {
            global $woocommerce;
             if (!is_null(WC()->session)) {
                return true;
            }
        }
        if (wp_verify_nonce($value, 'esig-nonce-all' . $nonce)) {
            return true;
        }
        return false;
    }

}

if (!function_exists('esig_verify_not_spam')) {

    function esig_verify_not_spam() {
        
        
        $check_spam= apply_filters("esig_check_spam",true);
        
        if(!$check_spam){
            return true;
        }
        
        $sp = esigpost('esig_sp_url');
        if (empty($sp)) {
            return true;
        }
        return false;
    }

}




if (!function_exists('esigGetVersion')) {

    function esigGetVersion() {
        if (!function_exists("get_plugin_data"))
            require ABSPATH . 'wp-admin/includes/plugin.php';

        $plugin_data = get_plugin_data(ESIGN_PLUGIN_FILE);
        $plugin_version = $plugin_data['Version'];
        return $plugin_version;
    }

}

if (!function_exists('esigStripTags')) {

    function esigStripTags($str, $tag) {

        $str = preg_replace('/<' . $tag . '[^>]*>/i', '', $str);

        $str = preg_replace('/<\/' . $tag . '>/i', '', $str);

        return $str;
    }

}

if (!function_exists('ESIG_COOKIE')) {

    function ESIG_COOKIE($key) {
        return filter_input(INPUT_COOKIE, $key);
    }

}

if (!function_exists('getAddonVersion')) {

    function getAddonVersion($path) {
        if (!function_exists("get_plugin_data"))
            require ABSPATH . 'wp-admin/includes/plugin.php';

        $plugin_data = get_plugin_data($path);
        $plugin_version = $plugin_data['Version'];
        return $plugin_version;
    }

}

if (!function_exists('esigHtml')) {

    function esigHtml($value) {
        $value = stripslashes($value);
        return esc_html($value);
    }

}


if (!function_exists('esig_is_func_disabled')) {

    function esig_is_func_disabled($function) {
        $disabled = explode(',', ini_get('disable_functions'));
        return in_array($function, $disabled);
    }

}


if (!function_exists('esig_strip_shortcodes')) {

    function esig_strip_shortcodes($content) {

        $shortcode_tags = array();

        if (false === strpos($content, '[')) {
            return $content;
        }

       
        // Find all registered tag names in $content.
        preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $content, $matches);
        $tags_to_remove = array_keys($shortcode_tags);
       
        
        $tags_to_remove = array();
        foreach($matches[1] as $value){
            if(strpos($value, 'esig') === false){                
                $tags_to_remove[] = $value;
            }            
        }
        

        /**
         * Filters the list of shortcode tags to remove from the content.
         *
         * @since 4.7.0
         *
         * @param array  $tag_array Array of shortcode tags to remove.
         * @param string $content   Content shortcodes are being removed from.
         */
        $tags_to_remove = apply_filters('esig_strip_shortcodes_tagnames', $tags_to_remove);

        $tagnames = array_intersect($tags_to_remove, $matches[1]);

        if (empty($tagnames)) {
            return $content;
        } else {
            $adminName = WP_E_Sig()->user->getUserFullName();
            $msg = sprintf(__("Hey %s! :-) Looks like you’re trying to insert a form into your document, which will break your document (oh no!). The proper way to make this work is to create a page and put your form on that page. Then, come back to this document and ‘call’ your form information into the document using Singer Input Fields. Then, it will work properly (yay!).", "esig"), $adminName);
            WP_E_Notice::instance()->set("error", $msg);
        }

        $content = do_shortcodes_in_html_tags($content, true, $tagnames);

        $pattern = get_shortcode_regex($tagnames);
        $content = preg_replace_callback("/$pattern/", 'strip_shortcode_tag', $content);

        // Always restore square braces so we don't break things like <!--[if IE ]>
        $content = unescape_invalid_shortcodes($content);

        return $content;
    }

}

/**
 *  Remove all shortcodes from basic document 
 */
if (!function_exists('esig_strip_other_shortcodes')) {

    function esig_strip_other_shortcodes($content, $document_Type = "normal") {


        global $shortcode_tags;

        $tags_to_keep = array("esigget", "esigtextfield", "esigtextarea", "esigtodaydate", "esigdatepicker", "esigradio", "esigcheckbox", "esigdropdown", "esigfile",
            "esigcf7", "esigformidable", "esigninja", "esigcaldera", "esigwpform", "esig-woo-order-details", "esig-edd-order-details", "esigtemptextfield",
            "esigtemptextarea", "esigtempdatepicker","esigtempfile","esigtemptodaydate", "esigtempradio", "esigtempcheckbox", "esigtempdropdown", "esiggravity","esig-page-break");

        if (false === strpos($content, '[')) {
            return $content;
        }

        // Find all registered tag names in $content.
        preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $content, $matches);
        /**
         * Filters the list of shortcode tags to remove from the content.
         *
         * @since 4.7.0
         *
         * @param array  $tag_array Array of shortcode tags to remove.
         * @param string $content   Content shortcodes are being removed from.
         */
        $tags_to_keep = apply_filters('esig_donot_remove_shortcode_name', $tags_to_keep);

        $tagnames = $matches[1];

        if (empty($tagnames)) {
            return $content;
        }

        $tags_to_remove = array();

        foreach ($tagnames as $key => $name) {

            if (!shortcode_exists($name) && !in_array($name, $tags_to_keep)) {
                $tags_to_remove[] = $name;
                unset($tagnames[$key]);
                continue;
            }

            if (in_array($name, $tags_to_keep)) {
                unset($tagnames[$key]);
            }
        }

        $adminName = WP_E_Sig()->user->getUserFullName();

        // striping non exist shortcode 
        if (!empty($tags_to_remove)) {

            if ($document_Type == "normal") {
               
                $msg = sprintf(__("Hey %s! :-) Looks like you inserted a non e-signature shortcode into your document, which is not active and has been removed automatically.", "esig"), $adminName);
                WP_E_Notice::instance()->set("error striped", $msg);
            }

            $content = do_shortcodes_in_html_tags($content, true, $tags_to_remove);

            //first strip non shortcode tag. 
            $nonPattern = get_shortcode_regex($tags_to_remove);
            $content = preg_replace_callback("/$nonPattern/", 'strip_shortcode_tag', $content);
        }

        if (!empty($tagnames)) {
            if ($document_Type == "normal") {
                
                $msg = sprintf(__("Hey %s! :-) Looks like you inserted a non e-signature shortcode into your document, which we have rendered and stored into database permanently.", "esig"), $adminName);
                WP_E_Notice::instance()->set("error stored", $msg);
            }
        } else {
            return $content;
        }
        // rendering non e-signature shortcode. 
        $content = do_shortcodes_in_html_tags($content, true, $tagnames);
        $pattern = get_shortcode_regex($tagnames);
        
        $content = preg_replace_callback("/$pattern/", 'do_shortcode_tag', $content);
        // Always restore square braces so we don't break things like <!--[if IE ]>
        $content = unescape_invalid_shortcodes($content);

        return $content;
    }

}

if (!function_exists('esigAddQueryString')) {
    function esigAddQueryString($string=array())
    {
        $oldQueryString = $_SERVER['QUERY_STRING'];
        parse_str($oldQueryString, $urlArray);
        $currentArray = wp_parse_args($string, $urlArray);
        return http_build_query($currentArray);
    }

}

