<?php

/*
 *  Render e-sigature shortcode content on signing and save it to database. 
 *  
 */

function esig_render_shortcode($doc_id) {

    $docType = WP_E_Sig()->document->getDocumentType($doc_id);
    if ($docType != "stand_alone") {
        return false;
    }
    $documentContentUnfilter = WP_E_Sig()->document->esig_do_shortcode($doc_id);

    $document_content = WP_E_Sig()->signature->encrypt(ENCRYPTION_KEY, $documentContentUnfilter);
    $document_checksum = sha1($doc_id . $documentContentUnfilter);
    Esign_Query::_update("documents", array("document_content" => $document_content, "document_checksum" => $document_checksum), array("document_id" => $doc_id), array("%s", "%s"), array("%d"));
    //Esign_Query::_update("documents",array("document_content"=>$document_content),array("document_id"=>$doc_id),array("%s"),array("%d"));
}

//add_action("esig_agreement_cloned_from_stand_alone", "esig_render_shortcode", 1, 1);

if (!function_exists('esig_replace_image')) {

    function esig_replace_image($contentToReplace) {

        if (empty($contentToReplace)) {

            return $contentToReplace;
        }

        if(!class_exists("DOMDocument"))
        {
            return $contentToReplace;
        }
        
        $domDocument  = new DOMDocument();
        @$domDocument->loadHTML($contentToReplace);
        
         if(!is_object($domDocument))
         {
             return $contentToReplace;
         }

        $xpath = simplexml_import_dom($domDocument);
        $images = $xpath->xpath('//img');
        
        if (empty($images) || !is_array($images)) {
            return $contentToReplace;
        }
        
        foreach ($images as $img) {
            
            //print_r($img);
            $imagePath = $img['src'];

            if (filter_var($imagePath, FILTER_VALIDATE_URL) === FALSE) {
                continue;
            }
            
            if (is_base64($imagePath)) {
                
                continue;
            }
          
            // grab image content here 
            /* $imageContent = WP_E_Sig()->signature->esig_get_contents($imagePath);
              $imageType = $audit_trail_helper->get_image_type($imageContent, $imagePath);
              $newImage = "data:image/" . $imageType . ";base64," . base64_encode($imageContent); */
            $newImage = esig_encoded_image($imagePath);

            $contentToReplace = str_replace($imagePath, $newImage, $contentToReplace);
        }


        return $contentToReplace;
    }

}


if (!function_exists("esig_encoded_image")) {

    function esig_encoded_image($imagePath) {

        $relativePath = esig_make_relative_link($imagePath);
       
        $imageContent = WP_E_Sig()->signature->esig_get_contents(ABSPATH . $relativePath);

        if (empty($imageContent)) {
             $imageContent = WP_E_Sig()->signature->esig_get_contents($imagePath);
        }

        if (empty($imageContent)) {
            $wpcontentDir = basename(WP_CONTENT_DIR);
            list($firstPart, $secondPart) = explode($wpcontentDir, $imagePath);
            $imagePath = content_url() . $secondPart;
            $imageContent = WP_E_Sig()->signature->esig_get_contents($imagePath);
        }

        $audit_trail_helper = new WP_E_AuditTrail();
        $imageType = $audit_trail_helper->get_image_type($imageContent, $imagePath);
       
      
        $newImage = "data:image/" . $imageType . ";base64," . base64_encode($imageContent);
        return $newImage;
    }

}


if (!function_exists('is_base64')) {

    function is_base64($s) {
        
       
        //return (bool) preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $s);
       /* if (strpos($s,"data:image/") !== false) {
           
            return true;
              
        }
        return false;*/
        return (bool) preg_match('/^data:image/', $s);
    }

}


if (!function_exists('esig_make_relative_link')) {

    function esig_make_relative_link($link) {
       
       return str_replace(site_url(), '', $link);
    }

}

/**
 *  esig do unique shortcode . 
 * It is used to render a shortcode 
 * @since 1.6.0
 * @return string $content
 */
if (!function_exists('esig_do_unique_shortcode')) {

    function esig_do_unique_shortcode($content,$shortcodeName=array(),$ignore_html = false)
    {
            global $shortcode_tags;
        
            if (false === strpos($content, '[')) {
                return $content;
            }

            if ( empty( $shortcode_tags ) || ! is_array( $shortcode_tags ) ) {
                return $content;
            }

            preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $content, $shortcodeListInContents);

            $tagnames = array_intersect( $shortcodeName, $shortcodeListInContents[1] );

            if (empty($tagnames)) 
            {
                return $content;
            }
            
            $tagnames = array_intersect($shortcodeName, $tagnames);
            
            $content = do_shortcodes_in_html_tags( $content, $ignore_html  , $tagnames );

            $pattern = get_shortcode_regex( $tagnames );
            $content = preg_replace_callback( "/$pattern/", 'do_shortcode_tag', $content );
        
            // Always restore square braces so we don't break things like <!--[if IE ]>.
            $content = unescape_invalid_shortcodes( $content );

            return $content;
    }

}

