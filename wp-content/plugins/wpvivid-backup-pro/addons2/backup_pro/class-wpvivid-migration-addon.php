<?php

/**
 * WPvivid addon: yes
 * Addon Name: wpvivid-backup-pro-all-in-one
 * Description: Pro
 * Version: 2.2.21
 * Need_init: yes
 * Interface Name: WPvivid_Migration_Page_addon
 */
if (!defined('WPVIVID_BACKUP_PRO_PLUGIN_DIR'))
{
    die;
}

if(!defined('WPVIVID_PRO_REMOTE_SEND_TO_SITE'))
    define('WPVIVID_PRO_REMOTE_SEND_TO_SITE','send_to_site');

class WPvivid_Migration_Page_addon
{
    public $main_tab;

    public function __construct()
    {
        //add_filter('wpvivid_get_toolbar_menus', array($this, 'get_toolbar_menus'),11);

        add_filter('wpvivid_put_transfer_key', array($this, 'wpvivid_put_transfer_key'), 11);

        add_action('wp_ajax_wpvivid_send_backup_to_site_addon',array( $this,'send_backup_to_site'));
        add_action('wp_ajax_wpvivid_hide_auto_migration_success_notice', array($this, 'hide_auto_migration_success_notice'));

        //init
        //add_action('wpvivid_backup_do_js_addon', array($this, 'wpvivid_backup_do_js_addon'), 11);
        //dashboard
        //add_filter('wpvivid_get_dashboard_menu', array($this, 'get_dashboard_menu'), 10, 2);
        //add_filter('wpvivid_get_dashboard_screens', array($this, 'get_dashboard_screens'), 10);

        //add_filter('wpvivid_get_role_cap_list',array($this, 'get_caps'));
    }

    public function get_caps($cap_list)
    {
        $cap['slug']='wpvivid-can-migrate';
        $cap['display']='Migrate websites';
        $cap['menu_slug']=strtolower(sprintf('%s-migration', apply_filters('wpvivid_white_label_slug', 'wpvivid')));
        $cap_list[$cap['slug']]=$cap;

        return $cap_list;
    }

    public function get_dashboard_screens($screens)
    {
        $screen['menu_slug']='wpvivid-migration';
        $screen['screen_id']='wpvivid-plugin_page_wpvivid-migration';
        $screen['is_top']=false;
        $screens[]=$screen;
        return $screens;
    }

    public function get_dashboard_menu($submenus,$parent_slug)
    {
        $display = apply_filters('wpvivid_get_menu_capability_addon', 'menu_migration');
        if($display)
        {
            $submenu['parent_slug'] = $parent_slug;
            $submenu['page_title'] = apply_filters('wpvivid_white_label_display', 'Auto-Migration');
            $submenu['menu_title'] = 'Auto-Migration';
            $submenu['capability'] = apply_filters("wpvivid_menu_capability","administrator","wpvivid-migration");
            $submenu['menu_slug'] = strtolower(sprintf('%s-migration', apply_filters('wpvivid_white_label_slug', 'wpvivid')));
            $submenu['index'] = 3;
            $submenu['function'] = array($this, 'init_page');
            $submenus[$submenu['menu_slug']] = $submenu;
        }
        return $submenus;
    }

    public function get_toolbar_menus($toolbar_menus)
    {
        $admin_url = apply_filters('wpvivid_get_admin_url', '');
        $display = apply_filters('wpvivid_get_menu_capability_addon', 'menu_migration');
        if($display) {
            $menu['id'] = 'wpvivid_admin_menu_migration';
            $menu['parent'] = 'wpvivid_admin_menu';
            $menu['title'] = 'Auto-Migration';
            $menu['tab'] = 'admin.php?page=' . apply_filters('wpvivid_white_label_plugin_name', 'wpvivid-migration');
            $menu['href'] = $admin_url . 'admin.php?page=' . apply_filters('wpvivid_white_label_plugin_name', 'wpvivid-migration');
            $menu['capability'] = apply_filters("wpvivid_menu_capability","administrator","wpvivid-migration");
            $menu['index'] = 3;
            $toolbar_menus[$menu['parent']]['child'][$menu['id']] = $menu;
        }
        return $toolbar_menus;
    }

