<?php

 class WP_E_Update extends WP_E_Model {

    public function __construct() {
        parent::__construct();
    }

    /**
     *  Add a transient to the database for 24 hours to run the automatic update 
     * @return void 
     */
    public function enableAutomaticUpdateEvent($enableAutoUpdate)
    {
        if (!wp_validate_boolean($enableAutoUpdate) && get_transient('esig_donotrun_automatic_addon_update')) {
            delete_transient('esig_run_automatic_addon_update');
        } elseif (wp_validate_boolean($enableAutoUpdate) && !get_transient('esig_donotrun_automatic_addon_update')) {
            set_transient('esig_donotrun_automatic_addon_update', true, 24 * HOUR_IN_SECONDS);
        } 
    }

    /**
     *  Check if the automatic update event is enabled
     * @return boolean 
     */
    public function isAutomaticUpdateEventEnabled(){
        $esigAutoUpdate = WP_E_Sig()->setting->get_generic('esign_auto_update');
        if(wp_validate_boolean($esigAutoUpdate) && !wp_validate_boolean(get_transient('esig_donotrun_automatic_addon_update'))){
            $this->enableAutomaticUpdateEvent($esigAutoUpdate);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Reset the automatic update event
     * @return void
     */
    public function resetAutomaticUpdateEvent(){
        delete_transient('esig_donotrun_automatic_addon_update');
    }

    /**
     * Check if the automatic update  is enabled
     */

    public function isAutomaticUpdateEnabled(){
        $esigAutoUpdate = WP_E_Sig()->setting->get_generic('esign_auto_update');
        if(wp_validate_boolean($esigAutoUpdate)){
            return true;
        } else {
            return false;
        }
    }

    

 }
