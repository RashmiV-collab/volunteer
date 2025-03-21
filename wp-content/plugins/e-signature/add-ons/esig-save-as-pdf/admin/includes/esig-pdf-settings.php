<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class esigPdfSetting {

    static function fontConfig() {
        $fontdata = [
            "dejavusanscondensed" => array(
                'R' => "DejaVuSansCondensed.ttf",
                'B' => "DejaVuSansCondensed-Bold.ttf",
                'I' => "DejaVuSansCondensed-Oblique.ttf",
                'BI' => "DejaVuSansCondensed-BoldOblique.ttf",
                'useOTL' => 0xFF,
                'useKashida' => 75,
            ),
            "1" => array(
                'R' => "LaBelleAurore.ttf",
            ),
            "2" => array(
                'R' => "ShadowsIntoLight.ttf",
            ),
            "3" => array(
                'R' => "NothingYouCouldDo.ttf",
            ),
            "4" => array(
                'R' => "Zeyada.ttf",
            ),
            "5" => array(
                'R' => "DawningofaNewDay.ttf",
            ),
            "6" => array(
                'R' => "HerrVonMuellerhoff-Regular.ttf",
            ),
            "7" => array(
                'R' => "OvertheRainbow.ttf",
            )
        ];
        return $fontdata;
    }

    /**
     *  Check pdf different langauge mode enabled 
     *  @since 1.5.7.0
     *  @return bolean
     */

     public static function isPdfExportEnabled()
     {
         $pdfExport = WP_E_Sig()->setting->get_generic("esig_export_pdf_different_language");
         if($pdfExport)
         {
             return true;
         }
         return false;
     }

    /**
     * Returns supported page format list as array 
     * @since 1.5.7.0 
     * @return Array 
     *  */ 
    public static function supportedPageFormat()
    {
        return ["Legal","A4", "Letter"];
    } 

    public static function pageFormat()
    {
        if ($pageFormatCache = wp_cache_get('esig_pdf_page_format', 'esign')) {
            return $pageFormatCache;
        }

        $pageFormat = WP_E_Sig()->setting->get_generic("esig_pdf_page_format");
        // If no page format set default page format 
        if(!$pageFormat)
        {
            // return default page format legal. 
            $pageFormat =  "Legal" ; 
        }

        wp_cache_set('esig_pdf_page_format', $pageFormat , 'esign');

        return $pageFormat;
    }

    public static function uploadDir()
    {
        $upload_dir_list = wp_upload_dir();
        $upload_dir = $upload_dir_list['basedir'];
        wp_mkdir_p($upload_dir . '/esign');
        $upload_path = $upload_dir . '/esign';
        return apply_filters("esig_file_upload_path", $upload_path);
    }

    public static function isFontDirectoryExists()
    {
        if(file_exists(self::uploadDir() . "/esigpdffonts"))
        {
            return true;
        }

        return false ; 
    }

    /**
     *  Define audit trial wrapper size. 
     *@since 1.5.7.0 
     */
    public static function auditTrialWrapperSize()
    {
            $pageSize = self::pageFormat();
            if($pageSize === "Legal")
            {
                return 90 ; 
            }
            else
            {
                return 88.5 ; 
            }
    }


    public static function getDrawMarginClassName($imageUrl)
    {
        $image_info = getimagesize($imageUrl);
          
        if(!$image_info && !is_array($image_info))
        {
            return 9;
        }

        $width = esigget(0, $image_info);
        $height = esigget(1, $image_info);

        if($width < 400 && $width > 340 && $height == 100)
        {
            return 3;
        }
        elseif($width <= 340 && $width <=200 && $height == 100)
        {
            return 0;
        }
        elseif($width <= 340 && $width >200 && $height == 100)
        {
            return -5;
        }


        return 9; 
    }

    public static function getMarginClassName($signatureLeanth,$fontType)
    {
        
        
        $margin = false;    
        
        // font one style start here 
        if ($signatureLeanth <= 19 && $fontType == 7) {
            $margin = "margin-top:14%;";
        }
        elseif($signatureLeanth<=19 && $fontType ==5)
        {
            $margin = "margin-top:14%;"; 
        }
        elseif ($signatureLeanth <= 19) {
            $margin = "margin-top:15%;";
        } 
        elseif ($signatureLeanth >= 19 && $fontType == 1) {
            $margin = "margin-top:3%;";
        } 
        elseif ($signatureLeanth >= 19 && $fontType == 2) {
            $margin = "margin-top:3%;";
        } 
        // type signature font 4 start here 
        elseif ($signatureLeanth >= 19 && $fontType == 4) {
            $margin = "margin-top:3%;";
        }
        // type signature font 6 start here 
        elseif ($signatureLeanth >= 19 && $fontType == 6) {
            $margin = "margin-top:14%;";
        }
        // type signature font 7 start here 
        elseif ($signatureLeanth >= 19 && $fontType == 7) {
            $margin = "margin-top:2%;";
        }
        elseif($signatureLeanth <=15 && $fontType ==3)
        {
            $margin = "margin-top:13%;";
        }
        elseif($signatureLeanth <=19 && $fontType == 3)
        {
            $margin = "margin-top:2%;";
        } 
        elseif ($signatureLeanth <= 26 && $fontType == 3) {
            $margin = "margin-top:3%;";
        } 
        elseif ($signatureLeanth >= 26 && $fontType == 3) {
           
            $margin = "margin-top:-10%;";
        }
        elseif ($signatureLeanth <= 21) {
            $margin = "margin-top:2%;";
        }
        
        if(is_rtl() == '1'){
            $margin = apply_filters('esign-rtl-signature-margin',$signatureLeanth);
        }
        

        return $margin;
        
    }


}
