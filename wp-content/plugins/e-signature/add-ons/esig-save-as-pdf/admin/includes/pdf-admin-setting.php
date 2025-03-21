<?php
/**
 *  Pdf optional settings. 
 * @package     WP_E_Sig
 * @subpackage  pdf
 * @copyright   Copyright (c) 2020
 * @license     http://opensource.org/licenses/gpl-2.1.php GNU Public License
 * @since       1.5.7.0
 */

 class esig_pdf_admin_settings
 {
     /**
      *  Class initialization 
      * 
      * @since 1.5.7.0
      */

     public static function init()
     {
        add_filter('esig-pdf-form-data', array(__CLASS__, 'misc_settings'), 11, 1);
        add_action('esig_pdf_settings_save', array(__CLASS__ ,'misc_settings_save'));
        add_filter("esig_pdf_filter_default_config",array(__CLASS__,"enable_export_different_langauge"));
        add_filter("esig_pdf_filter_default_config", array(__CLASS__, "enable_paper_size"));
        add_action('wp_ajax_esig_pdf_font_download', array(__CLASS__, "esig_pdf_font_download"));
     }
     /**
      *  Esig pdf download font data from remote server to setup e-signature export pdf in different language support
      *  @since 1.5.7.0 
     */
     public static function esig_pdf_font_download()
     {

         if(!is_esig_super_admin())
         {
             $errorText = __("You are not wp e-signature super admin . You need to have wp e-signature super admin privilege to donwload pdf fonts.","esig");
             echo json_encode(array("status"=>"error","errorMsg"=>$errorText));
             wp_die();
         }

         if(!current_user_can("install_plugins"))
         {
            $errorText = __("You do not have sufficient access to download.", "esig");
            echo json_encode(array("status" => "error", "errorMsg" => $errorText));
            wp_die();
         } 

         //check if font folder already exists 
         $fontFolder = esigPdfSetting::uploadDir();

         if(esigPdfSetting::isFontDirectoryExists())
         {
            $errorText = __("You already have downloaded pdf fonts.", "esig");
            echo json_encode(array("status" => "success", "errorMsg" => $errorText));
            wp_die();
         }

        // if class not exists add wp updater class 
        if (!class_exists('WP_Upgrader'))
        {
            require ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        }

        $esigRemoteFontFile = "https://www.approveme.com/wp-content/uploads/esigpdffonts.zip";

        // Suppress feedback.
         ob_start();

        // execute download method 
        $skin = new Automatic_Upgrader_Skin();
        $wp_installer = new WP_Upgrader($skin);

        $result = $wp_installer->run(array(
            'package' => $esigRemoteFontFile,
            'destination' => $fontFolder . "/esigpdffonts/",
            'clear_destination' => false, // Do not overwrite files.
            'clear_working' => true,
            'hook_extra' => array(
                'type' => 'package',
                'action' => 'install',
            )
        ));

        // Discard feedback.
        ob_end_clean();

        if(is_wp_error($result))
        {
            $errorText = __("Download error" . $result->get_error_message(), "esig");
            echo json_encode(array("status" => "error", "errorMsg" => $errorText));
            wp_die();
        }

        $successText = __("WP E-Signature pdf font download successful.", "esig");
        echo json_encode(array("status" => "success", "errorMsg" => $successText));
        wp_die();

     }

     /**
      *  Display settings option in WP E-Signature customization tab
      * 
      * @since 1.5.7.0 
      */

      public static function misc_settings($template_data)
      {
          
            $tabName = esigget("tab_name",$template_data);
            if($tabName !="Customization"){
                return $template_data;
            }

            $checked = (esigPdfSetting::isPdfExportEnabled())? "checked": false ; 

            $html = esigget("other_form_element",$template_data);

            $inputEnabled = "" ; 

            $html .= '<div class="esig-settings-wrap">
                <p> <h3>Exporting a PDF with different Languages</h3>  </p>
                <p> We are currently saving PDF agreements only in the English/European languages/alphabets.
                        Since WP E-Signature is used all over the world, we have added support to our PDF
                         export module for all languages and alphabets. ';

            if (!esigPdfSetting::isFontDirectoryExists()) {
                        $html .= 'To enable this 
                            feature you are required to download PDF fonts.
                        
                        ';
            }

         $html .= ' </p>';

            if(!esigPdfSetting::isFontDirectoryExists())
            {
                $inputEnabled = "disabled readonly" ; 

                $html .= '<div id="esig-pdf-font-container" style="margin:20px;">
                            <div id="esig-pdf-font-errorbox"> </div> 
                            <div id="esig-pdf-font-download" class="button-primary"> Download PDF fonts </div>
                        </div>';
            }

            $html .= '<div style="margin:20px;"><label for=""><input ' . $inputEnabled .' name="esig_export_pdf_different_language" id="esig-export-pdf-different-language" type="checkbox" value="1" '. $checked .'> ' . 
                     __('Check this box to export a PDF with different languages', 'esig' ) . ' </label><div style="margin-left:25px;font-size:10px"> e.g. "Chinese,Japanese" </div>
                    </div></div>';

            

            $template_data['other_form_element'] = $html;

            return $template_data;

      }

      /**
       * Misc settings save method
       * 
       * @since 1.5.7.0
       */

       public static function misc_settings_save()
       {
            $exportPdf = esigpost("esig_export_pdf_different_language");
            WP_E_Sig()->setting->set_generic("esig_export_pdf_different_language",$exportPdf);

            // page format settings goes here 
            $pageFormat = esigpost("esig_pdf_page_format");
            WP_E_Sig()->setting->set_generic("esig_pdf_page_format", $pageFormat);
       }

       public static function enable_paper_size($pdfConfig)
       {
            $paperSize = esigPdfSetting::pageFormat();
            if(!$paperSize)
            {
                return $pdfConfig;
            }

            $array = ["format" => $paperSize];

            return array_merge($pdfConfig, $array);
       }

       /**
        *  Filters default pdf config to allow export pdf in different langauge 
        *   @since 1.5.7.0
        */

        public static function  enable_export_different_langauge($pdfConfig)
        {
            if(!esigPdfSetting::isPdfExportEnabled())
            {
                return $pdfConfig;
            }

            if (!class_exists("Mpdf\Config\FontVariables")) {
                return $pdfConfig;
            }

            // find default configuration folder path and replace. 
            $defaultConfig = (new Mpdf\Config\ConfigVariables())->getDefaults();
            

            $defaultFontConfig = (new Mpdf\Config\FontVariables())->getDefaults();
            $fontData = $defaultFontConfig['fontdata'];
            
            $pdfConfig['fontdata'] += $fontData;
            
            $config = array (
                'fontDir' => array_merge(esigget('fontDir',$defaultConfig),[esigPdfSetting::uploadDir() . "/esigpdffonts/"]),
                'mode' => '+aCJK', 
                // "allowCJKoverflow" => true, 
                "autoScriptToLang" => true,
                // "allow_charset_conversion" => false,
                "autoLangToFont" => true,
            );

            return array_merge($pdfConfig,$config);
        }



 }

 //load init method to execute all hooks. 
 esig_pdf_admin_settings::init();