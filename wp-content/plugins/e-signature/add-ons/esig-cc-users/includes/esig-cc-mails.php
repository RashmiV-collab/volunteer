<?php

class Cc_mails extends Cc_Settings
{

    public static function Init()
    {
        add_action('esig_signature_saved', array(__CLASS__, 'cc_auto_trail_create'), 101, 1);
        add_action('esig_document_before_content_load', array(__CLASS__, 'signature_saved'), 101, 1);
        add_action('esig_document_before_display', array(__CLASS__, 'signature_saved'), 101, 1);

        add_action('init', array(__CLASS__, 'cc_preview'), -8);

        add_filter("can_view_preview_document", array(__CLASS__, 'document_allow'), 9, 2);

        add_filter("esig_preview_login_required", array(__CLASS__, 'login_required'), 9, 2);
    }

    public static function login_required($preview, $docId)
    {
        if (esigget('ccpreview')) {
           
            if (!self::is_cc_enabled($docId)) return $preview;
            // allow document without cc hash for a while. 
            return "2";
            $ccInformations = self::get_cc_information($docId, true);
            $cc_hash = esigget('cc_user_preview');
            foreach ($ccInformations as $ccInfo) {
               
                if (in_array($cc_hash, $ccInfo)) {
                    $preview = "2";
                }
            }
        }

        return $preview;
    }

    public static function document_allow($allow, $docId)
    {
        if (!self::is_cc_enabled($docId)) return $allow;

        // allow document view from url  where cc hash not found
        return true;

        if (esigget('cc_user_preview')) {

            $ccInformations = self::get_cc_information($docId, true);
            $cc_hash = esigget('cc_user_preview');
            foreach ($ccInformations as $ccInfo) {
                
                if (in_array($cc_hash, $ccInfo)) {
                    return true;
                } else {
                    $allow = false;
                }
            }
        }
        
        return $allow;
    }

    public static function cc_preview()
    {
        if (esigget('ccpreview')) {
            $docId = esigget('document_id');
            $cc_hash = esigget('cc_hash');
            
            $ccInformations = self::get_cc_information($docId, true);
            if (!$ccInformations) return false;
            // old url support added .
            if (!$cc_hash) {
               return true;
            }
            foreach ($ccInformations as $ccInfo) {
                // old url support added .
                
                if (in_array($cc_hash, $ccInfo)) {
                    wp_redirect(self::cc_preview_url($docId, $cc_hash));
                    exit;
                }
            }
        }
        return false;
    }

    public static function is_approval_document($document_id)
    {
        $assign_approval = WP_E_Sig()->meta->get($document_id, 'esig_assign_approval_signer');

        if ($assign_approval) {
            return true;
        }
        return false;
    }

    public static function approval_created($document_id)
    {
        $approval_created = WP_E_Sig()->meta->get($document_id, 'approval_signer_created');
        if ($approval_created) {
            return true;
        }
        return false;
    }

    public static function cc_auto_trail_create($PostData)
    {

        $document_id = $PostData['invitation']->document_id;


        if (!self::is_cc_enabled($document_id)) {
            return false;
        }
        // generate an object to pass value in email templates 
        $cc_users = new stdClass();

        $cc_users->doc = WP_E_Sig()->document->getDocument($document_id);

        $docType = $cc_users->doc->document_type;

        $cc_users->owner_name = self::get_owner_name($cc_users->doc->user_id);
        $cc_users->owner_email = self::get_owner_email($cc_users->doc->user_id);
        /* $cc_users->organization_name = self::get_organization_name($cc_users->doc->user_id);


        $cc_users->signed_link = self::get_cc_preview($cc_users->doc->document_checksum);
        $cc_users->wpUserId = $cc_users->doc->user_id;*/

        $signers = self::get_cc_information($document_id, false);

        foreach ($signers as $user_info) {
            $cc_users->user_info = $user_info;
            // $this->invitationsController->saveThenSend($invitation, $doc);
            $formIntegration = WP_E_Sig()->document->getFormIntegration($document_id);
            if ($docType == "stand_alone" || !empty($formIntegration)) {
                self::cc_record_event($document_id, $cc_users->owner_name, $cc_users->owner_email, $user_info->first_name, $user_info->email_address);
            }
        }
    }

