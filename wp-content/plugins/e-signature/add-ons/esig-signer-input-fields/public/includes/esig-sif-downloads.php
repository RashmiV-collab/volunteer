<?php

if (!class_exists('esig_sif_downloads')) :

    class esig_sif_downloads
    {

        public static function init()
        {
            add_action("esig_ame_download_file", array(__CLASS__, "new_download_file"));
        }

        public static function new_download_file()
        {

            $token = esigget("token");
            $token = sanitize_text_field($token);
            // if token is not valid returns . 
            if (!$token) return false;

            $values = explode(':', base64_decode($token));
            // If number of parameter does not match return  
            if (count($values) !== 3) return false ;

            $signatureHash = sanitize_text_field(esigget(0, $values));
            $download = sanitize_text_field(esigget(1, $values));
            $sifName = sanitize_text_field(esigget(2, $values));
         
            $signatureId = WP_E_Sig()->signature->getSignatureIdBySalt($signatureHash);
            
            // if signature id is not valid return
            if(!$signatureId) return false ;
            // Get signer input uploads fields value . 
            $result = Esign_Query::_row(Esign_Query::$table_signer_fields_data,["signature_id"=>$signatureId],["%d"]);
            // check result is valid  
            
            if(!$result) return false;
            
            $decrypt_fields = WP_E_Sig()->signature->decrypt("esig_sif", $result->input_fields);
            $fields = json_decode($decrypt_fields,true);
            
            // field not exists return 
            if(!array_key_exists($sifName,$fields)) return false;
        
            $downloadPath = esigget($sifName,$fields);
            $downloadName =  esigSifSetting::instance()->getDownloadName($downloadPath);
            // check download hash and download token hash match to force download otherwise return
            
            if($downloadName != $download) return false;

            $uploadPath = esigSifSetting::instance()->uploadDir() . "/" . basename($downloadPath);

            if (!file_exists($uploadPath)) return false;
            // validation success process downloads . 
            self::processDownload($uploadPath, $downloadName);

        }

        public static function processDownload($downloadPath, $downloadName)
        {
           
            $mimeType  = wp_check_filetype($downloadPath);
            $fileType = esigget("type",$mimeType);
            if(!$fileType) return false;

            $ext = pathinfo($downloadPath, PATHINFO_EXTENSION);
            
            header('Content-Description: File Transfer');
            header('Content-Type:' . $fileType);
            header('Content-Disposition: attachment; filename=' . $downloadName . ".". $ext);
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length: ' . filesize($downloadPath));
            ob_clean();
            flush();
            readfile($downloadPath);
            exit; 
        }

    }

    esig_sif_downloads::init();

endif;