    public function wpvivid_put_transfer_key($html)
    {
        $options=WPvivid_Setting::get_option('wpvivid_saved_api_token');
        if(empty($options)){
            ob_start();
            ?>
            <div class="wpvivid-one-coloum wpvivid-workflow" style="margin-top:1em;">
                <span>
                    <h2>Step 1: Paste the key below:
                        <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip">
                            <div class="wpvivid-bottom">
                                <h3>How to get a site key?</h3>
                                <!-- The content you need -->
                                <p>1. Go to the destination site > WPvivid Plugin > Auto-Migration tab > General A Key sub-tab.</p>
                                <p>2. Generate a key by clicking Generate button and copy it.</p>
                                <p>3. Go back to this page and paste the key into the field below and click Save button.</p>
                                <i></i> <!-- do not delete this line -->
                            </div>
                        </span>
                    </h2>
                </span>
                <textarea type="text" id="wpvivid_transfer_key_text" onkeyup="wpvivid_check_key(this.value)" style="width: 100%; height: 140px; margin-bottom:1em;"></textarea>
                <input class="button-primary" id="wpvivid_save_url_button" type="submit" value="Save" onclick="wpvivid_click_save_site_url();">

                <p></p>
                <div>
                    <span>Tips: Some web hosts may restrict the connection between the two sites, so you may get a 403 error or unstable connection issue when performing auto migration. In that case, it is recommended to use <a href="https://docs.wpvivid.com/custom-migration-overview.html" target="_blank" style="text-decoration: none;">the 'manual transfer' or 'migrate via remote storage' option</a> to migrate.</span>
                </div>

            </div>
            <?php
            $html = ob_get_clean();
        }
        else{
            $token='';
            $source_dir='';
            $target_dir='';
            $key_status='';
            foreach ($options as $key => $value)
            {
                $token = $value['token'];
                $source_dir=home_url();
                $target_dir=$value['domain'];
                $expires=$value['expires'];

                if ($expires != 0 && time() > $expires) {
                    $key_status='<span>Error: The key has expired. Please delete it first and paste a new one.</span>';
                }
                else{
                    $time_diff = $expires - time();
                    $key_status = '<p><span>The key will expire in: </span><span>'.date("H:i:s",$time_diff).'</span></p>
                                   <p><span>Connection Status:</span><span class="wpvivid-rectangle wpvivid-green">OK</span></p>
                                   <p><span>Now you can transfer the site <code>'.$source_dir.'</code> to the site <code>'.$target_dir.'</code></span></p>';
                }
            }
            ob_start();
            ?>
            <div class="wpvivid-one-coloum wpvivid-workflow">
            <span>
                <h2>Step 1: Paste the key below:
                    <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip">
                        <div class="wpvivid-bottom">
                            <h3>How to get a site key?</h3>
                            <!-- The content you need -->
                            <p>1. Go to the destination site > WPvivid Plugin > Auto-Migration tab > General A Key sub-tab.</p>
                            <p>2. Generate a key by clicking Generate button and copy it.</p>
                            <p>3. Go back to this page and paste the key into the field below and click Save button.</p>
                            <i></i> <!-- do not delete this line -->
                        </div>
                    </span>
                </h2>
            </span>
                <span>Key:</span>
                <input type="text" id="wpvivid_send_remote_site_url_text" value="<?php echo $token; ?>" readonly="readonly">
                <input class="button-primary" id="wpvivid_delete_key_button" type="submit" value="Delete" onclick="wpvivid_click_delete_transfer_key();">
                <p>
                    <?php echo $key_status; ?>
                </p>
            </div>

            <div class="wpvivid-one-coloum wpvivid-workflow" style="margin-top:1em;">
                <span>
                    <h2>
                        <span style="line-height: 30px;">Step 2: Select what to migrate</span>
                        <!--<input class="button" type="submit" id="wpvivid_recalc_migration_size" value="Re-Calc" />
                        <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip" style="margin-top: 4px;">
                            <div class="wpvivid-bottom">
                                <p>Recalculate sizes of the contents to be backed up after you finish selecting them.</p>
                                <i></i>
                            </div>
                        </span>-->
                    </h2>
                </span>
                <p></p>

                <p><span class="dashicons dashicons-screenoptions wpvivid-dashicons-blue"></span><span><strong>Backup Content</strong></span></p>
                <div style="padding:1em;margin-bottom:1em;background:#eaf1fe;border-radius:8px;">
                    <?php
                    if(!is_multisite())
                    {
                        $fieldset_style = '';
                        ?>
                        <fieldset style="<?php esc_attr_e($fieldset_style); ?>">
                            <?php
                            $html = '';
                            echo apply_filters('wpvivid_add_backup_type_addon', $html, 'backup_files');
                            ?>
                        </fieldset>
                        <?php
                    }
                    else{
                        $fieldset_style = '';
                        ?>
                        <div style="padding:1em 1em 1em 1em;margin-bottom:1em;background:#eaf1fe;border-radius:0.8em;">
                            <div style="">
                                <fieldset style="<?php esc_attr_e($fieldset_style); ?>">
                                    <?php
                                    $html = '';
                                    echo apply_filters('wpvivid_add_backup_type_addon', $html, 'backup_files');
                                    ?>
                                </fieldset>
                            </div>
                            <div id="wpvivid_custom_manual_backup_mu_single_site_list" style="display: none;">
                                <p>Choose the childsite you want to migrate</p>
                                <p>
                                    <span style="padding-right:0.2em;">
                                        <input type="search" style="margin-bottom: 4px; width:300px;" id="wpvivid-mu-single-site-search-input" placeholder="Enter title, url or description" name="s" value="">
                                    </span>
                                    <span><input type="submit" id="wpvivid-mu-single-search-submit" class="button" value="Search"></span>
                                </p>

                                <div id="wpvivid_mu_single_site_list"></div>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                </div>

                <div id="wpvivid_custom_migration_backup" style="display: none;">
                    <?php
                    $general_setting=WPvivid_Setting::get_setting(true, "");
                    if(isset($general_setting['options']['wpvivid_common_setting']['use_new_custom_backup_ui'])){
                        if($general_setting['options']['wpvivid_common_setting']['use_new_custom_backup_ui']){
                            $use_new_custom_backup_ui = '1';
                        }
                        else{
                            $use_new_custom_backup_ui = '';
                        }
                    }
                    else{
                        $use_new_custom_backup_ui = '';
                    }
                    if($use_new_custom_backup_ui == '1'){
                        $custom_backup_manager = new WPvivid_Custom_Backup_Manager_Ex('wpvivid_custom_migration_backup','migration_backup');
                    }
                    else{
                        $custom_backup_manager = new WPvivid_Custom_Backup_Manager('wpvivid_custom_migration_backup','migration_backup', '1', '0');
                    }
                    //$custom_backup_manager = new WPvivid_Custom_Backup_Manager('wpvivid_custom_migration_backup','migration_backup');
                    $custom_backup_manager->output_custom_backup_table();
                    $custom_backup_manager->load_js();
                    ?>
                </div>
                <div id="wpvivid_custom_migration_backup_mu_single_site" style="display: none;">
                    <?php
                    $type = 'manual_backup';
                    do_action('wpvivid_custom_backup_setting', 'wpvivid_custom_manual_backup_mu_single_site_list', 'wpvivid_custom_migration_backup_mu_single_site', $type, '0');
                    ?>
                </div>
            </div>

            <div class="wpvivid-one-coloum wpvivid-workflow" style="margin-top:1em;">
                <span>
                    <h2>Step 3: Perform the migration
                        <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip">
                            <div class="wpvivid-bottom">
                                <!-- The content you need -->
                                <p>The unstable connection between sites could cause a failure of files transfer. In this case, uploading backups to destination site is a good alternative to the automatic website migration.</p>
                                <i></i> <!-- do not delete this line -->
                            </div>
                        </span>
                    </h2></span>
                <p></p>
                <div>
                    <input class="button-primary" style="width: 200px; height: 50px; font-size: 20px; margin-bottom: 10px; pointer-events: auto; opacity: 1;" id="wpvivid_quickbackup_btn" type="submit" value="Clone then Transfer">
                    <div class="wpvivid-element-space-bottom" style="text-align: left;">
                        <div>
                            <p>
                                <span class="dashicons dashicons-pressthis wpvivid-dashicons-orange"></span><span>1. In order to successfully complete the migration, you'd better deactivate <a href="https://wpvivid.com/best-redirect-plugins.html" target="_blank" style="text-decoration: none;">301 redirect plugin</a>, <a href="https://wpvivid.com/8-best-wordpress-firewall-plugins.html" target="_blank" style="text-decoration: none;">firewall and security plugin</a>, and <a href="https://wpvivid.com/best-free-wordpress-caching-plugins.html" target="_blank" style="text-decoration: none;">caching plugin</a> (if they exist) before transferring website.</span>
                            </p>
                            <p>
                                <span class="dashicons dashicons-pressthis wpvivid-dashicons-orange"></span><span>2. Please migrate website with the manual way when using <strong>Local by Flywheel</strong> environment.</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <?php
            $html = ob_get_clean();
        }
        return $html;
    }

    public function send_backup_to_site()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('wpvivid-can-migrate');
        try
        {
            if(isset($_POST['backup'])&&!empty($_POST['backup']))
            {
                $options = WPvivid_Setting::get_option('wpvivid_saved_api_token');

                if (empty($options)) {
                    $ret['result'] = 'failed';
                    $ret['error'] = 'A key is required.';
                    echo json_encode($ret);
                    die();
                }

                $url = '';
                foreach ($options as $key => $value) {
                    $url = $value['url'];
                }

                if ($url === '') {
                    $ret['result'] = 'failed';
                    $ret['error'] = 'The key is invalid.';
                    echo json_encode($ret);
                    die();
                }

                if ($options[$url]['expires'] != 0 && $options[$url]['expires'] < time()) {
                    $ret['result'] = 'failed';
                    $ret['error'] = 'The key has expired.';
                    echo json_encode($ret);
                    die();
                }

                $json['test_connect']=1;
                $json=json_encode($json);
                $crypt=new WPvivid_crypt(base64_decode($options[$url]['token']));
                $data=$crypt->encrypt_message($json);
                $data=base64_encode($data);
                $args['body']=array('wpvivid_content'=>$data,'wpvivid_action'=>'send_to_site_connect');
                $response=wp_remote_post($url,$args);

                if ( is_wp_error( $response ) )
                {
                    $ret['result']=WPVIVID_PRO_FAILED;
                    $ret['error']= $response->get_error_message();
                    echo json_encode($ret);
                    die();
                }
                else
                {
                    if($response['response']['code']==200) {
                        $res=json_decode($response['body'],1);
                        if($res!=null) {
                            if($res['result']==WPVIVID_PRO_SUCCESS) {
                            }
                            else {
                                $ret['result']=WPVIVID_PRO_FAILED;
                                $ret['error']= $res['error'];
                                echo json_encode($ret);
                                die();
                            }
                        }
                        else {
                            $ret['result']=WPVIVID_PRO_FAILED;
                            $ret['error']= 'failed to parse returned data, unable to establish connection with the target site.';
                            $ret['response']=$response;
                            echo json_encode($ret);
                            die();
                        }
                    }
                    else {
                        $ret['result']=WPVIVID_PRO_FAILED;
                        $ret['error']= 'upload error '.$response['response']['code'].' '.$response['body'];
                        echo json_encode($ret);
                        die();
                    }
                }

                $json = $_POST['backup'];
                $json = stripslashes($json);
                $backup_options = json_decode($json, true);
                if (is_null($backup_options))
                {
                    die();
                }

                $remote_option['url'] = $options[$url]['url'];
                $remote_option['token'] = $options[$url]['token'];
                $remote_option['type'] = WPVIVID_PRO_REMOTE_SEND_TO_SITE;
                $remote_options['temp'] = $remote_option;
                $backup_options['remote_options'] = $remote_options;
                $backup_options = apply_filters('wpvivid_custom_backup_options', $backup_options);
                if(!isset($backup_options['type']))
                {
                    $backup_options['type']='Manual';
                    $backup_options['action']='backup';
                }

                global $wpvivid_plugin;

                $ret = $wpvivid_plugin->check_backup_option($backup_options, $backup_options['type']);
                if($ret['result']!=WPVIVID_PRO_SUCCESS)
                {
                    echo json_encode($ret);
                    die();
                }

                if(isset($_POST['is_export']))
                {
                    $backup_options['is_export'] = true;
                }

                //$ret=$wpvivid_plugin->pre_backup($backup_options);
                $ret=$this->pre_backup($backup_options);
                if($ret['result']=='success')
                {
                    //Check the website data to be backed up
                    $ret['check']=$wpvivid_plugin->check_backup($ret['task_id'],$backup_options);
                    if(isset($ret['check']['result']) && $ret['check']['result'] == WPVIVID_PRO_FAILED)
                    {
                        echo json_encode(array('result' => WPVIVID_PRO_FAILED,'error' => $ret['check']['error']));
                        die();
                    }

                    $html = '';
                    $ret['html'] = $html;
                }
                echo json_encode($ret);
                die();
            }
        }
        catch (Exception $error)
        {
            $ret['result']='failed';
            $message = 'An exception has occurred. class:'.get_class($error).';msg:'.$error->getMessage().';code:'.$error->getCode().';line:'.$error->getLine().';in_file:'.$error->getFile().';';
            $ret['error'] = $message;
            $id=uniqid('wpvivid-');
            $log_file_name=$id.'_backup';
            $log=new WPvivid_Log();
            $log->CreateLogFile($log_file_name,'no_folder','backup');
            $log->WriteLog($message,'notice');
            $log->CloseFile();
            WPvivid_error_log::create_error_log($log->log_file);
            error_log($message);
            echo json_encode($ret);
            die();
        }
        die();
    }

