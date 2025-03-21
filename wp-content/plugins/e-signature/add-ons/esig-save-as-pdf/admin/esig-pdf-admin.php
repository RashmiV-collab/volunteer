<?php

/**
 *
 * @package ESIG_PDF_Admin
 * @author  Abu Shoaib
 */
if (!class_exists('ESIG_PDF_Admin')) :

    class ESIG_PDF_Admin {

        /**
         * Instance of this class.
         * @since    0.1
         * @var      object
         */
        protected static $instance = null;

        /**
         * Slug of the plugin screen.
         * @since    0.1
         * @var      string
         */
        protected $plugin_screen_hook_suffix = null;
        public $pdf_download = false;

        /**
         * Initialize the plugin by loading admin scripts & styles and adding a
         * settings page and menu.
         * @since     0.1
         */
        public function __construct() {

            /*
             * Call $plugin_slug from public plugin class.
             */

            $this->plugin_slug = "esig";

            // action list start here 
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
            add_action('admin_menu', array($this, 'register_esig_pdf_page'));
            // Add an action link pointing to the options page.
            $plugin_basename = plugin_basename(plugin_dir_path(__FILE__) . $this->plugin_slug . '.php');


            add_action('init', array($this, 'esig_frontend_pdf_save'));

            add_action('esig_pdf_settings_save', array($this, 'misc_settings_save'));

            add_action('esig_document_after_save', array($this, 'document_after_save'), 10, 1);
            // Ajax handlers
            // filter list star here . 
            add_filter('plugin_action_links_' . $plugin_basename, array($this, 'add_action_links'));
            add_filter('esig-document-footer-data', array($this, 'pdf_document_footer'), 10, 2);

            add_filter('esig_display_pdf_button', array($this, 'display_pdf_button'), 10, 2);

            add_filter('esig-pdf-form-data', array($this, 'pdf_misc_settings'), 10, 1);

            add_filter('esig-pdf-form-data', array($this, 'document_add_pdf_option'), 10, 1);

            add_filter('esig-edit-document-template-data', array($this, 'document_add_pdf_option'), 10, 2);

            add_filter('esig_admin_more_document_actions', array($this, 'document_save_as_pdf_action'), 10, 2);
        }

        public function display_pdf_button($result, $docId) {

            $esig_pdf_button = $this->getPdfOption($docId);
            $csum = WP_E_Sig()->document->document_checksum_by_id($docId);


            $pdfurl = add_query_arg(array('esigtodo' => 'esigpdf', 'did' => $csum), WP_E_Sig()->setting->default_link());
            // 
            if (wp_is_mobile()) {
                $target = 'target="_blank"';
            } else {
                $target = "";
            }
            $docType = WP_E_Sig()->document->getDocumenttype($docId);
            $signed = false;
            if ($docType == 'stand_alone') {
                $docStatus = WP_E_Sig()->document->getStatus($docId);
                if ($docStatus == 'signed') {
                    $signed = true;
                }
            } else {
                if (WP_E_Sig()->document->getSignedresult($docId)) {
                    $signed = true;
                }
            }
            if ($signed && $esig_pdf_button == 1) {
                return "<a href=\"$pdfurl\" $target class=\"agree-button esig-pdf-button\" id=\"esig-pdf-download\">" . __("Save As PDF", "esig") . "</a>";
            } elseif ($esig_pdf_button == 2) {
                return false;
            } elseif ($esig_pdf_button == 3) {
                return "<a href=\"$pdfurl\" $target class=\"agree-button esig-pdf-button\" id=\"esig-pdf-download\">" . __("Save As PDF", "esig") . "</a>";
            } else {
                return false;
            }
        }

        public function pdf_file_name($document_id) {

            $settings = new WP_E_Setting();
            $this->document = new WP_E_Document;
            $document = $this->document->getDocumentById($document_id);

            if(!$document)
            {
                return false;
            }
            
            $esig_pdf_option = json_decode($settings->get_generic('esign_misc_pdf_name'));
            $file_name = '';

            if (isset($esig_pdf_option)) {

                foreach ($esig_pdf_option as $names) {

                    if ($names == "document_name")
                        $file_name = $file_name . " " . str_replace(' ', '-', strtolower($document->document_title));
                    elseif ($names == "unique_document_id")
                        $file_name = $file_name . "_" . $document->document_checksum;
                    elseif ($names == "esig_document_id")
                        $file_name = $file_name . "_" . $document->document_id;
                    elseif ($names == "current_date")
                        $file_name = $file_name . "_" . date("d-m-Y");
                    elseif ($names == "document_create_date")
                        $file_name = $file_name . "_" . date("d-M-Y", strtotime($document->date_created));
                }
            }

            if (empty($file_name))
                    $file_name = str_replace(' ', '-', strtolower($document->document_title));

            $file_name = $this->esig_sanitize_file_name($file_name);
            
            if(empty($file_name)){ $file_name = $document->document_checksum;  } 

            return apply_filters("esig_pdf_file_name", $file_name, $document_id);
        }

        public function esig_sanitize_file_name($filename) {
            $filename_raw = $filename;
            $special_chars = array("?", "[", "]", "/", "\\", "=", "<", ">", ":", ";", ",", "'", "\"", "&", "$", "@", "#", "*", "(", ")", "|", "~", "`", "!", "{", "}");
            $special_chars = apply_filters('sanitize_file_name_chars', $special_chars, $filename_raw);
            $filename = str_replace($special_chars, '', $filename);
            $filename = preg_replace('/[\s-]+/', '-', $filename);
            $filename = trim($filename, '.-_');
            return apply_filters('sanitize_file_name', $filename, $filename_raw);
        }

        /**
         * This is method pdf_document creates 
         *
         * @return pdf file . 
         *
         */
        public function pdf_document($document_id = null,$pdf_name=false,$output="S") {

            global $esig_pdf_export;

            $esig_pdf_export = true;


            if (!function_exists('WP_E_Sig'))
                return;

            $api = WP_E_Sig();
            $this->document = new WP_E_Document;
            $this->signature = new WP_E_Signature;
            $this->invitation = new WP_E_Invite();
            $this->user = new WP_E_User;

            $pdf = $this->create_pdf_document();
            
           // $pdf->WriteHTML('<h1>Hello world!</h1>');
           // $pdf->Output($pdf_name, 'D');
     

            if ($document_id == null) {
                $document_id = isset($_GET['did']) ? $this->document->document_id_by_csum($_GET['did']) : $_GET['document_id'];
            }

            if ($document_id) {
                $doc_id = $document_id;
                $document = $this->document->getDocumentById($doc_id);
                
                
                //$document_report = $api->shortcode->auditReport($doc_id, $document);
                set_transient('is_esig_pdf', 'yes', 60);
                // get shortcoded document content by document id   
                $unfiltered_content = $this->document->esig_do_shortcode($document_id,$document);

                delete_transient('is_esig_pdf');
                
                $content = $unfiltered_content ; //apply_filters('the_content', $unfiltered_content);
               
                //checkbox replaced with checkmark symbol
                $content = preg_replace_callback('/<label><input type="checkbox"([^>]*)>(.*?)<\/label>/', function($matches) {
                    $checkbox = $matches[1];
                    $label = $matches[2];
                    $status = strpos($checkbox, 'checked') !== false ? '<span style="font-size:16px;">&#9745;</span>' : '<span style="font-size:16px;">&#9744;</span>';
                    return $status . ' ' . $label . '  ';
                }, $content);

                

                $content = preg_replace_callback('/<label class="checkbox button-inline"><input type="checkbox"([^>]*)>(.*?)<\/label>/', function($matches) {
                    $checkbox = $matches[1];
                    $label = $matches[2];
                    $status = strpos($checkbox, 'checked') !== false ? '<span style="font-size:16px;">&#9745;</span>' : '<span style="font-size:16px;">&#9744;</span>';
                    return $status . ' ' . $label . '  ';
                }, $content);
 

                //$dt = new DateTime($document->date_created);

                $date4sort = $this->document->esig_date_format($document->date_created, $document_id); //$dt->format(get_option('date_format'));

                $blogname = get_bloginfo('name');
                $blog_url = WP_E_Sig()->document->get_site_url($doc_id);


                $header = "<div class=\"document-sign-page\"><div  height=\"50px\"><div class='document_id'>" . __("Document ID:", "esig") . " {$document->document_checksum}</div>
<div class='document_date'>" . __("Generated on: ", "esig") . "{$date4sort}</div>"
. "<div class='signed_on'>" . __("Signed On: ", "esig") . " {$blog_url}</div></div>";




                $html = "

<div class='document-sign-page'>";

                $document_title_display = apply_filters("esig_document_title_display", true, $document_id);
                if ($document_title_display) {
                    $title_alignment = apply_filters('esig-document_title-alignment', '', $document->user_id);
                    
                    if(is_rtl() == '1'){
                        $html .= '<p class="doc_title">'.$document->document_title.'</p>';
                    } 
                    else{
                    
                    $html .= EsigDocument::showTitle(["docId"=>$document_id,"document_title"=> esig_unslash($document->document_title), "wpUserId"=> $document->user_id]); //"<p  " . $title_alignment . " class='doc_title'>" . esig_unslash($document->document_title) . "</p>";
                    
                    }

                }

                $html .= "
                            {$content}
                            </div>
                           
                            <div class ='signatures row'>

                            ";
                $allinvitaions = $this->invitation->getInvitations($doc_id);

                //pad height fot type siganture 
               
                $signature_css_class ="" ;

                if (!empty($allinvitaions)) {
                    
                    $small_img = ESIGN_DIRECTORY_URI . "assets/images/sign-here_blank.jpg";
                    $count = 0 ; 
                    $adminMarginTop = 3;
                    foreach ($allinvitaions as $invite) {

                        $fullname = $this->user->get_esig_signer_name($invite->user_id, $doc_id);

                        $date = $this->signature->GetSignatureDate($invite->user_id, $doc_id);

                        if (!$this->signature->userHasSignedDocument($invite->user_id, $doc_id)) {
                            $sign = '<div width="255px" height="100px"></div>';
                            $sign_bottom_text = $fullname . "<br>";
                            $sign_bottom_text .= __("(Awaiting Signature) ", "esig");
                        } else {
                            $sign_bottom_text = sprintf(__("Signed By %s ", "esig"), $fullname);
                            $sign_bottom_text .= "<br>" . __("Signed On: ", "esig") . $this->document->esig_date_format($date, $document_id);
                        }
                        
                        
                        
                        $signature_type = $this->signature->getDocumentSignature_Type($invite->user_id, $doc_id);
                        // pad height 
                        
                        if ($signature_type == "typed") {

                            $sign_data = $this->signature->getDocumentSignature($invite->user_id, $doc_id);
                            $font = $this->signature->get_font_type($doc_id, $invite->user_id);
                            
                            //echo strlen($sign_data);
                            $font_size = abs(36 - strlen($sign_data) * 0.5);
                            $signatureLeanth = strlen($sign_data); 
                            
                            //if($font_size > 30) { $signerFontSize = 80; }
                            $font_size =  WP_E_Sig()->signature->defineFontSize($sign_data);
                            
                            $lineHeight = ($signatureLeanth <= 19) ? 1 : 1;
                           
                            $marginTop  = esigPdfSetting::getMarginClassName($signatureLeanth,$font); 
                            $adminMarginTop = 9;
                           $signature_css_class ="-typed" ;
                                
                            $sign = '<div class="sign-text-pdf" style=" ' . $marginTop . 'margin-left:1%;font-size:'. $font_size . ';">
						
						<div style="font-family:'. $font .';">' . $sign_data . '</div></div>';
                           
                        } elseif ($signature_type == "full") {

                            //$signature_url = $this->get_signature_image_url($invite->user_id, $document->document_checksum);
                            $signature_image = $this->signature->display_signature($invite->user_id,$document->document_checksum,wp_create_nonce($invite->user_id . $document->document_checksum));
                          
                            $sign = '<div style="background-size:100% auto;
                            background: transparent url(' . $signature_image . ') 0px 0px ;
                            background-position: top left;
                            height:70px;
                            margin-top:12px;
                            "></div>'; 
                            
                            $signature_css_class ="-signed" ; 
                        }
                       
                        $html .= '<div class="signature-left" align="left">
                                        
					<div class="pdf-signature-pad"  style="
                            text-align:left;
                            position:relative;
                            background-size:100% auto;
                            background: transparent url(' . $small_img . ') no-repeat 0px 0px ;
                            background-position: bottom left;
                           
                           
                            ">
					
					    ' . $sign . '
					
					 </div><div class="signature-top'. $signature_css_class .'">';

                        $html .= $sign_bottom_text . "</div></div>";
                        
                         $count++ ;
                        if($count % 2 == 0 ){
                            
                           $html .= "</div><div class ='signatures row'>" ;  
                        }
                        
                    }  // foreach end here ;
                } else {
                    $small_img2 = ESIGN_DIRECTORY_URI. "assets/images/sign-here_blank.jpg";

                    $html .= '<div class="signature-left" align="left">
					<div class="signature-top" style="
                            text-align:left;
                            background:transparent url(' . $small_img2 . ') no-repeat left bottom ;
                            height:100px;
                            ">
					
					
					 </div><div class="signature-top">';

                    $html .= "</div></div>";
                }
                //admin signature start here 
                if ($document->add_signature) {


                    $owner_id = WP_E_Sig()->meta->get($document->document_id, 'auto_add_signature');
                    if (!$owner_id) {
                        $owner_id = $document->user_id;
                    } else {
                        $esig_users = WP_E_Sig()->user->getUserBy('user_id', $owner_id);
                        $owner_id = $esig_users->wp_user_id;
                    }

                    $owner = $api->user->getUserBy('wp_user_id', $owner_id);

                    $signature_id = WP_E_Sig()->meta->get($document->document_id, "auto_add_signature_id");

                     $no_admin=true;
                    if (!$signature_id) {
                        $signature_id = $this->signature->GetSignatureId($owner->user_id, $doc_id);
                        // 
						if(!$signature_id){
							 $sig_data = $this->signature->getSignatureData($owner->user_id);
							 $signature_id = $sig_data->signature_id;
							 $no_admin=false;
						}
                    }
					
                    $signature_type = $api->signature->getSignature_type_signature_id($signature_id);

                    
                    if ($signature_type == "typed" && $no_admin) {

                        $sign_data = $this->signature->getDocumentSignature($owner->user_id, $doc_id, "admin_signature");

                        if (!$sign_data) {
							
                            $sign_data = $this->signature->getDocumentSignature($owner->user_id, $doc_id);
							
							if(!$sign_data){
								
								$sign_data = $this->signature->getUserSignature_by_type($owner->user_id,"typed");
								
							}
                        }
                        $font = $api->setting->get_font($owner->user_id, $doc_id);

                        $font_size = strlen($sign_data) ; //abs(50 - strlen($sign_data) * 1.2);
                        
                        if( $font_size >= 45){
                            $signMarginTop = '5';
                        }elseif( $font_size >= 25){
                            $signMarginTop = '8';
                        }elseif( $font_size >= 30){
                            $signMarginTop =  '15';
                        }else{
                            $signMarginTop =  '55';
                        }                   

                        $font_size =  ($font_size <= 19)? 38 : 30 ; 
                        $marginTop = ($font_size <= 19) ? "pdf-admin-type-margin" : 30; 

                        if (!empty($font_family)) {
                            $font = "sun-extA";
                        }
                         $signature_css_class ="-typed" ;
                        $sign_admin = '<div class="sign-text-pdf " style="margin-top:'. $signMarginTop .'px;margin-left:1%;font-size:'. $font_size .';">
						<div style="font-family:'. $font .';">'. $sign_data .'</div>
						</div>';
							
							
                    } elseif ($signature_type == "full" && $no_admin) {
                        $sign_data = $this->signature->getDocumentSignature($owner->user_id, $doc_id, "admin_signature");
                            
                        if (!$sign_data) {
							
                            $signature_url = $this->get_signature_image_url($owner->user_id, $document->document_checksum);
							
                        } else {
                            $signature_url = $this->get_signature_image_url($owner->user_id, $document->document_checksum, "admin_signature");
                        }

                         $signature_css_class ="-signed" ;
                       
                        // $marginTop = 3 ; 
                        
                        $sign_admin ='<div style="background-size:100% auto;
                        background:transparent url(' . $signature_url . ') no-repeat;
                        background-position:left bottom;
                        height:85px;
                        "></div>'; 
                       
                    } else {
						
						$signature_url = $this->get_signature_image_url($owner->user_id,false,false,true);
			 $signature_css_class ="-signed" ;			
                       // $this->esign_set_json($owner->user_id, 'old-aams', true);
                       $sign_admin = '<img style="margin-top:9%;" src="' . $signature_url . '" width="350px" height="70px">';
					   
                    }
                    
                    
                    if($signature_type == "full"){
                        $small_img4 = ESIGN_DIRECTORY_URI . "assets/images/sign-here_blank.jpg";
                       
                       
                    } else {
                       $small_img4 = ESIGN_DIRECTORY_URI . "assets/images/sign-here_blank.jpg"; 
                    }
                   
                    $html .= '<div class="signature-left" align="left">
					<div class="pdf-signature-pad"  style="
                                        text-align:left;
                                        position:relative;
                                        background-size:100% auto;
                            background:transparent url(' . $small_img4 . ') no-repeat left bottom;
                            ">
					
					' . $sign_admin . '
					
					</div><div class="signature-top-admin'. $signature_css_class .'">';
                    $html .= sprintf(__("Signed By %s %s ", "esig"), $owner->first_name, $owner->last_name);
                    $html .= "<br>" . __("Signed On: ", "esig") . $this->document->esig_date_format($document->last_modified, $document_id) . "</div></div>";
                }

                // admin signature end here

                $html .= "

                            </div></div> ";


                $footer = "<div class='pdf-footer'>
		<div class='footer-left'>
			<img src='" . ESIGN_ASSETS_DIR_URI . "/images/verified-approveme-gray.svg' alt='WP E-Signature'/>
		</div>
		<div class='footer-right'>
			{$blogname} <br>" . __("Page", "esig") . " {PAGENO} " . __("of", "esig") . " {nb}
			<br/> {$this->get_audit_trail_serial($doc_id, $document)}
		</div>
	</div>";

                          
                $this->define_footer($pdf, $footer);

                $pdf_front_page = apply_filters('esig_save_as_pdf_front_page', '', $doc_id);

                // load pdf stylesheet 
                $this->loadStylesheet($pdf);
                //set protection 
                $this->setProtection($pdf);

                if (!empty($pdf_front_page)) {
                    //$pdf_header = apply_filters('esig_save_as_pdf_header', '', $doc_id);
                    //$pdf->SetHTMLHeader($pdf_header);

                    $pdf_footer = apply_filters('esig_save_as_pdf_footer', '', $doc_id);
                    
                    $pdf->SetHTMLFooter($pdf_footer);

                    $pdf->AddPage();

                    $pdf->WriteHTML($pdf_front_page);
                    $pdf->SetHTMLHeader($header);


                    $pdf->SetHTMLFooter($footer);
                    
                  //  $pdf->AddPage();
                   $pdf->AddPageByArray([
                       'margin-top' => 35,
                       'margin-bottom' => 27,
                       
                       
                    ]);
                }
                else {
                    $pdf->SetHTMLHeader($header);
                         $pdf->AddPageByArray([
                              'margin-top' => 35,
                              'margin-bottom' => 27,
                       
                           ]);
                }     

                $pdf->SetHTMLHeader($header);

                $pdf->SetHTMLFooterByName('Regular_PDF_Footer');

                $pdf->WriteHTML($html);
                $pdf->SetHTMLHeader('');
                
                //$page_count = $pdf->docPageNum($pdf->page, true) - 1;
                
               // $pdf->SetHTMLHeader('');
               

                $audit_trail_html = "{$api->shortcode->auditReport($doc_id, $document, false, true)}";
                //$audit_trail_html = str_replace('{PAGENO}', $page_count + $this->get_audit_trail_page_count($audit_trail_html), $audit_trail_html);
                $pdf->setAutoBottomMargin = 'stretch';


                $pdf->WriteHTML($audit_trail_html);
              
                $this->auditTrailFooter($pdf); 

                $pdf->SetHTMLFooterByName('Audit_Trail_Footer');
                
                if(!$pdf_name){
                    $pdf_name = $this->pdf_file_name($doc_id) . ".pdf";
                }


                // delete all signature json files 
                $fullPath = ESIGN_PLUGIN_PATH . "/assets/temps/";
                array_map('unlink', glob("$fullPath*.txt"));
                // after generating pdf set global pdf export false for web loads

                $esig_pdf_export = false;

                // output pdf file
              
               return $pdf->Output($pdf_name, $output);
              
                
            }
        }
        
        private function auditTrailFooter($pdf)
        {
             
                global $audit_trail_data;
               $differentPageStyle =(esigPdfSetting::pageFormat() !="Legal") ? 'style="padding-bottom:-12px;"' : false;
                $images_url = ESIGN_ASSETS_DIR_URI . ESIG_DS . "/images";
           $auditTrailFooter =' 
                <div class="footer audit-pdf-footer">
                    <table>
                        <tr>
                            <td class="pdf-qr-code">
                                <img style="width: 80px; height: 80px;" src="'. $audit_trail_data->current_url_qr .'" alt="Audit trial Qr code">
                            </td>
                            <td class="pdf-footer-description">
                                '. __("This audit trail report provides a detailed record of the online activity and events recorded for this contract.", "esig") .'
                            </td>
                            <td class="pdf-footer-pages">
                                ' . __("Page {PAGENO} of {nb} ", "esig") . '
                            </td>
                        </tr>
                        </table>
                </div>
                 <div class="bottom-footer audit-pdf-bottom-footer" '. $differentPageStyle  .' >
                        <table style="width: 100%;">
                            <tr>
                               ' ;

                                    if(is_rtl() == '1'){
                                        $auditTrailFooter .= ' <td style="float:right;text-align:right;"><a class="audit-link" href="' .$audit_trail_data->site_url .'"> ' . $audit_trail_data->site_url .' </a></td><td style="">';  
                                    } else{
                                        $auditTrailFooter .= ' <td style="width: 40%;padding-left:10px;"><a class="audit-link" href="' .$audit_trail_data->site_url .'"> ' . $audit_trail_data->site_url .' </a></td><td style="width: 60%; text-align: right;padding-right:10px; ">';    
                                    }
                                     if ($audit_trail_data->audit_signature_id) {
                                       $auditTrailFooter .= ' <div class="pdf-audit-signature audit-signature pull-right"><img style=" height: auto;width: 12px;vertical-align:bottom;margin-top: -5px;margin-right: 5px;" src=" ' . $images_url . '/lock.png" alt=""> ' . __("Audit Trail Serial#", "esig") . $audit_trail_data->audit_signature_id  .'</div>';
                                     }
                                   
                      $auditTrailFooter .= '  </td>
                            </tr>
                        </table>
                    </div>
            ';

             $pdf->DefHTMLFooterByName('Audit_Trail_Footer', $auditTrailFooter);                           

        }
        
        private function loadStylesheet($pdf){

            
           
            $stylesheet = file_get_contents(ESIGN_TEMPLATES_PATH . '/default/print_style.css'); // external css
           
            $stylefile = apply_filters("esig-pdf-export-stylesheet", $stylesheet); 
           
            if(is_rtl() == '1'){
                $pdf->WriteHTML($stylefile,\Mpdf\HTMLParserMode::HEADER_CSS);
            } else{
                $pdf->WriteHTML($stylesheet,\Mpdf\HTMLParserMode::HEADER_CSS);
            }
        }
        
        private function setProtection($pdf){
           $pdf->SetProtection(array('copy', 'print', 'print-highres', 'extract', 'assemble'));
        }

        private function get_audit_trail_serial(
        $doc_id, $document) {
            $all_signed = WP_E_Sig()->document->getSignedresult($doc_id);
            if ($all_signed) {
                $shortcode = new WP_E_Shortcode();
                $serial = $shortcode->auditReport($doc_id, $document, true);
                return "<img src = '" . ESIGN_ASSETS_DIR_URI . "/images/lock.png' width = '8' height = '12' alt = 'Audit Lock'/> " . __("Audit Trail Serial#", "esig") . " {$serial}";
            }
            return '';
        }

        private function get_audit_trail_page_count($audit_trail_html) {
            $pdf = $this->create_pdf_document();
            $pdf->WriteHTML($audit_trail_html);
            $page_count = $pdf->docPageNum($pdf->page, true);
            return $page_count;
        }

        public function get_signature_image_url($user_id, $document_checksum, $signer_type = false,$old_signature=false) {
			
			if($old_signature){
                $signatureImage =$this->esign_set_json($user_id, false, true);
			}
			else {
                $signatureImage = WP_E_Sig()->signature->esign_set_json($user_id, $document_checksum, false, $signer_type);
			}

            // If signature is with our new signature pad return signature image .
            if ($signatureImage) {
                return $signatureImage;
            }
               
            $nonce = wp_create_nonce($user_id . $document_checksum);
            $image_url = (ESIGN_DIRECTORY_URI . 'lib/sigtoimage.php?uid=' . $user_id . '&is_pdf=1&doc_id=' . $document_checksum . '&esig_verify=' . $nonce);
            $image_content = WP_E_Sig()->signature->esig_get_contents($image_url);
            return "data:image/png;base64," . base64_encode($image_content);
        }

        public function document_save_as_pdf_action($more_actions, $args) {

            $doc = $args['document'];
          
            if(!esigget("document_id",$doc)){
                return $more_actions;
            }

            $pdfurl = add_query_arg(array('esigtodo' => 'esigpdf', 'did' => WP_E_Sig()->document->document_checksum_by_id($doc->document_id)), WP_E_Sig()->setting->default_link());
            if ($doc->document_status == 'signed')
                $more_actions .= '| <span class="save_as_pdf_link"><a href="' . $pdfurl . '" title="Save as pdf">' . __('Save As PDF', 'esig') . '</a></span>';


            return $more_actions;
        }

        public function pdf_document_footer($template_data) {


            $document_id = ESIG_GET('document_id');

            $this->document = new WP_E_Document;
            $settings = new WP_E_Setting();
            $esig_pdf_button = $this->getPdfOption($document_id);

            if (empty($esig_pdf_button))
                $esig_pdf_button = $settings->get_generic('esig_pdf_option');


            $csum = $this->document->document_checksum_by_id($document_id);


            $pdfurl = add_query_arg(array('esigtodo' => 'esigpdf', 'did' => $csum), WP_E_Sig()->setting->default_link());


            // 

            if (wp_is_mobile()) {
                $target = 'target="_blank"';
            } else {
                $target = "";
            }


            if ($this->document->getSignedresult($document_id) && $esig_pdf_button == 1) {

                $template_data['pdf_button'] = "<a href=\"$pdfurl\" $target class=\"agree-button\" id=\"downloadLink\">" . __("Save As PDF", "esig") . "</a>";

                return $template_data;
            } elseif ($esig_pdf_button == 2) {
                return $template_data;
            } elseif ($esig_pdf_button == 3) {
                $template_data['pdf_button'] = "<a href=\"$pdfurl\" $target class=\"agree-button\" id=\"downloadLink\">" . __("Save As PDF", "esig") . "</a>";
                return $template_data;
            } else {
                return $template_data;
            }
        }

        /*
         * Esig pdf saving option from front end 
         * Since 1.0.9
         */

        public function esig_frontend_pdf_save() {
            $esigtodo = isset($_GET['esigtodo']) ? $_GET['esigtodo'] : null;
            if (isset($esigtodo)) {
                $this->save_as_pdf_content();
            } else {
                return;
            }
        }

        public function pdf_misc_settings($template_data) {

            $settings = new WP_E_Setting();
            $esig_pdf_option = json_decode($settings->get_generic('esign_misc_pdf_name'),true);

            if (empty($esig_pdf_option))
                $esig_pdf_option = array();
                  
            $tabName = esigget("tab_name",$template_data);
            if($tabName !="Customization"){
                return $template_data;
            }
           
            $pdfNameOptions = [
                'document_name'=> __('Document Name', 'esig'),
                'unique_document_id' => __('Unique Document ID', 'esig'),
                'esig_document_id' =>  __('Esig Document ID', 'esig'),
                'current_date' => __('Current Date', 'esig'),
                'document_create_date' => __('Document Create Date', 'esig'),
            ];

            $pdfNames = array_merge(array_flip($esig_pdf_option), $pdfNameOptions);

            $html = esigget("other_form_element",$template_data);

            $html .= '<div class="esig-settings-wrap"><label>' . __('How would you like to name your PDF documents?', 'esig') . '</label><select id="esig-pdf-naming-option" data-placeholder="' . __('Choose your naming format(s)', 'esig') . '" name="pdfname[]" style="margin:17px;width:350px;" multiple class="esig-pdf-naming-select2" tabindex="11">
            <option value=""></option>';
            foreach($pdfNames as $key => $value)
            {
                $selected = false;
                if (in_array($key, $esig_pdf_option)) $selected = "selected";

                $html .='<option value="' . $key .'" ' . $selected .'> '. $value .' </option>';
            }

            $html .= '</select><span class="description"><br />e.g. "My-NDA-Document_10-12-2014.pdf"</span></div>';

            $template_data['other_form_element'] = $html;
            return $template_data;
        }

        public function document_add_pdf_option($template_data) {
            $settings = new WP_E_Setting();

            // defining variable . 
            $esig_pdf_button = '';
            $esig_pdf_option1 = '';
            $esig_pdf_option2 = '';
            $esig_pdf_option3 = '';

            $document_id = isset($_GET['document_id']) ? $_GET['document_id'] : null;

            $esig_pdf_button = $this->getPdfOption($document_id);

            if (empty($esig_pdf_button))
                $esig_pdf_button = apply_filters('esig_pdf_button_filter', '');


            if (empty($esig_pdf_button)) {
                $esig_pdf_button = json_decode($settings->get_generic('esig_pdf_option'));
            }

            if (!empty($esig_pdf_button)) {
                if ($esig_pdf_button == 1)
                    $esig_pdf_option1 = "selected";
                else if ($esig_pdf_button == 2)
                    $esig_pdf_option2 = "selected";
                else if ($esig_pdf_button == 3)
                    $esig_pdf_option3 = "selected";
            } else {
                $esig_pdf_option1 = "selected";
            }


            $html = sprintf(__('<p>
			<h4 class="esig-pdf-heading">' . __('Save as PDF <span class="description">default settings:</span>', 'esig') . '</h4>
         
				<select  style="width:500px;" data-placeholder="Choose a Option..." name="esig_pdf_option" class="esig-select2" tabindex="9">
					<option value=""></option>		
					<option value="1" %s>' . __('Only display Save as PDF button when document is signed by everyone', 'esig') . '</option>
					
					<option value="2" %s>' . __('Hide Save as PDF button always, no matter what.', 'esig') . '</option>
								
					<option value="3" %s>' . __('Display Save as PDF button always, no matter what.', 'esig') . '</option>
						  
				 </select>
 
			</p>
			', 'esig'), $esig_pdf_option1, $esig_pdf_option2, $esig_pdf_option3);
            $template_data['pdf_options'] = $html;


            return $template_data;
        }

        public function getPdfOption($docId) {
            $option = WP_E_Sig()->meta->get($docId, 'esig_pdf_option');
            if ($option) {
                return $option;
            }
            $option = WP_E_Sig()->setting->get_generic('esig_pdf_option' . $docId);
            if ($option) {
                return $option;
            }
            return WP_E_Sig()->setting->get_generic('esig_pdf_option');
        }

        public function document_after_save($args) {
            $docId = $args['document']->document_id;
            WP_E_Sig()->meta->add($docId, 'esig_pdf_option', esigpost('esig_pdf_option'));
        }

        public function misc_settings_save() {
            $misc_data = array();

            if (isset($_POST['pdfname'])) {
                
                
                $exitingPdfName = array("document_name", "unique_document_id", "esig_document_id","current_date","document_create_date");              
                
                foreach ($_POST['pdfname'] as $key => $value) {                    
                    if( in_array( $value , $exitingPdfName ) )                    {
                        $misc_data[$key] = $value;
                    }                       
                }
            }
            $misc_ready = json_encode($misc_data);
            $settings = new WP_E_Setting();
            $settings->set_generic("esign_misc_pdf_name", $misc_ready);

            if (isset($_POST['esig_pdf_option']))
                $settings->set_generic("esig_pdf_option", $_POST['esig_pdf_option']);
        }

        /**
         * Return an instance of this class.
         * @since     0.1
         * @return    object    A single instance of this class.
         */
        public static function instance() {
            // If the single instance hasn't been set, set it now.
            if (null == self::$instance) {
                self::$instance = new self;
            }

            return self::$instance;
        }

        public function esign_set_json($user_id, $csum_id, $owner_id = false) {
            $document = new WP_E_Document;
            $signature = new WP_E_Signature;
            // getting document id from csum id .
            $doc_id = $document->document_id_by_csum($csum_id);
            if ($owner_id) {
                $json = $signature->getUserSignature($user_id);
				//$csum_id="old-signature";
            } else {
                $json = $signature->getDocumentSignature($user_id, $doc_id);
            }
		
            $file_name = ESIGN_PLUGIN_PATH . '/assets/temps/' . $user_id . '-' . $csum_id . '.txt';
			
           if (!@file_put_contents($file_name, $json)) {

            $sigfile = @fopen($file_name, "w");

            @fwrite($sigfile, $json);

            fclose($sigfile);
			}
		
            return false;
        }

        /**
         * Render the settings page for this plugin.
         * @since    0.1
         */
        public function display_plugin_admin_page() {
            include_once( 'views/admin.php' );
        }

        public function enqueue_admin_styles() {
            $screen = get_current_screen();
            $admin_screens = array(
                'admin_page_esign-misc-general'
            );

            if (in_array(esigget("id",$screen), $admin_screens)) {
                wp_enqueue_style($this->plugin_slug . '-admin-styles', plugins_url('assets/css/chosen.min.css', __FILE__), array(), esigGetVersion());
            }
        }

        /**
         * Register and enqueue admin-specific JavaScript.
         *
         * @since     0.1
         * @return    null    Return early if no settings page is registered.
         */
        public function enqueue_admin_scripts() {
            $screen = get_current_screen();
            
            $admin_screens = array(
                'admin_page_esign-pdf-option'
            );

            // Add/Edit Document scripts
            if (in_array(esigget("id",$screen), $admin_screens)) {
                wp_enqueue_script('jquery-ui-dialog');
                wp_enqueue_script($this->plugin_slug . '-admin-script', plugins_url('assets/js/chosen.jquery.js', __FILE__), array('jquery', 'jquery-ui-dialog'), esigGetVersion(), true);
                wp_enqueue_script($this->plugin_slug . '-admin-script1', plugins_url('assets/js/prism.js', __FILE__), array('jquery', 'jquery-ui-dialog'), esigGetVersion(), true);
                wp_enqueue_script($this->plugin_slug . '-admin-script2', plugins_url('assets/js/main.js', __FILE__), array('jquery', 'jquery-ui-dialog'), esigGetVersion(), true);
                wp_enqueue_script($this->plugin_slug . '-pdf-scripts', plugins_url('assets/js/admin.js', __FILE__), array('jquery', 'jquery-ui-dialog'), esigGetVersion(), true);
            }
        }

        /**
         * Add settings action link to the plugins page.
         * @since    0.1
         */
        public function add_action_links($links) {

            return array_merge(
                    array(
                'settings' => '<a href="' . admin_url('admin.php?page=esign-misc-general') . '">' . __('Settings', $this->plugin_slug) . '</a>'
                    ), $links
            );
        }

        /**
         * adding pdf menu page . 
         * Since 1.0.1
         * */
        public function register_esig_pdf_page() {
            add_submenu_page(' ', 'Pdf link page', 'Pdf link page', 'read', 'esigpdf', array($this, 'save_as_pdf_content'));
            //add_menu_page('E-signature save as pdf','manage_options', 'esigpdf', array($this,'save_as_pdf_content'),'', 6 );
        }
        /**
         * pdf page content here 
         *
         * Since 1.0.1
         */
        public function save_as_pdf_content($document_id = null) {

            $this->document = new WP_E_Document;
            if ($document_id == null) {
                $document_id = isset($_GET['did']) ? $this->document->document_id_by_csum($_GET['did']) : $_GET['document_id'];
            }

            $pdf_name = $this->pdf_file_name($document_id) . ".pdf";
       
            $this->pdf_document($document_id,$pdf_name,'D');
            exit;
        }

        private function create_pdf_document() {

           // $current_error_reporting = error_reporting(0);
           if (file_exists(WP_CONTENT_DIR . "/esign_customization/mpdf/autoload.php")) {
                require_once ( WP_CONTENT_DIR . "/esign_customization/mpdf/autoload.php" );
            } else {
                require_once ( ESIG_MPDF_PATH . '/autoload.php' );
            }

            $enableDebug  = apply_filters("esig_pdf_debug",false);

            $configArray = array(
                "debug"=>$enableDebug,
                'fontdata' => esigPdfSetting::fontConfig(),
                "format"=> "Legal",
                "tempDir" => esigFiles::instance()->tempPath() . "/tmp" 
            );

            $esigPdfConfig = apply_filters("esig_pdf_filter_default_config",$configArray);
         
            $pdf = new \Mpdf\Mpdf($esigPdfConfig);
           
            return $pdf;
        }

        private function define_footer($pdf, $footer) {
            $pdf->DefHTMLFooterByName('Regular_PDF_Footer', $footer);
        }

    }

    

    

    

    

        

    

endif;
