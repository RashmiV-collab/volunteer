<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class WP_E_optionsController 
{

    public function __construct()
    {
    }

    public function pdf()
    {


        if (count($_POST) > 0 && esigpost('esig-pdf-option-submit')) {

            WP_E_Sig()->view->setAlert(array('type' => 'alert e-sign-alert esig-updated', 'title' => '', 'message' => __('<strong>Well done</strong> : Your E-Signature PDF settings have been updated.', 'esig')));

            // call an action hook to trigger save as pdf options 
            do_action('esig_pdf_settings_save');
        }


        $misc_more_actions = apply_filters('esig_misc_more_document_actions', '');

        $class = (isset($_GET['page']) && $_GET['page'] == 'esign-pdf-option') ? 'misc_current' : '';
        $esigGeneral = new WP_E_General();

        $template_data = array(
            "post_action" => 'admin.php?page=esign-pdf-option',
            "misc_tab_class" => 'nav-tab-active',
            "customizztion_more_links" => $misc_more_actions,
            "Licenses" => $esigGeneral->checking_extension(),
            "link_active" => $class,
            "tab_name" => "Customization"
        );

        // sets message 
        $template_data["message"] = WP_E_Sig()->view->renderAlerts();

        $template_filter = apply_filters('esig-pdf-form-data', $template_data, array());
        $template_data = array_merge($template_data, $template_filter);

        $templatePath  = ESIG_PDF_ADMIN_PATH . "/views/admin.php";
        $esigView = new WP_E_View();
        $esigView->render($templatePath, false , $template_data);
    }
}
