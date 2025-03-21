<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>



<?php
$this->setting = new WP_E_Setting();
if (array_key_exists('messages', $data)) {
    echo $data['messages'];
}
?>

<?php
$esig_update = isset($_GET['esig-update']) ? $_GET['esig-update'] : null;

if ($esig_update == "success") {
?>

    <div class="alert alert e-sign-alert esig-updated">
        <div class="title"></div>
        <p class="message"><?php _e('Hey there, congrats!  It looks like your recent E-Signature add-on update has been successful.', 'esig'); ?></p>
    </div>

<?php
}

$esig_permission = '';

if (!current_user_can('install_plugins')) {
?>

    <div class="alert alert e-sign-alert e-sign-red-alert" style="padding: 5px;">
        <p class="message"><?php _e('You do not have sufficient permission to install plugins.', 'esig'); ?> </p>
    </div>
<?php
    $esig_permission = "onclick=\"javascript: return false ;\" install-permission=\"no\"";
}

?>

<h3><?php _e('Premium Add-on Extensions', 'esig'); ?></h3>

<p class="esig-add-on-description"><?php _e('Add-ons are customizable features that you can add or remove depending on your specific needs. Signing documents should only be as automated/customizable as you need it to be. Visit the Get More tab to see what else ApproveMe can do for you.', 'esig'); ?></p>

<?php
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'all';

$documentation_page = '';
$settings_page = '';


//

