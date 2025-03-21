<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

if (!class_exists('ESIG_PDF_OPTION')) :

    class ESIG_PDF_OPTION
    {

        /**
         * Initialize the plugin by loading admin scripts & styles and adding a
         * settings page and menu.
         * @since     0.1
         */
        public static function Init()
        {
            // usr action 
            add_filter('esig_misc_more_document_actions', array(__CLASS__, 'esig_misc_page_more_acitons'), 10, 1);
            add_action('admin_menu', array(__CLASS__, 'adminMenu'));
          
        }

        final static function esig_misc_page_more_acitons($misc_more_actions)
        {

            $class = (isset($_GET['page']) && $_GET['page'] == 'esign-pdf-option') ? 'misc_current' : '';
            $misc_more_actions .= ' | <a class="misc_link ' . $class . '" href="admin.php?page=esign-pdf-option">' . __('PDF Option', 'esig') . '</a>';
            return $misc_more_actions;
        }

        final static function adminMenu()
        {
            $esigClass = new Esign_core_load();
            add_submenu_page(" ", __('E-mails', 'esig'), __('E-mails', 'esig'), 'read', 'esign-pdf-option', array(&$esigClass, 'route'));
        }
    }





endif;
