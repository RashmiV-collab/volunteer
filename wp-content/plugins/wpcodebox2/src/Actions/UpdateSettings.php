<?php


namespace Wpcb2\Actions;


use Wpcb2\FunctionalityPlugin\Manager;
use Wpcb2\FunctionalityPlugin\PluginsFolderNotWritableException;

class UpdateSettings
{
    public function execute()
    {
        $data = file_get_contents("php://input");
        $data = json_decode($data, true);

        if(isset($data['apiKey'])) {
            add_option('wpcb_settings_api_key');
            update_option('wpcb_settings_api_key', $data['apiKey'], false);
        }

        if(isset($data['showInTools'])) {
            add_option('wpcb_show_in_tools');
            update_option('wpcb_show_in_tools', $data['showInTools'], false);
        }

        if(isset($data['editorFontSize'])) {
            add_option('wpcb_settings_editor_font_size');
            update_option('wpcb_settings_editor_font_size', $data['editorFontSize'], false);
        }

        if(isset($data['editorTheme'])) {
            add_option('wpcb2_settings_editor_theme');
            update_option('wpcb2_settings_editor_theme', $data['editorTheme'], false);
        }

        if(isset($data['checkForUpdates'])) {
            add_option('wpcb_check_for_updates');
            update_option('wpcb_check_for_updates', $data['checkForUpdates'], false);
        }

        if(isset($data['wrapLongLines'])) {
            add_option('wpcb_wrap_long_lines');
            update_option('wpcb_wrap_long_lines', $data['wrapLongLines'], false);
        }

        if(isset($data['showCodemap'])) {
            add_option('wpcb_show_codemap');
            update_option('wpcb_show_codemap', $data['showCodemap'], false);
        }

        if(isset($data['darkMode'])) {
            add_option('wpcb_dark_mode');
            update_option('wpcb_dark_mode', $data['darkMode'], false);
        }

        if(isset($data['editorInTheMiddle'])) {
            add_option('wpcb_editor_in_the_middle');
            update_option('wpcb_editor_in_the_middle', $data['editorInTheMiddle'], false);
        }

        $functionalityPluginManager = new Manager(\get_option('wpcb_functionality_plugin_name'));

        if(isset($data['enableFunctionalityPlugin']) && $data['enableFunctionalityPlugin']) {


                add_option('wpcb_functionality_plugin_name');
                update_option('wpcb_functionality_plugin_name', 'WPCodeBox Functionality Plugin', false);
                add_option('wpcb_functionality_plugin_description');
                update_option('wpcb_functionality_plugin_description', 'This plugin stores and executes the WPCodeBox Code Snippets.', false);


            $functionalityPluginManager = new Manager('WPCodeBox Functionality Plugin');

            if(!$functionalityPluginManager->isEnabled()) {
                try {
                    $functionalityPluginManager->enable();
                } catch (PluginsFolderNotWritableException $e) {
                    status_header(501, 'The plugins folder is not writable');
                    die;

                }
            }
        } else {
            if($functionalityPluginManager->isEnabled()) {
                $functionalityPluginManager->disable();
            }
        }



        die;
    }
}