$this->model->esig_addons_tabs($tab);
echo '<div class="esig-add-ons-wrapper">';
// tab content start here
if ($tab == 'all') {

    if (Esign_licenses::is_license_active()) {
        $license_key = 'yes';
        $all_addons_list = $this->model->esig_get_premium_addons_list();
    } else {

        $license_key = 'no';
        $all_addons_list = $this->model->esig_get_addons_list();
    }

    if ($all_addons_list) {

        $total = 0;

        $all_addon_install = true;

        $all_install = array();

        if ($license_key == "no") {
?>

            <div class="esig-add-on-block esig-pro-pack open">

                <h3><?php _e("A Valid E-Signature License is Required", "esig"); ?></h3>
                <p style="display:block;"><?php _e("A valid WP E-Signature license is required for access to critical updates and support. Without a license you are putting you and your signers at risk. To protect yourself and your documents please update your license.", "esig"); ?></p><a href="https://www.approveme.com/wp-e-signature/?add-ons-plugin" class="esig-btn-pro" target="_blank"><?php _e("Purchase a license", "esig"); ?> </a>
            </div>

        <?php
        }



        $business_pack_option = $this->model->one_click_installation_option($all_addons_list);

        if (empty($business_pack_option)) {
            WP_E_Sig()->common->force_to_check_update();
            WP_E_Sig()->common->esign_check_update();
            $business_pack_option = $this->model->one_click_installation_option($all_addons_list);
        }

        if ($business_pack_option && !Esig_Addons::is_updates_available()) {
        ?>

            <div class="esig-add-on-block esig-pro-pack open" id="esig-all_install">
                <?php _e(' <h3>Save Time...Install everything with one click</h3>
					                    <p style="display:block;">Since you have access to the Buisness Pack you can save time by installing
                                        all add-ons at once .
                                        Please Note: The installation process can take few minutes to complete.</p>', 'esig'); ?>
                <a class="esig-btn-pro" id="esig-install-alladdons" <?php echo $esig_permission;  ?> href="<?php echo $business_pack_option ?>"><?php _e('Install all Add-ons Now', 'esig'); ?></a>
            </div>

        <?php
        } elseif ($business_pack_option && Esig_Addons::is_updates_available()) {
        ?>

            <div class="esig-add-on-block esig-pro-pack open" id="esig-all_install">
                <?php _e(' <h3>Save Time...Update everything with one click</h3>
					                    <p style="display:block;">Since you have access to the Buisness Pack you can save time by updating
                                        all add-ons at once .
                                        Please Note: The Update process can take few minutes to complete.</p>', 'esig'); ?>
                <a class="esig-btn-pro" id="esig-update-alladdons" <?php echo $esig_permission;  ?> href="<?php echo $business_pack_option ?>"><?php _e('Update all Add-ons Now', 'esig'); ?></a>
            </div>
            <?php
        }

        $all_addons = Esig_Addons::esig_object_sort($all_addons_list);

        foreach ($all_addons as $addonlist => $addons) {

            $update_available = '';


            if (WP_E_Addon::is_business_pack_list($addons)) {
                continue;
            }

            if ($addonlist == "license_type") continue;

            if ($addonlist == "license_info") continue;

            if ($addonlist == "esig-price") continue;

            if ($addons->addon_name != 'WP E-Signature' && $addons->addon_name != 'WPESignature') {


                $plugin_root_folder = trim($addons->download_name, ".zip");

                $plugin_file = $this->model->esig_get_addons_file_path($plugin_root_folder);


                $esig_update_link = '';
                if ($license_key == 'no') {

                    $price = isset($price) ? $price : 197;

                    $esig_action_link = '<div class="esig-add-on-actions"><div class="esig-add-on-buy-now"><a href="https://www.approveme.com/wp-e-signature/?add-ons-plugin" target="_blank" class="eisg-addons-upgrade">' . __('Upgrade Now', 'esig') . '</a></div></div>';
                } elseif ($plugin_file) {

                    $plugin_data = Esig_Addons::get_addon_data(Esig_Addons::get_installed_directory($plugin_file) . $plugin_file);

                    $plugin_name = $plugin_data['Name'];
                    $update_available = '';
                    $settings_page = '';
                    $documentation_page = '<span class="esig-add-on-author"><a href="' . $addons->download_page_link . '" target="_blank">' . __('Documentation', 'esig') . '</a></span>';
                    if (empty($documentation_page) && !empty($plugin_data['Documentation'])) {
                        $documentation_page = '<span class="esig-add-on-author"><a href="' . $plugin_data['Documentation'] . '" target="_blank">' . __('Documentation', 'esig') . '</a></span>';
                    }
                    if (Esig_Addons::is_enabled($plugin_file)) {
                        $esig_name = preg_replace('/[^a-zA-Z0-9_\s]/', '', str_replace(' ', '_', "WP E-Signature - " . $addons->addon_name));



                        // settings page .

                        if (is_callable('esig_addon_setting_page_' . str_replace('-', '_', $plugin_root_folder))) {
                            $settings_page = call_user_func('esig_addon_setting_page_' . str_replace('-', '_', $plugin_root_folder), $settings_page);
                        }

                        $esig_action_link = '<div class="esig-add-on-enabled"><a data-text-disable="Disable" data-text-enabled="Enabled" href="?page=esign-addons&tab=enable&esig_action=disable&plugin_url=' . urlencode($plugin_file) . '&plugin_name=' . $plugin_name . '" ' . $esig_permission . '>' . __('Enabled', 'esig') . '</a></div>';
                    } elseif (!Esig_Addons::is_enabled($plugin_file)) {
                        $esig_action_link = '<div class="esig-add-on-disabled"><a data-text-enable="Enable" data-text-disabled="Disabled" href="?page=esign-addons&tab=disable&esig_action=enable&plugin_url=' . urlencode($plugin_file) . '&plugin_name=' . $plugin_name . '" ' . $esig_permission . '>' . __('Disabled', 'esig') . '</a></div>';
                    }

                    if (version_compare($plugin_data['Version'], $addons->new_version, '<')) {
                        $update_available = __('Update Available', 'esig');
                        $esig_action_link = '<div class="esig-add-on-disabled"><a  href="?page=esign-addons&esig_action=update&download_url=' . WP_E_Addon::base64_url_encode($addons->download_link) . '&download_name=' . $plugin_file . '" ' . $esig_permission . ' class="eisg-addons-update">' . __('Update Now', 'esig') . '</a></div>';
                    }
                } else {
                    if ($addons->download_access == 'yes') {
                        // set all addon transients

                        $all_addon_install = false;

                        $all_install[$addons->download_name] = $addons->download_link;
                        if ($this->model->addonType($all_addons_list, "business_pack")) {
                            $esig_action_link = '<div class="esig-add-on-disabled"><a ' . $esig_permission . '  href="' . $business_pack_option . '" class="eisg-addons-install">' . __('Install Now', 'esig') . '</a></div>';
                        } else {
                            $esig_action_link = '<div class="esig-add-on-disabled"><a  ' . $esig_permission . ' href="?page=esign-addons&esig_action=install&download_url=' . WP_E_Addon::base64_url_encode($addons->download_link) . '&download_name=' . $addons->download_name . '" ' . $esig_permission . ' class="eisg-addons-install">' . __('Install Now', 'esig') . '</a></div>';
                        }

                        // $esig_action_link = '<div class="esig-add-on-disabled"><a  href="' . $business_pack_option .'" class="eisg-addons-install">' . __('Install Now', 'esig') . '</a></div>';
                        // $esig_action_link = '<div class="esig-add-on-disabled">'. WP_E_Addon::get_install_link($addons->download_link, $addons->download_name, $esig_permission) .'</div>';
                    } else {
                        $esig_action_link = '<div class="esig-add-on-actions"><div class="esig-add-on-buy-now"><a href="https://www.approveme.com/wp-e-signature/?add-ons-plugin" target="_blank" class="eisg-addons-upgrade">' . __('Upgrade Now', 'esig') . '</a></div></div>';
                    }
                }

                $total++;
            ?>


                <div class="esig-add-on-block">


                    <div class="esig-add-on-icon">
                        <div class="esig-image-wrapper">
                            <img src="<?php echo $addons->addon_image[0]; ?>" width="50px" height="50px" alt="">
                        </div>
                    </div>

                    <div class="esig-add-on-info">
                        <h4><a href="<?php echo $addons->download_page_link; ?>" target="_blank"><?php echo "WP E-Signature - " . $addons->addon_name; ?></a></h4>
                        <span class="esig-add-on-author"> <?php _e('by', 'esig'); ?> <a href="https://www.approveme.com/wp-e-signature" target="_blank"><?php _e('ApproveMe.com', 'esig'); ?></a></span>
                        <?php echo $documentation_page; ?>



                        <p class="esig-add-on-description"><?php echo $addons->addon_description; ?></p>
                    </div>

                    <div class="esig-add-on-actions">

                        <?php echo $esig_action_link; ?>
                        <?php echo $settings_page; ?>

                    </div>
                </div>


                <?php
            }
        } //foreach end here
        // setting transient for all addons array .
        set_transient('esig-all-addons-install', json_encode($all_install), 12 * HOUR_IN_SECONDS);


        if ($total == 0) {

            echo '<div> ' . _e('You have already installed all addons.', 'esig') . '</div>';
        }
    }
}
// all tab end here
// enable tab start here
if ($tab == "enable") {

    //$array_Plugins = get_plugins();
    $array_Plugins = Esig_Addons::get_all_addons();

    asort($array_Plugins);

    $total = 0;
    if (!empty($array_Plugins)) {

        foreach ($array_Plugins as $plugin_file => $plugin_data) {

            if (Esig_Addons::is_enabled($plugin_file)) {

                $plugin_name = $plugin_data['Name'];

                if (preg_match("/esig-/", $plugin_file)) {

                    if ($plugin_name != "WP E-Signature") {
                        $total++;

                        list($folder_name, $file_name) = explode('/', $plugin_file);

                        // $plugin_name= trim($plugin_name, "WP E-Signature");
                        $esig_name = preg_replace('/[^a-zA-Z0-9_\s]/', '', str_replace(' ', '_', $plugin_name));

                        $plugin_data = Esig_Addons::get_addon_data(Esig_Addons::get_installed_directory($plugin_file) . $plugin_file);
                        if (!empty($plugin_data['Documentation'])) {
                            $documentation_page = '<span class="esig-add-on-author"><a href="' . $plugin_data['Documentation'] . '" target="_blank">' . __('Documentation', 'esig') . '</a></span>';
                        }


                        // settings page .
                        $settings_page = '';
                        if (is_callable('esig_addon_setting_page_' . str_replace('-', '_', $folder_name))) {
                            $settings_page = call_user_func('esig_addon_setting_page_' . str_replace('-', '_', $folder_name), $settings_page);
                        }


                        /* if (get_option($esig_name . "_setting_page")) {
                          $settings_page = '<div class="esig-add-on-settings"><a href="' . get_option($esig_name . "_setting_page") . '"></a></div>';
                          } else {
                          $settings_page = '';
                          } */
                ?>
                        <div class="esig-add-on-block">

                            <div class="esig-add-on-icon">
                                <div class="esig-image-wrapper">
                                    <?php
                                    $logoFile = ESIGN_ASSETS_DIR_URI . '/images/add-ons/' . $folder_name . '.png';
                                    if (!file_exists($logoFile)) {
                                        $logoFile = apply_filters("esig_addons_logo_" . $folder_name, $logoFile);
                                    }
                                    ?>
                                    <img src="<?php echo $logoFile; ?>" width="50px" height="50px" alt="">
                                </div>
                            </div>

                            <div class="esig-add-on-info">
                                <h4><?php echo $plugin_name; ?></h4>
                                <span class="esig-add-on-author"> <?php _e('by', 'esig'); ?> <a href="http://approveme.com"><?php _e('ApproveMe.com', 'esig'); ?></a></span>
                                <?php echo $documentation_page; ?>

                                <p class="esig-add-on-description"><?php echo $plugin_data['Description']; ?></p>
                            </div>


                            <div class="esig-add-on-actions">
                                <?php if (Esig_Addons::isAlwaysEnabled($plugin_file)) { ?>
                                    <div class="esig-add-on-enabled-fixed"><?php echo '<a href="#" ' . $esig_permission . ' class="eisg-addons-disable-fixed">' . __('Enabled', 'esig') . '</a>'; ?></div>
                                <?php } else { ?>
                                    <div class="esig-add-on-enabled"><?php echo '<a data-text-disable="Disable" ' . $esig_permission . ' data-text-enabled="Enabled" href="?page=esign-addons&tab=enable&esig_action=disable&plugin_url=' . urlencode($plugin_file) . '&plugin_name=' . $plugin_name . '" class="eisg-addons-disable">' . __('Enabled', 'esig') . '</a>'; ?></div>
                                <?php } ?>
                                <?php echo $settings_page; ?>
                            </div>

                        </div>


    <?php
                    }
                }
            }
        }
    }

    if ($total == 0) {
        echo '<div class="esig-addons-achievement">
				<p><h2>' . _e('No add-ons are currently enabled', 'esig') . '</h2></p>
				<p class="esig-addon-enable-now"><a href="?page=esign-addons&tab=disable" class="esig-addon-enable-now">' . __('Go enable Add-Ons', 'esig') . '</a></p>

			    </div>';
    }
    ?>

    <?php
} // enable tab end here
// disable tab start here
if ($tab == 'disable') {

    $array_Plugins = Esig_Addons::get_all_addons();
    asort($array_Plugins);
    $total = 0;
    if (!empty($array_Plugins)) {
        foreach ($array_Plugins as $plugin_file => $plugin_data) {
            if (!Esig_Addons::is_enabled($plugin_file)) {
                $plugin_name = $plugin_data['Name'];

                if (preg_match("/esig-/", $plugin_file)) {

                    if ($plugin_name != "WP E-Signature") {
                        $total++;
                        // $plugin_name= trim($plugin_name, "WP E-Signature");
                        list($folder_name, $file_name) = explode('/', $plugin_file);
                        //$plugin_data = Esig_Addons::get_addon_data(Esig_Addons::get_installed_directory($plugin_file) . $plugin_file);
                        $documentation_page = '';
                        if (!empty($plugin_data['Documentation'])) {
                            $documentation_page = '<span class="esig-add-on-author"><a href="' . $plugin_data['Documentation'] . '" target="_blank">' . __('Documentation', 'esig') . '</a></span>';
                        }
    ?>
                        <div class="esig-add-on-block">

                            <div class="esig-add-on-icon">
                                <div class="esig-image-wrapper">
                                    <?php
                                    $logoFile = ESIGN_ASSETS_DIR_URI . '/images/add-ons/' . $folder_name . '.png';
                                    if (file_exists(WP_PLUGIN_DIR . '/e-signature-business-add-ons/' . $folder_name . '/assets/images/' . $folder_name . '.png')) {
                                        $logoFile = plugins_url() . '/e-signature-business-add-ons/' . $folder_name . '/assets/images/' . $folder_name . '.png';
                                    } else if (file_exists(WP_PLUGIN_DIR . '/wpesignature-add-ons/' . $folder_name . '/assets/images/' . $folder_name . '.png')) {
                                        $logoFile = plugins_url() . '/wpesignature-add-ons/' . $folder_name . '/assets/images/' . $folder_name . '.png';
                                    } else if (!file_exists($logoFile)) {
                                        $logoFile = apply_filters("esig_addons_logo_" . $folder_name, $logoFile);
                                    }


                                    ?>
                                    <img src="<?php echo $logoFile; ?>" width="50px" height="50px" alt="">
                                </div>
                            </div>

                            <div class="esig-add-on-info">
                                <h4><?php echo $plugin_name; ?></h4>
                                <span class="esig-add-on-author"> <?php _e('by', 'esig'); ?> <a href="http://approveme.com"><?php _e('ApproveMe.com', 'esig'); ?></a></span>

                                <?php echo $documentation_page; ?>

                                <p class="esig-add-on-description"><?php echo $plugin_data['Description']; ?></p>
                            </div>

                            <div class="esig-add-on-actions">
                                <div class="esig-add-on-disabled"><?php echo '<a data-text-enable="Enable" ' . $esig_permission . ' data-text-disabled="Disabled" href="?page=esign-addons&tab=disable&esig_action=enable&plugin_url=' . urlencode($plugin_file) . '&plugin_name=' . $plugin_name . '" class="eisg-addons-enable">' . __('Disabled', 'esig') . '</a>'; ?></div>


                            </div>



                        </div>


    <?php
                    }
                }
            }
        }
    }
    if ($total == 0) {
        echo '<div class="esig-addons-achievement">
				<h2>' . __('No add-ons are currently disabled', 'esig') . '</h2>

			    </div>';
    }
    ?>

    <?php
} // disable tab end here
// get-more tab start here
if ($tab == 'get-more') {


    if (Esign_licenses::is_license_active()) {
        $license_key = 'yes';
    } else {
        $license_key = 'no';
    }

    $all_addons_list = $this->model->esig_get_addons_list();

    if ($all_addons_list) {
        $total = 0;
        $all_addons = Esig_Addons::esig_object_sort($all_addons_list);
        foreach ($all_addons as $addonlist => $addons) {

            if (WP_E_Addon::is_business_pack_list($addons)) {
                continue;
            }
            if ($addonlist == "esig-price") {



                // installed business license 
    ?>
                <div class="esig-add-on-block esig-pro-pack open">
                    <?php _e('<h3>Are there any features you\'d like to see?</h3>
					        <p style="display:block;">WP E-Signature is a powerful (and highly customizable) document signing application powered by WordPress. We LOVE customer feedback and would love to hear from you. Let us know how we can improve your experience.</p>
					        <a class="esig-btn-pro" href="https://www.approveme.com/wpesignature-suggestions/" target="_blank">Submit a feature request</a> ', 'esig'); ?>

                </div>
    <?php

            }
        }


        if ($total == 0) {
            echo '<div class="esig-addons-achievement">
				<h2>' . __('Awesome! Looks like you have everything installed. Well done.', 'esig') . '</h2>
				<p><img src="' . ESIGN_ASSETS_DIR_URI . '/images/boss.svg" width="244" height="245"></p>
				<p><img src="' . ESIGN_ASSETS_DIR_URI . '/images/logo.png" width="243" height="55"></p>

			    </div>';
        }
    }
    ?>

<?php
} // get-more tab end here

if ($tab == 'integration') {
    include_once "integrations.php";
}
?>


</div>

<div class="esig-addon-devbox" style="display:none;">
    <div class="esig-addons-wrap">
        <div class="progress-wrap">
            <div class="progress">
                <span class="countup"></span>
            </div>
        </div>
    </div>
</div>



<?php
$esign_auto_update = $this->setting->get_generic("esign_auto_update");

if (isset($esign_auto_update) && empty($esign_auto_update)) {
    if (!get_transient('esign-update-remind')) {
        if (get_transient('esign-auto-downloads')) {
            include_once ESIGN_PLUGIN_PATH . "/views/about/update.php";
        }
    }
}
?>