<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
if (!class_exists('ESIG_Business_Updater')) :

    class ESIG_Business_Updater {

        public static function Init() {
            
            add_filter('pre_set_site_transient_update_plugins', array(__CLASS__, 'esig_make_updates_available'));
            add_filter("upgrader_package_options",array(__CLASS__,'esig_upgrder_package_options'),10,1);
            add_action("upgrader_process_complete",array(__CLASS__,"esig_upgrader_process_complete"),10,2);
        }

        public static function esig_make_updates_available($_transient_data) {
           
            if (empty($_transient_data)) {
                return $_transient_data;
            }

           
            if (!Esig_Addons::is_business_pack_exists() && !Esig_Addons::is_addons_pack_exists()) {
                return $_transient_data;
            }
           
            if (!Esig_Addons::is_updates_available()) {
                return $_transient_data;
            }
           
            $file = Esig_Addons::get_business_pack_path();
            $newAddon = new WP_E_Addon();

            $file = $newAddon->esig_get_addons_file_path(basename($file));
           
            $to_send = array('slug' => '');
            $name = plugin_basename($file);

            $update_list = json_decode(Esig_Addons::esig_get_update_list());

            $business_downloads = self::addonPack($update_list);
            
            $response = json_encode(array(
                        'slug' => trim($business_downloads->download_name,".zip"),
                        'plugin' => $file,
                        'new_version' => $business_downloads->new_version,
                        'url' => Esign_licenses::$approveme_url,
                        'package' => $business_downloads->download_link,
                    ));

            
            $api_respnose = json_decode($response);
            $plugin_data = get_plugin_data(WP_PLUGIN_DIR . "/" . $file);
            
            if (false !== $api_respnose && is_object($api_respnose)) {
                if (version_compare($plugin_data['Version'], $business_downloads->new_version, '<')) {

                   
                    $_transient_data->response[$name] = $api_respnose;
                }
            }
            return $_transient_data;
        }

        /**
         * return pack downloads 
         */
        public static function addonPack($update_list)
        {
            $serverLicenseType = Esign_licenses::get_serverLicense_type($update_list);
            $esigLicenseType = Esign_licenses::get_license_type();

            if ($serverLicenseType !== $esigLicenseType) {
                return false;
            }

            $businessPack = esigget("business_pack", $update_list);
            $addonsPack = esigget("wpesignature_addons", $update_list);
            if (empty($businessPack) && empty($addonsPack)) return false;
            if(is_object($businessPack)) return $businessPack;
            if(is_object($addonsPack)) return $addonsPack ; 
            return false;
        }

        /**
         * esig_upgrder_package_options
         */

       public static function esig_upgrder_package_options($options)
       {
           $hookExtra  = esigget("hook_extra",$options);
           $pluginFile = esigget("plugin", $hookExtra);
           $pluginName = basename($pluginFile);

           if($pluginName == "wpesignature-add-ons.php")
           {
                $options['clear_destination'] = false;
                $options['abort_if_destination_exists'] = false;
                
           }

           return $options;
       }

        /**
         * esig_upgrader_process_complete
         */
        public static function esig_upgrader_process_complete($plugin, $hook_extra)
        {
            
            $result = esigget("result",$plugin);
            
            $destinationName = esigget("destination_name",$result);
            //var_dump($plugin);
            $pluginInfo = esigget("plugin_info",$plugin->skin);
            $pluginName = esigget("Name",$pluginInfo);
            if($pluginName == "WP E-Signature add-ons" && $destinationName == "wpesignature-add-ons")
            {
                $downloadName = esigget("plugins",$hook_extra);
                $filePath = esigget(0,$downloadName);
                $source = esigget("source",$result);

                $newAddon = new WP_E_Addon();
                // Update all add-ons 
                $newAddon->esig_addons_updateall($source, $filePath);
            }

            return false;
        }

    } 
    

    endif;

ESIG_Business_Updater::Init();
