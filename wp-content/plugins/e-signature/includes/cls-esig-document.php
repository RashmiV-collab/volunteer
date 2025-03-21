<?php

if(!class_exists("EsigDocument")): 

    class esigDocument
    {
        public static function showTitle($data)
        {   
            
            $displayTitle = apply_filters("esig_display_docucment_title",true,esigget("docId",$data));
            
            if(!$displayTitle)
            {
                return false; 
            }

            $title_alignment = apply_filters('esig-document_title-alignment', '', esigget('wpUserId', $data));
            return '<p ' . $title_alignment . ' class="doc_title" id="doc_title1"> '. esigget("document_title",$data) .'</p>';
        }
    }

endif;