    public function pre_backup($backup_options)
    {
        global $wpvivid_plugin;
        if(apply_filters('wpvivid_need_clean_oldest_backup',true,$backup_options))
        {
            $wpvivid_plugin->clean_oldest_backup();
        }
        //do_action('wpvivid_clean_oldest_backup',$backup_options);

        if(WPvivid_taskmanager::is_tasks_backup_running())
        {
            $ret['result']='failed';
            $ret['error']=__('We detected that there is already a running backup task. Please wait until it completes then try again.', 'wpvivid');
            return $ret;
        }

        if(!class_exists('WPvivid_Backup_Task_Ex'))
        {
            include WPVIVID_BACKUP_PRO_PLUGIN_DIR . 'addons2/backup_pro/class-wpvivid-backup-task-addon.php';
        }

        $backup=new WPvivid_Backup_Task_Ex();
        $ret=$backup->new_backup_task($backup_options,$backup_options['type'],$backup_options['action']);
        return $ret;
    }

    public function hide_auto_migration_success_notice()
    {
        check_ajax_referer( 'wpvivid_ajax', 'nonce' );
        try
        {
            update_option('wpvivid_display_auto_migration_success_notice', false);
            $ret['result']='success';
            echo json_encode($ret);
        }
        catch (Exception $error)
        {
            $ret['result']='failed';
            $message = 'An exception has occurred. class:'.get_class($error).';msg:'.$error->getMessage().';code:'.$error->getCode().';line:'.$error->getLine().';in_file:'.$error->getFile().';';
            $ret['error'] = $message;
            echo json_encode($ret);
        }
        die();
    }

    public function wpvivid_backup_do_js_addon()
    {
        $backup_addon = new WPvivid_Backup_Restore_Page_addon();
        $ret = $backup_addon->_list_tasks_addon();
        ?>
        <script>
            <?php
            $general_setting=WPvivid_Setting::get_setting(true, "");
            if(!isset($general_setting['options']['wpvivid_common_setting']['estimate_backup'])&&$general_setting['options']['wpvivid_common_setting']['estimate_backup'] == 0)
            {
            ?>
            jQuery('.wpvivid_estimate_backup_info').hide();
            <?php
            }
            ?>
            var data = <?php echo json_encode($ret) ?>;
            wpvivid_list_task_data(data);
        </script>
        <?php
    }

    public function add_progress()
    {
        ?>
        <div class="wpvivid-one-coloum" id="wpvivid_postbox_backup_percent" style="display: none;">
            <div class="wpvivid-one-coloum wpvivid-workflow wpvivid-clear-float">
                <p><span class="wpvivid-span-progress"><span class="wpvivid-span-processed-progress wpvivid-span-processed-percent-progress">53% completed</span></span></p>
                <p>
                    <!--<span class="dashicons dashicons-list-view wpvivid-dashicons-blue wpvivid_estimate_backup_info"></span><span class="wpvivid_estimate_backup_info">Database Size:</span><span class="wpvivid_estimate_backup_info" id="wpvivid_backup_database_size">N/A</span>
                    <span class="dashicons dashicons-portfolio wpvivid-dashicons-orange wpvivid_estimate_backup_info"></span><span class="wpvivid_estimate_backup_info">File Size:</span><span class="wpvivid_estimate_backup_info" id="wpvivid_backup_file_size">N/A</span>-->
                    <span class="dashicons dashicons-admin-page wpvivid-dashicons-green"></span><span>Total Size:</span><span>N/A</span>
                    <span class="dashicons dashicons-upload wpvivid-dashicons-blue"></span><span>Uploaded:</span><span>N/A</span>
                    <span class="dashicons dashicons-plugins-checked wpvivid-dashicons-green"></span><span>Speed:</span><span>N/A</span>
                    <span class="dashicons dashicons-networking wpvivid-dashicons-green"></span><span>Network Connection:</span><span>OK</span>
                </p>
                <p><span class="dashicons dashicons-welcome-write-blog wpvivid-dashicons-grey"></span><span>Action:</span><span>running</span></p>
                <div><input class="button-primary" id="wpvivid_backup_cancel_btn" type="submit" value="Cancel"></div>
            </div>
        </div>

        <script>
            jQuery('#wpvivid_postbox_backup_percent').on("click", "input", function() {
                if(jQuery(this).attr('id') === 'wpvivid_backup_cancel_btn')
                {
                    wpvivid_cancel_backup();
                }
            });

            function wpvivid_cancel_backup() {
                var ajax_data= {
                    'action': 'wpvivid_backup_cancel_ex'
                };
                jQuery('#wpvivid_backup_cancel_btn').css({'pointer-events': 'none', 'opacity': '0.4'});
                wpvivid_post_request_addon(ajax_data, function(data){
                    try {
                        var jsonarray = jQuery.parseJSON(data);
                        jQuery('#wpvivid_current_doing').html(jsonarray.msg);
                    }
                    catch(err){
                        alert(err);
                    }
                }, function(XMLHttpRequest, textStatus, errorThrown) {
                    jQuery('#wpvivid_backup_cancel_btn').css({'pointer-events': 'auto', 'opacity': '1'});
                    var error_message = wpvivid_output_ajaxerror('cancelling the backup', textStatus, errorThrown);
                    wpvivid_add_notice('Backup', 'Error', error_message);
                });
            }
        </script>
        <?php
    }