    public static function signature_saved($PostData)
    {

        $document_id = $PostData['invitation']->document_id;

        $signer_id = $PostData['invitation']->user_id;

        if (!self::is_cc_enabled($document_id)) {
            return false;
        }
        // generate an object to pass value in email templates 
        $cc_users = new stdClass();

        $cc_users->doc = WP_E_Sig()->document->getDocument($document_id);

        $docType = $cc_users->doc->document_type;

        $cc_users->owner_name = self::get_owner_name($cc_users->doc->user_id);
        $cc_users->owner_email = self::get_owner_email($cc_users->doc->user_id);
        $cc_users->organization_name = self::get_organization_name($cc_users->doc->user_id);

        $cc_users->wpUserId = $cc_users->doc->user_id;
        if ($docType == "stand_alone") {
            $cc_users->signers = WP_E_Sig()->signer->get_document_signer_info($signer_id, $document_id);
            $subject = sprintf(__("You have been copied on %s - signed by %s", "esig"), $cc_users->doc->document_title, $cc_users->signers->signer_name);
        } else {
            $cc_users->signers = WP_E_Sig()->signer->get_document_signer_info($signer_id, $document_id);
            $subject = sprintf(__("You have been copied on %s - signed by %s", "esig"), $cc_users->doc->document_title, $cc_users->signers->signer_name);
        }



        if ($docType == "stand_alone") {
            if (class_exists("esigBrandingSetting") && esigBrandingSetting::instance()->esig_plain_email_template()) {
                $notify_template = ESIGN_CC_PATH . '/views/sad-signed-email-plain-template.php';
            } else {
                $notify_template = ESIGN_CC_PATH . '/views/sad-signed-email-template.php';
            }
        } else {
            if (class_exists("esigBrandingSetting") && esigBrandingSetting::instance()->esig_plain_email_template()) {
                $notify_template = ESIGN_CC_PATH . '/views/signed-email-plain-template.php';
            } else {
                $notify_template = ESIGN_CC_PATH . '/views/signed-email-template.php';
            }
        }

        $signers = self::get_cc_information($document_id, false);

        $attachments = false;
        $allSigned = WP_E_Sig()->document->getSignedresult($document_id);
        if (!self::is_approval_document($document_id) && $allSigned) {
            $attachments = apply_filters('esig_email_pdf_attachment', array('document' => $cc_users->doc));
        } else {
            if (self::approval_created($document_id) && $allSigned) {
                $attachments = apply_filters('esig_email_pdf_attachment', array('document' => $cc_users->doc));
            }
        }


        if (is_array($attachments) || empty($attachments)) {
            $attachments = false;
        }

        $mailsent = false;
        foreach ($signers as $user_info) {
            $cc_users->user_info = $user_info;

            $cc_users->signed_link = self::get_cc_preview($cc_users->doc->document_id, esigget("cc_hash",$user_info));

            $email_temp = WP_E_Sig()->view->renderPartial('', $cc_users, false, '', $notify_template);
            $mailsent = WP_E_Sig()->email->esig_mail($cc_users->owner_name, $cc_users->owner_email, $user_info->email_address, $subject, $email_temp, $attachments);
            // $this->invitationsController->saveThenSend($invitation, $doc);


        }

        do_action('esig_cc_email_sent', array('document' => $cc_users->doc));
    }

    public static function cc_record_event($document_id, $sender_name, $sender_email, $cc_name, $cc_email)
    {
        $ipAddress = WP_E_Sig()->document->docIp($document_id);
        $event_text = sprintf(__("%s - %s added by %s - %s as a CC'd Recipient Ip: %s", 'esig'), esig_unslash($cc_name), $cc_email, esig_unslash($sender_name), $sender_email, $ipAddress);
        WP_E_Sig()->document->recordEvent($document_id, 'document_cc', $event_text);
    }
}