    public function init_page()
    {
        do_action('wpvivid_before_setup_page');
        ?>
        <div class="wrap wpvivid-canvas">
            <div class="icon32"></div>
            <h1><?php esc_attr_e( apply_filters('wpvivid_white_label_display', 'WPvivid').' Plugins - Auto-Migration', 'wpvivid' ); ?></h1>
            <div id="wpvivid_backup_notice">
                <?php
                $display_notice = get_option('wpvivid_display_auto_migration_success_notice', false);
                if($display_notice)
                {
                    ?>
                    <div class="notice notice-success notice-transfer-success is-dismissible inline" style="margin-bottom: 5px;">
                        <p>Transfer succeeded. Please scan the backup list on the destination site to display the backup, then restore the backup.</p>
                    </div>
                    <?php
                }
                ?>
            </div>
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <!-- main content -->
                    <div id="post-body-content">
                        <div class="meta-box-sortables ui-sortable">
                            <div class="wpvivid-backup">
                                <div class="wpvivid-welcome-bar wpvivid-clear-float">
                                    <div class="wpvivid-welcome-bar-left">
                                        <p><span class="dashicons dashicons-migrate wpvivid-dashicons-large wpvivid-dashicons-blue"></span><span class="wpvivid-page-title">Auto Migration</span></p>
                                        <span class="about-description">The fastest method to migrate a small/medium-sized WordPress website.</span>
                                        <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip">
                                            <div class="wpvivid-bottom">
                                                <p>The feature can help you transfer a Wordpress site to a new domain(site). It would be a convenient way to migrate your WP site from dev environment to live server or from old server to the new.</p>
											    <i></i> <!-- do not delete this line -->
                                            </div>
									    </span>
                                    </div>
                                    <div class="wpvivid-welcome-bar-right">
                                        <p></p>
                                        <div style="float:right;">
                                            <span>Local Time:</span>
                                            <span>
                                                <a href="<?php esc_attr_e(apply_filters('wpvivid_get_admin_url', '').'options-general.php'); ?>">
                                                    <?php
                                                    $offset=get_option('gmt_offset');
                                                    echo date("l, F-d-Y H:i",time()+$offset*60*60);
                                                    ?>
                                                </a>
                                            </span>
                                            <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip">
                                                <div class="wpvivid-left">
                                                    <p>Clicking the date and time will redirect you to the WordPress General Settings page where you can change your timezone settings.</p>
                                                    <i></i> <!-- do not delete this line -->
                                                </div>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="wpvivid-nav-bar wpvivid-clear-float">
                                        <span class="dashicons dashicons-lightbulb wpvivid-dashicons-orange"></span>
                                        <span> If cURL error 28 occurs, please navigate to WPvivid settings and lower the chunk size.</span>
                                        <!--<span class="dashicons dashicons-portfolio wpvivid-dashicons-orange"></span>
                                        <span>Localhost Storage Directory:</span>
                                        <span>
                                            <code>
                                                <?php
                                                $backupdir=WPvivid_Setting::get_backupdir();
                                                _e(WP_CONTENT_DIR.DIRECTORY_SEPARATOR.$backupdir);
                                                ?>
                                            </code>
                                        </span>
                                        <span><a href="admin.php?page=wpvivid-setting">rename directory</a></span>
                                        <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip">
                                            <div class="wpvivid-bottom">
                                                Click to change WPvivid Pro custom backup folder.
                                                <i></i>
                                            </div>
                                        </span>
                                        <span><a href="admin.php?page=wpvivid-backup-and-restore">or view backups list</a></span>
                                        <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip">
										<div class="wpvivid-bottom">
											<p>Click to browse and manage all your backups.</p>
											<i></i>
										</div>-->
									</span>
                                    </div>
                                </div>

                                <!---  backup progress --->
                                <?php
                                $this->add_progress();
                                ?>

                                <div class="wpvivid-canvas wpvivid-clear-float">
                                    <?php
                                    if(!class_exists('WPvivid_Tab_Page_Container_Ex'))
                                        include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR . 'includes/class-wpvivid-tab-page-container-ex.php';
                                    $this->main_tab=new WPvivid_Tab_Page_Container_Ex();
                                    $args['span_class']='dashicons dashicons-migrate wpvivid-dashicons-blue';
                                    $args['span_style']='margin-top:0.1em;';
                                    $args['div_style']='padding-top:0;display:block;';
                                    $args['is_parent_tab']=0;
                                    $this->main_tab->add_tab('Auto-Migration','auto_migration',array($this, 'output_auto_migration'), $args);
                                    $args['span_class']='dashicons dashicons-admin-network wpvivid-dashicons-green';
                                    $args['span_style']='margin-top:0.1em;';
                                    $args['div_style']='padding-top:0;';
                                    $args['is_parent_tab']=0;
                                    $this->main_tab->add_tab('Generate A Key','general_key',array($this, 'output_general_key'), $args);
                                    $this->main_tab->display();
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- sidebar -->
                    <?php
                    do_action( 'wpvivid_backup_pro_add_sidebar' );
                    ?>

                </div>
            </div>
        </div>

        <script>
            var m_need_update_addon=false;
            var wpvivid_prepare_backup=false;
            var running_backup_taskid='';
            var task_retry_times = 0;

            jQuery(document).ready(function (){
                wpvivid_activate_cron_addon();
                wpvivid_manage_task_addon();

                var wpvivid_migration_backup_table = wpvivid_migration_backup_table || {};
                wpvivid_migration_backup_table.init_refresh = false;

                var parent_id = 'wpvivid_custom_migration_backup';
                var type = 'migration_backup';
                if(!wpvivid_migration_backup_table.init_refresh){
                    wpvivid_migration_backup_table.init_refresh = true;
                    wpvivid_refresh_custom_backup_info(parent_id, type);
                    jQuery('#'+parent_id).find('.wpvivid-database-loading').addClass('is-active');
                    jQuery('#'+parent_id).find('.wpvivid-themes-plugins-loading').addClass('is-active');
                }

                jQuery(document).on('click', '.notice-transfer-success .notice-dismiss', function(){
                    var ajax_data = {
                        'action': 'wpvivid_hide_auto_migration_success_notice'
                    };
                    wpvivid_post_request(ajax_data, function(res){
                    }, function(XMLHttpRequest, textStatus, errorThrown) {
                    });
                });
            });

            function wpvivid_activate_cron_addon(){
                var next_get_time = 3 * 60 * 1000;
                wpvivid_cron_task();
                setTimeout("wpvivid_activate_cron_addon()", next_get_time);
                setTimeout(function(){
                    m_need_update_addon=true;
                }, 10000);
            }

            function wpvivid_manage_task_addon() {
                if(m_need_update_addon === true){
                    m_need_update_addon = false;
                    wpvivid_check_runningtask_addon();
                }
                else{
                    setTimeout(function(){
                        wpvivid_manage_task_addon();
                    }, 3000);
                }
            }

            function wpvivid_check_runningtask_addon() {
                var ajax_data = {
                    'action': 'wpvivid_list_tasks_addon'
                };

                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    setTimeout(function ()
                    {
                        wpvivid_manage_task_addon();
                    }, 3000);
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        wpvivid_list_task_data(jsonarray);
                    }
                    catch(err)
                    {
                        alert(err);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    setTimeout(function ()
                    {
                        m_need_update_addon = true;
                        wpvivid_manage_task_addon();
                    }, 3000);
                });

            }

            function wpvivid_list_task_data(data) {
                var b_has_data = false;

                if(data.action === 'auto_transfer'){
                    if(data.progress_html!==false)
                    {
                        jQuery('#wpvivid_postbox_backup_percent').show();
                        jQuery('#wpvivid_postbox_backup_percent').html(data.progress_html);
                    }
                    else
                    {
                        if(!wpvivid_prepare_backup)
                            jQuery('#wpvivid_postbox_backup_percent').hide();
                    }

                    var update_backup=false;
                    if (data.success_notice_html !== false)
                    {
                        jQuery('#wpvivid_backup_notice').show();
                        jQuery('#wpvivid_backup_notice').append(data.success_notice_html);
                        update_backup=true;
                        alert('Auto migration succeeded. Please scan the backup list on the destination site to display the backup, then restore the backup.');
                    }
                    if(data.error_notice_html !== false)
                    {
                        jQuery('#wpvivid_backup_notice').show();
                        jQuery('#wpvivid_backup_notice').append(data.error_notice_html);
                        update_backup=true;
                    }

                    if(update_backup)
                    {
                        jQuery( document ).trigger( 'wpvivid_update_local_backup');
                        jQuery( document ).trigger( 'wpvivid_update_log_list');
                    }

                    if(data.need_refresh_remote !== false){
                        jQuery( document ).trigger( 'wpvivid_update_remote_backup');
                    }

                    if(data.last_msg_html !== false)
                    {
                        jQuery('#wpvivid_last_backup_msg').html(data.last_msg_html);
                    }

                    if(data.need_update)
                    {
                        m_need_update_addon = true;
                    }

                    if(data.running_backup_taskid!== '')
                    {
                        b_has_data = true;
                        task_retry_times = 0;
                        running_backup_taskid = data.running_backup_taskid;
                        wpvivid_control_backup_lock();
                        if(data.wait_resume)
                        {
                            if (data.next_resume_time !== 'get next resume time failed.')
                            {
                                wpvivid_resume_backup(running_backup_taskid, data.next_resume_time);
                            }
                            else {
                                wpvivid_delete_backup_task(running_backup_taskid);
                            }
                        }
                    }
                    else
                    {
                        if(!wpvivid_prepare_backup)
                        {
                            jQuery('#wpvivid_backup_cancel_btn').css({'pointer-events': 'auto', 'opacity': '1'});
                            if(get_custom_table_retry.has_get_db_tables)
                                wpvivid_control_backup_unlock();
                        }
                        running_backup_taskid='';
                    }
                    if (!b_has_data)
                    {
                        task_retry_times++;
                        if (task_retry_times < 5)
                        {
                            m_need_update_addon = true;
                        }
                    }
                }
                else if(typeof data.action === 'undefined' && typeof data.need_update !== 'undefined' && !data.need_update && !wpvivid_prepare_backup){
                    jQuery('#wpvivid_postbox_backup_percent').hide();
                }
            }

            jQuery('input:radio[option=backup][name=backup_files]').click(function(){
                if(this.value === 'custom'){
                    jQuery('#wpvivid_custom_migration_backup').show();
                    jQuery('#wpvivid_custom_migration_backup_mu_single_site').hide();
                    jQuery('#wpvivid_custom_manual_backup_mu_single_site_list').hide();
                    jQuery( document ).trigger( 'wpvivid_refresh_migration_backup_tables', 'migration_backup' );
                }
                else if(this.value === 'mu'){
                    jQuery('#wpvivid_custom_migration_backup').hide();
                    jQuery('#wpvivid_custom_migration_backup_mu_single_site').show();
                    jQuery('#wpvivid_custom_manual_backup_mu_single_site_list').show();
                }
                else{
                    jQuery('#wpvivid_custom_migration_backup').hide();
                    jQuery('#wpvivid_custom_migration_backup_mu_single_site').hide();
                    jQuery('#wpvivid_custom_manual_backup_mu_single_site_list').hide();
                }
            });

            <?php
            $general_setting=WPvivid_Setting::get_setting(true, "");
            if(isset($general_setting['options']['wpvivid_common_setting']['use_new_custom_backup_ui'])){
                if($general_setting['options']['wpvivid_common_setting']['use_new_custom_backup_ui']){
                    $use_new_custom_backup_ui = '1';
                }
                else{
                    $use_new_custom_backup_ui = '0';
                }
            }
            else{
                $use_new_custom_backup_ui = '0';
            }
            ?>
            var use_new_custom_backup_ui = '<?php echo $use_new_custom_backup_ui; ?>';

            function wpvivid_check_backup_option_avail(type){
                if(type === 'migration_backup'){
                    var parent_id = 'wpvivid_custom_migration_backup';
                }

                var check_status = true;

                //check is backup db or files
                if(jQuery('#'+parent_id).find('.wpvivid-custom-database-part').prop('checked')){
                    var has_db_item = false;
                    if(jQuery('#'+parent_id).find('.wpvivid-custom-database-check').prop('checked')){
                        has_db_item = true;
                        var has_local_table_item = false;
                        jQuery('#'+parent_id).find('input:checkbox[name=migration_backup_database]').each(function(index, value){
                            if(jQuery(this).prop('checked')){
                                has_local_table_item = true;
                            }
                        });
                        if(!has_local_table_item){
                            check_status = false;
                            alert('Please select at least one table to back up. Or, deselect the option \'Tables In The WordPress Database\' under the option \'Databases Will Be Backed up\'.');
                            return check_status;
                        }
                    }
                    if(jQuery('#'+parent_id).find('.wpvivid-custom-additional-database-check').prop('checked')){
                        has_db_item = true;
                        var has_additional_db = false;
                        jQuery('#'+parent_id).find('.wpvivid-additional-database-list div').find('span:eq(2)').each(function(){
                            has_additional_db = true;
                        });
                        if(!has_additional_db){
                            check_status = false;
                            alert('Please select at least one additional database to back up. Or, deselect the option \'Include Additional Databases\' under the option \'Databases Will Be Backed up\'.');
                            return check_status;
                        }
                    }
                    if(!has_db_item){
                        check_status = false;
                        alert('Please select at least one option from \'Tables In The WordPress Database\' and \'Additional Databases\' under the option \'Databases Will Be Backed up\'. Or, deselect the option \'Databases Will Be Backed up\'.');
                        return check_status;
                    }
                }
                if(jQuery('#'+parent_id).find('.wpvivid-custom-file-part').prop('checked')){
                    var has_file_item = false;
                    if(jQuery('#'+parent_id).find('.wpvivid-custom-core-check').prop('checked')){
                        has_file_item = true;
                    }
                    if(jQuery('#'+parent_id).find('.wpvivid-custom-themes-check').prop('checked')){
                        has_file_item = true;
                    }
                    if(jQuery('#'+parent_id).find('.wpvivid-custom-plugins-check').prop('checked')){
                        has_file_item = true;
                    }
                    if(jQuery('#'+parent_id).find('.wpvivid-custom-content-check').prop('checked')){
                        has_file_item = true;
                    }
                    if(jQuery('#'+parent_id).find('.wpvivid-custom-uploads-check').prop('checked')){
                        has_file_item = true;
                    }
                    if(jQuery('#'+parent_id).find('.wpvivid-custom-additional-folder-check').prop('checked')){
                        has_file_item = true;
                        var has_additional_folder = false;
                        jQuery('#'+parent_id).find('.wpvivid-custom-include-additional-folder-list div').find('span:eq(2)').each(function(){
                            has_additional_folder = true;
                        });
                        if(!has_additional_folder){
                            check_status = false;
                            alert('Please select at least one additional file or folder under the option \'Files/Folders Will Be Backed up\', Or, deselect the option \'Non-WordPress Files/Folders\'.');
                            return check_status;
                        }
                    }
                    if(!has_file_item){
                        check_status = false;
                        alert('Please select at least one option under the option \'Files/Folders Will Be Backed up\'. Or, deselect the option \'Files/Folders Will Be Backed up\'.');
                        return check_status;
                    }
                }

                return check_status;
            }

            function wpvivid_check_additional_folder_valid(type){
                if(type === 'migration_backup'){
                    var parent_id = 'wpvivid_custom_migration_backup';
                }
                if(use_new_custom_backup_ui == '1'){
                    var check_status = true;
                }
                else{
                    var check_status = false;
                    if(jQuery('input:radio[option=backup][name=backup_files][value=custom]').prop('checked')){
                        if(jQuery('#'+parent_id).find('.wpvivid-custom-additional-folder-check').prop('checked')){
                            jQuery('#'+parent_id).find('.wpvivid-custom-include-additional-folder-list div').find('span:eq(2)').each(function(){
                                check_status = true;
                            });
                        }
                        else{
                            check_status = true;
                        }
                        if(check_status === false){
                            alert('Please select at least one item under the additional files/folder option, or deselect the option.');
                        }
                    }
                    else{
                        check_status = true;
                    }
                }
                return check_status;
            }

            function wpvivid_check_additional_db_valid(type){
                if(type === 'migration_backup'){
                    var parent_id = 'wpvivid_custom_migration_backup';
                }
                if(use_new_custom_backup_ui == '1'){
                    var check_status = true;
                }
                else{
                    var check_status = false;
                    if(jQuery('input:radio[option=backup][name=backup_files][value=custom]').prop('checked')){
                        if(jQuery('#'+parent_id).find('.wpvivid-custom-additional-database-check').prop('checked')){
                            jQuery('#'+parent_id).find('.wpvivid-additional-database-list div').find('span:eq(2)').each(function(){
                                check_status = true;
                            });
                        }
                        else{
                            check_status = true;
                        }
                        if(check_status === false){
                            alert('Please select at least one item under the additional database option, or deselect the option.');
                        }
                    }
                    else{
                        check_status = true;
                    }
                }

                return check_status;
            }

            function wpvivid_create_custom_setting_ex(custom_type){
                if(custom_type === 'migration_backup'){
                    var parent_id = 'wpvivid_custom_migration_backup';
                }
                var json = {};
                //exclude
                json['exclude_custom'] = '1';
                if(!jQuery('#'+parent_id).find('.wpvivid-custom-exclude-part').prop('checked')){
                    json['exclude_custom'] = '0';
                }

                //core
                json['core_check'] = '0';
                json['core_list'] = Array();
                if(jQuery('#'+parent_id).find('.wpvivid-custom-core-check').prop('checked')){
                    json['core_check'] = '1';
                }

                //themes
                json['themes_check'] = '0';
                json['themes_list'] = {};
                json['themes_extension'] = '';
                if(jQuery('#'+parent_id).find('.wpvivid-custom-themes-check').prop('checked')){
                    json['themes_check'] = '1';
                }
                if(json['exclude_custom'] == '1'){
                    jQuery('#'+parent_id).find('.wpvivid-custom-exclude-themes-list div').find('span:eq(2)').each(function (){
                        var folder_name = this.innerHTML;
                        json['themes_list'][folder_name] = {};
                        json['themes_list'][folder_name]['name'] = folder_name;
                        var type = jQuery(this).closest('div').attr('type');
                        if(type === 'folder'){
                            json['themes_list'][folder_name]['type'] = 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer';
                        }
                        else{
                            json['themes_list'][folder_name]['type'] = 'dashicons dashicons-media-default wpvivid-dashicons-grey wpvivid-icon-16px-nopointer';
                        }
                    });
                    json['themes_extension'] = jQuery('#'+parent_id).find('.wpvivid-themes-extension').val();
                }

                //plugins
                json['plugins_check'] = '0';
                json['plugins_list'] = {};
                json['plugins_extension'] = '';
                if(jQuery('#'+parent_id).find('.wpvivid-custom-plugins-check').prop('checked')){
                    json['plugins_check'] = '1';
                }
                if(json['exclude_custom'] == '1'){
                    jQuery('#'+parent_id).find('.wpvivid-custom-exclude-plugins-list div').find('span:eq(2)').each(function (){
                        var folder_name = this.innerHTML;
                        json['plugins_list'][folder_name] = {};
                        json['plugins_list'][folder_name]['name'] = folder_name;
                        var type = jQuery(this).closest('div').attr('type');
                        if(type === 'folder'){
                            json['plugins_list'][folder_name]['type'] = 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer';
                        }
                        else{
                            json['plugins_list'][folder_name]['type'] = 'dashicons dashicons-media-default wpvivid-dashicons-grey wpvivid-icon-16px-nopointer';
                        }
                    });
                    json['plugins_extension'] = jQuery('#'+parent_id).find('.wpvivid-plugins-extension').val();
                }

                //content
                json['content_check'] = '0';
                json['content_list'] = {};
                json['content_extension'] = '';
                if(jQuery('#'+parent_id).find('.wpvivid-custom-content-check').prop('checked')){
                    json['content_check'] = '1';
                }
                if(json['exclude_custom'] == '1'){
                    jQuery('#'+parent_id).find('.wpvivid-custom-exclude-content-list div').find('span:eq(2)').each(function (){
                        var folder_name = this.innerHTML;
                        json['content_list'][folder_name] = {};
                        json['content_list'][folder_name]['name'] = folder_name;
                        var type = jQuery(this).closest('div').attr('type');
                        if(type === 'folder'){
                            json['content_list'][folder_name]['type'] = 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer';
                        }
                        else{
                            json['content_list'][folder_name]['type'] = 'dashicons dashicons-media-default wpvivid-dashicons-grey wpvivid-icon-16px-nopointer';
                        }
                    });
                    json['content_extension'] = jQuery('#'+parent_id).find('.wpvivid-content-extension').val();
                }

                //uploads
                json['uploads_check'] = '0';
                json['uploads_list'] = {};
                json['upload_extension'] = '';
                if(jQuery('#'+parent_id).find('.wpvivid-custom-uploads-check').prop('checked')){
                    json['uploads_check'] = '1';
                }
                if(json['exclude_custom'] == '1'){
                    jQuery('#'+parent_id).find('.wpvivid-custom-exclude-uploads-list div').find('span:eq(2)').each(function (){
                        var folder_name = this.innerHTML;
                        json['uploads_list'][folder_name] = {};
                        json['uploads_list'][folder_name]['name'] = folder_name;
                        var type = jQuery(this).closest('div').attr('type');
                        if(type === 'folder'){
                            json['uploads_list'][folder_name]['type'] = 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer';
                        }
                        else{
                            json['uploads_list'][folder_name]['type'] = 'dashicons dashicons-media-default wpvivid-dashicons-grey wpvivid-icon-16px-nopointer';
                        }
                    });
                    json['upload_extension'] = jQuery('#'+parent_id).find('.wpvivid-uploads-extension').val();
                }

                //additional folders/files
                json['other_check'] = '0';
                json['other_list'] = {};
                if(jQuery('#'+parent_id).find('.wpvivid-custom-additional-folder-check').prop('checked')){
                    json['other_check'] = '1';
                }
                if(json['other_check'] == '1'){
                    jQuery('#'+parent_id).find('.wpvivid-custom-include-additional-folder-list div').find('span:eq(2)').each(function (){
                        var folder_name = this.innerHTML;
                        json['other_list'][folder_name] = {};
                        json['other_list'][folder_name]['name'] = folder_name;
                        var type = jQuery(this).closest('div').attr('type');
                        if(type === 'folder'){
                            json['other_list'][folder_name]['type'] = 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer';
                        }
                        else{
                            json['other_list'][folder_name]['type'] = 'dashicons dashicons-media-default wpvivid-dashicons-grey wpvivid-icon-16px-nopointer';
                        }
                    });
                }

                //database
                json['database_check'] = '0';
                json['database_list'] = Array();
                if(jQuery('#'+parent_id).find('.wpvivid-custom-database-check').prop('checked')){
                    json['database_check'] = '1';
                }
                jQuery('input[name=migration_backup_database][type=checkbox]').each(function(index, value){
                    if(!jQuery(value).prop('checked')){
                        json['database_list'].push(jQuery(value).val());
                    }
                });

                //additional database
                json['additional_database_check'] = '0';
                if(jQuery('#'+parent_id).find('.wpvivid-custom-additional-database-check').prop('checked')){
                    json['additional_database_check'] = '1';
                }

                return json;
            }

            function wpvivid_create_custom_setting(custom_type){
                if(custom_type === 'migration_backup'){
                    var parent_id = 'wpvivid_custom_migration_backup';
                }
                var json = {};
                //core
                json['core_check'] = '0';
                json['core_list'] = Array();
                if(jQuery('#'+parent_id).find('.wpvivid-custom-core-check').prop('checked')){
                    json['core_check'] = '1';
                }
                //database
                json['database_check'] = '0';
                json['database_list'] = Array();
                if(jQuery('#'+parent_id).find('.wpvivid-custom-database-check').prop('checked')){
                    json['database_check'] = '1';
                }
                jQuery('input[name=migration_backup_database][type=checkbox]').each(function(index, value){
                    if(!jQuery(value).prop('checked')){
                        json['database_list'].push(jQuery(value).val());
                    }
                });
                //themes & plugins
                json['themes_check'] = '0';
                json['plugins_check'] = '0';
                json['themes_list'] = Array();
                json['plugins_list'] = Array();
                json['exclude_themes_folder'] = Array();
                json['exclude_plugins_folder'] = Array();
                if(jQuery('#'+parent_id).find('.wpvivid-custom-themes-plugins-check').prop('checked')){
                    jQuery('input:checkbox[option=themes][name=migration_backup_themes]').each(function(){
                        if(jQuery(this).prop('checked')){
                            json['themes_check'] = '1';
                        }
                    });
                    jQuery('input[name=migration_backup_themes][type=checkbox]').each(function(index, value){
                        if(!jQuery(value).prop('checked')){
                            json['themes_list'].push(jQuery(value).val());
                        }
                    });
                    jQuery('input:checkbox[option=plugins][name=migration_backup_plugins]').each(function(){
                        if(jQuery(this).prop('checked')){
                            json['plugins_check'] = '1';
                        }
                    });
                    jQuery('input[name=migration_backup_plugins][type=checkbox]').each(function(index, value){
                        if(!jQuery(value).prop('checked')){
                            json['plugins_list'].push(jQuery(value).val());
                        }
                    });
                }
                jQuery('#'+parent_id).find('.wpvivid-custom-exclude-themes-folder ul').find('li div:eq(1)').each(function(){
                    var folder_name = this.innerHTML;
                    json['exclude_themes_folder'].push(folder_name);
                });
                jQuery('#'+parent_id).find('.wpvivid-custom-exclude-plugins-folder ul').find('li div:eq(1)').each(function(){
                    var folder_name = this.innerHTML;
                    json['exclude_plugins_folder'].push(folder_name);
                });
                //uploads
                json['uploads_check'] = '0';
                json['uploads_list'] = {};
                if(jQuery('#'+parent_id).find('.wpvivid-custom-uploads-check').prop('checked')){
                    json['uploads_check'] = '1';
                }
                jQuery('#'+parent_id).find('.wpvivid-custom-exclude-uploads-list ul').find('li div:eq(1)').each(function(){
                    var folder_name = this.innerHTML;
                    json['uploads_list'][folder_name] = {};
                    json['uploads_list'][folder_name]['name'] = folder_name;
                    json['uploads_list'][folder_name]['type'] = jQuery(this).prev().get(0).classList.item(0);
                });
                json['upload_extension'] = jQuery('#'+parent_id).find('.wpvivid-uploads-extension').val();
                //content
                json['content_check'] = '0';
                json['content_list'] = {};
                if(jQuery('#'+parent_id).find('.wpvivid-custom-content-check').prop('checked')){
                    json['content_check'] = '1';
                }
                jQuery('#'+parent_id).find('.wpvivid-custom-exclude-content-list ul').find('li div:eq(1)').each(function(){
                    var folder_name = this.innerHTML;
                    json['content_list'][folder_name] = {};
                    json['content_list'][folder_name]['name'] = folder_name;
                    json['content_list'][folder_name]['type'] = jQuery(this).prev().get(0).classList.item(0);
                });
                json['content_extension'] = jQuery('#'+parent_id).find('.wpvivid-content-extension').val();
                //additional folder
                json['other_check'] = '0';
                json['other_list'] = {};
                if(jQuery('#'+parent_id).find('.wpvivid-custom-additional-folder-check').prop('checked')){
                    json['other_check'] = '1';
                }
                jQuery('#'+parent_id).find('.wpvivid-custom-include-additional-folder-list ul').find('li div:eq(1)').each(function(){
                    var folder_name = this.innerHTML;
                    json['other_list'][folder_name] = {};
                    json['other_list'][folder_name]['name'] = folder_name;
                    json['other_list'][folder_name]['type'] = jQuery(this).prev().get(0).classList.item(0);
                });
                json['other_extension'] = jQuery('#'+parent_id).find('.wpvivid-additional-folder-extension').val();
                //additional database
                json['additional_database_check'] = '0';
                if(jQuery('#'+parent_id).find('.wpvivid-custom-additional-database-check').prop('checked')){
                    json['additional_database_check'] = '1';
                }
                return json;
            }

            function wpvivid_get_mu_site_setting(parent_id) {
                var json = {};
                json['mu_site_id']='';
                jQuery('input[name=mu_site][type=checkbox]').each(function(index, value)
                {
                    if(jQuery(value).prop('checked'))
                    {
                        json['mu_site_id']=jQuery(value).val();
                    }
                });

                json['exclude_custom'] = '1';
                if(!jQuery('#'+parent_id).find('.wpvivid-custom-exclude-part').prop('checked')){
                    json['exclude_custom'] = '0';
                }

                //themes
                json['themes_check'] = '0';
                json['themes_list'] = {};
                json['themes_extension'] = '';
                if(jQuery('#'+parent_id).find('.wpvivid-custom-themes-check').prop('checked')){
                    json['themes_check'] = '1';
                }
                if(json['exclude_custom'] == '1'){
                    jQuery('#'+parent_id).find('.wpvivid-custom-exclude-themes-list div').find('span:eq(2)').each(function (){
                        var folder_name = this.innerHTML;
                        json['themes_list'][folder_name] = {};
                        json['themes_list'][folder_name]['name'] = folder_name;
                        var type = jQuery(this).closest('div').attr('type');
                        if(type === 'folder'){
                            json['themes_list'][folder_name]['type'] = 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer';
                        }
                        else{
                            json['themes_list'][folder_name]['type'] = 'dashicons dashicons-media-default wpvivid-dashicons-grey wpvivid-icon-16px-nopointer';
                        }
                    });
                    json['themes_extension'] = jQuery('#'+parent_id).find('.wpvivid-themes-extension').val();
                }

                //plugins
                json['plugins_check'] = '0';
                json['plugins_list'] = {};
                json['plugins_extension'] = '';
                if(jQuery('#'+parent_id).find('.wpvivid-custom-plugins-check').prop('checked')){
                    json['plugins_check'] = '1';
                }
                if(json['exclude_custom'] == '1'){
                    jQuery('#'+parent_id).find('.wpvivid-custom-exclude-plugins-list div').find('span:eq(2)').each(function (){
                        var folder_name = this.innerHTML;
                        json['plugins_list'][folder_name] = {};
                        json['plugins_list'][folder_name]['name'] = folder_name;
                        var type = jQuery(this).closest('div').attr('type');
                        if(type === 'folder'){
                            json['plugins_list'][folder_name]['type'] = 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer';
                        }
                        else{
                            json['plugins_list'][folder_name]['type'] = 'dashicons dashicons-media-default wpvivid-dashicons-grey wpvivid-icon-16px-nopointer';
                        }
                    });
                    json['plugins_extension'] = jQuery('#'+parent_id).find('.wpvivid-plugins-extension').val();
                }

                //content
                json['content_check'] = '0';
                json['content_list'] = {};
                json['content_extension'] = '';
                if(jQuery('#'+parent_id).find('.wpvivid-custom-content-check').prop('checked')){
                    json['content_check'] = '1';
                }
                if(json['exclude_custom'] == '1'){
                    jQuery('#'+parent_id).find('.wpvivid-custom-exclude-content-list div').find('span:eq(2)').each(function (){
                        var folder_name = this.innerHTML;
                        json['content_list'][folder_name] = {};
                        json['content_list'][folder_name]['name'] = folder_name;
                        var type = jQuery(this).closest('div').attr('type');
                        if(type === 'folder'){
                            json['content_list'][folder_name]['type'] = 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer';
                        }
                        else{
                            json['content_list'][folder_name]['type'] = 'dashicons dashicons-media-default wpvivid-dashicons-grey wpvivid-icon-16px-nopointer';
                        }
                    });
                    json['content_extension'] = jQuery('#'+parent_id).find('.wpvivid-content-extension').val();
                }

                //uploads
                json['uploads_check'] = '0';
                json['uploads_list'] = {};
                json['upload_extension'] = '';
                if(jQuery('#'+parent_id).find('.wpvivid-custom-uploads-check').prop('checked')){
                    json['uploads_check'] = '1';
                }
                if(json['exclude_custom'] == '1'){
                    jQuery('#'+parent_id).find('.wpvivid-custom-exclude-uploads-list div').find('span:eq(2)').each(function (){
                        var folder_name = this.innerHTML;
                        json['uploads_list'][folder_name] = {};
                        json['uploads_list'][folder_name]['name'] = folder_name;
                        var type = jQuery(this).closest('div').attr('type');
                        if(type === 'folder'){
                            json['uploads_list'][folder_name]['type'] = 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer';
                        }
                        else{
                            json['uploads_list'][folder_name]['type'] = 'dashicons dashicons-media-default wpvivid-dashicons-grey wpvivid-icon-16px-nopointer';
                        }
                    });
                    json['upload_extension'] = jQuery('#'+parent_id).find('.wpvivid-uploads-extension').val();
                }

                //additional folders/files
                json['additional_file_check'] = '0';
                json['additional_file_list'] = {};
                if(jQuery('#'+parent_id).find('.wpvivid-custom-additional-folder-check').prop('checked')){
                    json['additional_file_check'] = '1';
                }
                if(json['exclude_custom'] == '1'){
                    jQuery('#'+parent_id).find('.wpvivid-custom-include-additional-folder-list div').find('span:eq(2)').each(function (){
                        var folder_name = this.innerHTML;
                        json['additional_file_list'][folder_name] = {};
                        json['additional_file_list'][folder_name]['name'] = folder_name;
                        var type = jQuery(this).closest('div').attr('type');
                        if(type === 'folder'){
                            json['additional_file_list'][folder_name]['type'] = 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer';
                        }
                        else{
                            json['additional_file_list'][folder_name]['type'] = 'dashicons dashicons-media-default wpvivid-dashicons-grey wpvivid-icon-16px-nopointer';
                        }
                    });
                }

                return json;
            }

            function wpvivid_control_backup_lock(){
                jQuery('#wpvivid_quickbackup_btn').css({'pointer-events': 'none', 'opacity': '0.4'});
            }

            function wpvivid_control_backup_unlock(){
                jQuery('#wpvivid_quickbackup_btn').css({'pointer-events': 'auto', 'opacity': '1'});
            }

            jQuery('#wpvivid_quickbackup_btn').on('click', function(){
                var check_status = wpvivid_check_backup_option_avail('migration_backup');
                if(check_status){
                    var backup_data = wpvivid_ajax_data_transfer('backup');
                    var action = 'wpvivid_send_backup_to_site_addon';
                    jQuery('input:radio[option=backup]').each(function (){
                        if(jQuery(this).prop('checked')){
                            var key = jQuery(this).prop('name');
                            var value = jQuery(this).prop('value');
                            if(value === 'custom'){
                                backup_data = JSON.parse(backup_data);
                                var custom_dirs = wpvivid_create_custom_setting_ex('migration_backup');
                                var custom_option = {
                                    'custom_dirs': custom_dirs
                                };
                                jQuery.extend(backup_data, custom_option);
                                backup_data = JSON.stringify(backup_data);
                            }
                            else if(value === 'mu'){
                                backup_data = JSON.parse(backup_data);
                                var perent_id = 'wpvivid_custom_mu_single_list';
                                var mu_setting = wpvivid_get_mu_site_setting(perent_id);
                                var custom_option = {
                                    'mu_setting': mu_setting
                                };
                                console.log(custom_option);
                                jQuery.extend(backup_data, custom_option);
                                backup_data = JSON.stringify(backup_data);
                                console.log(backup_data);
                            }
                        }
                    });
                    backup_data = JSON.parse(backup_data);
                    var backup_to = {};
                    backup_to['backup_to'] = 'auto_migrate';
                    jQuery.extend(backup_data, backup_to);
                    backup_data = JSON.stringify(backup_data);

                    var ajax_data = {
                        'action': action,
                        'backup': backup_data
                    };
                    wpvivid_control_backup_lock();
                    jQuery('#wpvivid_backup_cancel_btn').css({'pointer-events': 'none', 'opacity': '0.4'});
                    jQuery('#wpvivid_postbox_backup_percent').show();
                    jQuery('#wpvivid_current_doing').html('Ready to backup. Progress: 0%, running time: 0 second.');
                    var percent = '0%';
                    jQuery('.wpvivid-span-processed-percent-progress').css('width', percent);
                    jQuery('.wpvivid-span-processed-percent-progress').html(percent+' completed');
                    jQuery('#wpvivid_backup_database_size').html('N/A');
                    jQuery('#wpvivid_backup_file_size').html('N/A');
                    jQuery('#wpvivid_current_doing').html('');
                    wpvivid_prepare_backup = true;
                    wpvivid_post_request_addon(ajax_data, function (data) {
                        wpvivid_prepare_backup = false;
                        try {
                            var jsonarray = jQuery.parseJSON(data);
                            if (jsonarray.result === 'failed') {
                                wpvivid_delete_ready_task(jsonarray.error);
                            }
                            else if (jsonarray.result === 'success') {
                                var descript = '';
                                if (jsonarray.check.alert_db === true || jsonarray.check.alter_files === true) {
                                    descript = 'The database (the dumping SQL file) might be too large, backing up the database may run out of server memory and result in a backup failure.\n' +
                                        'One or more files might be too large, backing up the file(s) may run out of server memory and result in a backup failure.\n' +
                                        'Click OK button and continue to back up.';
                                    var ret = confirm(descript);
                                    if (ret === true) {
                                        wpvivid_backup_now(jsonarray.task_id);
                                    }
                                    else {
                                        jQuery('#wpvivid_backup_cancel_btn').css({
                                            'pointer-events': 'auto',
                                            'opacity': '1'
                                        });
                                        wpvivid_control_backup_unlock();
                                        jQuery('#wpvivid_postbox_backup_percent').hide();
                                    }
                                }
                                else {
                                    wpvivid_backup_now(jsonarray.task_id);
                                }
                            }
                        }
                        catch (err) {
                            wpvivid_delete_ready_task(err);
                        }
                    }, function (XMLHttpRequest, textStatus, errorThrown) {
                        wpvivid_prepare_backup = false;
                        //var error_message = wpvivid_output_ajaxerror('preparing the backup', textStatus, errorThrown);
                        var error_message='Calculating the size of files, folder and database timed out. If you continue to receive this error, please go to the plugin settings, uncheck \'Calculate the size of files, folder and database before backing up\', save changes, then try again.';
                        wpvivid_delete_ready_task(error_message);
                    });
                }
            });

            function wpvivid_backup_now(task_id){
                var ajax_data = {
                    'action': 'wpvivid_backup_now',
                    'task_id': task_id
                };
                task_retry_times = 0;
                m_need_update_addon=true;
                wpvivid_post_request_addon(ajax_data, function(data){
                }, function(XMLHttpRequest, textStatus, errorThrown) {
                });
            }

            function wpvivid_delete_backup_task(task_id){
                var ajax_data = {
                    'action': 'wpvivid_delete_task',
                    'task_id': task_id
                };
                wpvivid_post_request_addon(ajax_data, function(data){}, function(XMLHttpRequest, textStatus, errorThrown) {
                });
            }

            function wpvivid_delete_ready_task(error){
                var ajax_data={
                    'action': 'wpvivid_delete_ready_task'
                };
                wpvivid_post_request_addon(ajax_data, function (data) {
                    try {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success') {
                            wpvivid_add_notice('Backup', 'Error', error);
                            wpvivid_control_backup_unlock();
                            jQuery('#wpvivid_postbox_backup_percent').hide();
                        }
                    }
                    catch(err){
                        wpvivid_add_notice('Backup', 'Error', err);
                        wpvivid_control_backup_unlock();
                        jQuery('#wpvivid_postbox_backup_percent').hide();
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown) {
                    setTimeout(function () {
                        wpvivid_delete_ready_task(error);
                    }, 3000);
                });
            }
        </script>
        <?php
        do_action('wpvivid_backup_do_js_addon');
    }

    public function output_auto_migration()
    {
        ?>
        <div id="wpvivid_transfer_key">
        <?php
        $html = '';
        echo apply_filters('wpvivid_put_transfer_key', $html);
        ?>
        </div>
        <script>
            jQuery(document).ready(function (){
                jQuery('#wpvivid_recalc_migration_size').css({'pointer-events': 'none', 'opacity': '0.4'});
            });

            jQuery('#wpvivid_recalc_migration_size').click(function(){
                var custom_dirs = wpvivid_create_custom_setting_ex('migration_backup');
                var custom_option = {
                    'custom_dirs': custom_dirs
                };
                var custom_option = JSON.stringify(custom_option);

                var ajax_data = {
                    'action': 'wpvivid_recalc_backup_size',
                    'custom_option': custom_option
                };
                jQuery('#wpvivid_recalc_migration_size').css({'pointer-events': 'none', 'opacity': '0.4'});
                jQuery('#wpvivid_custom_migration_backup').find('.wpvivid-database-size').html('calculating');
                jQuery('#wpvivid_custom_migration_backup').find('.wpvivid-core-size').html('calculating');
                jQuery('#wpvivid_custom_migration_backup').find('.wpvivid-themes-size').html('calculating');
                jQuery('#wpvivid_custom_migration_backup').find('.wpvivid-plugins-size').html('calculating');
                jQuery('#wpvivid_custom_migration_backup').find('.wpvivid-uploads-size').html('calculating');
                jQuery('#wpvivid_custom_migration_backup').find('.wpvivid-content-size').html('calculating');
                jQuery('#wpvivid_custom_migration_backup').find('.wpvivid-additional-folder-size').html('calculating');
                jQuery('#wpvivid_custom_migration_backup').find('.wpvivid-total-file-size').html('calculating');
                jQuery('#wpvivid_custom_migration_backup').find('.wpvivid-total-exclude-file-size').html('calculating');
                wpvivid_post_request_addon(ajax_data, function (data) {
                    jQuery('#wpvivid_recalc_migration_size').css({'pointer-events': 'auto', 'opacity': '1'});
                    try {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success') {
                            jQuery('#wpvivid_custom_migration_backup').find('.wpvivid-database-size').html(jsonarray.database_size);
                            jQuery('#wpvivid_custom_migration_backup').find('.wpvivid-core-size').html(jsonarray.core_size);
                            jQuery('#wpvivid_custom_migration_backup').find('.wpvivid-themes-size').html(jsonarray.themes_size);
                            jQuery('#wpvivid_custom_migration_backup').find('.wpvivid-plugins-size').html(jsonarray.plugins_size);
                            jQuery('#wpvivid_custom_migration_backup').find('.wpvivid-uploads-size').html(jsonarray.uploads_size);
                            jQuery('#wpvivid_custom_migration_backup').find('.wpvivid-content-size').html(jsonarray.content_size);
                            jQuery('#wpvivid_custom_migration_backup').find('.wpvivid-additional-folder-size').html(jsonarray.additional_size);
                            jQuery('#wpvivid_custom_migration_backup').find('.wpvivid-total-file-size').html(jsonarray.total_file_size);
                            jQuery('#wpvivid_custom_migration_backup').find('.wpvivid-total-exclude-file-size').html(jsonarray.total_exclude_file_size);
                        }
                        else
                        {
                            alert(jsonarray.error);
                        }
                    }
                    catch (err) {
                        alert(err);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown) {
                    jQuery('#wpvivid_recalc_migration_size').css({'pointer-events': 'auto', 'opacity': '1'});
                    var error_message = wpvivid_output_ajaxerror('calcing backup', textStatus, errorThrown);
                    alert(error_message);
                });
            });

            var source_site ='<?php echo site_url(); ?>';
            function wpvivid_check_key(value)
            {
                var pos = value.indexOf('?');
                var site_url = value.substring(0, pos);
                if(site_url === source_site)
                {
                    alert('The key generated by this site cannot be added into this site.');
                    jQuery('#wpvivid_save_url_button').prop('disabled', true);
                }
                else{
                    jQuery("#wpvivid_save_url_button").prop('disabled', false);
                }
            }

            function wpvivid_click_save_site_url()
            {
                var url= jQuery('#wpvivid_transfer_key_text').val();
                var ajax_data =
                    {
                        'action': 'wpvivid_test_connect_site',
                        'url':url
                    };

                jQuery("#wpvivid_save_url_button").prop('disabled', true);
                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    jQuery("#wpvivid_save_url_button").prop('disabled', false);
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if(jsonarray.result==='success')
                        {
                            location.href='<?php echo apply_filters('wpvivid_white_label_page_redirect', 'admin.php?page=wpvivid-migration', 'wpvivid-migration'); ?>';
                        }
                        else
                        {
                            alert(jsonarray.error);
                        }
                    }
                    catch(err)
                    {
                        alert(err);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    jQuery("#wpvivid_save_url_button").prop('disabled', false);
                    var error_message = wpvivid_output_ajaxerror('saving key', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            function wpvivid_click_delete_transfer_key()
            {
                var ajax_data = {
                    'action': 'wpvivid_delete_transfer_key'
                };

                jQuery("#wpvivid_delete_key_button").css({'pointer-events': 'none', 'opacity': '0.4'});
                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    jQuery("#wpvivid_delete_key_button").css({'pointer-events': 'none', 'opacity': '0.4'});
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if(jsonarray.result==='success')
                        {
                            jQuery('#wpvivid_transfer_key').html(jsonarray.html);
                        }
                    }
                    catch(err)
                    {
                        alert(err);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    jQuery("#wpvivid_delete_key_button").css({'pointer-events': 'auto', 'opacity': '1'});
                    var error_message = wpvivid_output_ajaxerror('deleting key', textStatus, errorThrown);
                    alert(error_message);
                });
            }
        </script>
        <?php
    }

    public function output_general_key()
    {
        ?>
        <div class="wpvivid-one-coloum wpvivid-workflow">
            In order to allow another site to send a backup to this site, please generate a key below. Once the key is generated, this site is ready to receive a backup from another site. Then, please copy and paste the key in sending site and save it.
            <p></p>
            <div>
                <span>The key will expire in</span>
                <select id="wpvivid_generate_url_expires" style="margin-bottom: 2px;">
                    <option value="2 hour">2 hours</option>
                    <option selected="selected" value="8 hour">8 hours</option>
                    <option value="24 hour">24 hours</option>
                </select>
                <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip wpvivid-tooltip-padding-top">
                    <div class="wpvivid-bottom">
                        <!-- The content you need -->
                        <p>For security reason, please choose an appropriate expiration time for the key.</p>
                        <i></i> <!-- do not delete this line -->
                    </div>
                </span>
            </div>
            <textarea id="wpvivid_test_remote_site_url_text" style="width: 100%; height: 140px;margin-bottom:1em;"></textarea>
            <input class="button-primary" id="wpvivid_generate_url" type="submit" value="Generate" onclick="wpvivid_click_generate_url();">
        </div>
        <script>
            jQuery("#wpvivid_test_remote_site_url_text").focus(function() {
                jQuery(this).select();
                jQuery(this).mouseup(function() {
                    jQuery(this).unbind("mouseup");
                    return false;
                });
            });
            function wpvivid_click_generate_url()
            {
                var expires=jQuery('#wpvivid_generate_url_expires').val();
                var ajax_data = {
                    'action': 'wpvivid_generate_url',
                    'expires':expires
                };
                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    jQuery('#wpvivid_test_remote_site_url_text').val(data);
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    var error_message = wpvivid_output_ajaxerror('generating key', textStatus, errorThrown);
                    alert(error_message);
                });
            }
        </script>
        <?php
    }
}