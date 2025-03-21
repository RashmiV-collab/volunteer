<?php

/**
 * WPvivid addon: yes
 * Addon Name: wpvivid-white-label-addons
 * Description: Pro
 * Version: 2.2.21
 * Need_init: yes
 * Interface Name: WPvivid_White_Label_addon_ex
 */
if (!defined('WPVIVID_BACKUP_PRO_PLUGIN_DIR'))
{
    die;
}
class WPvivid_White_Label_addon_ex
{
    public $main_tab;

    public function __construct()
    {
        add_filter('wpvivid_white_label_screen_ids', array($this, 'wpvivid_white_label_screen_ids'));

        add_filter('wpvivid_white_label_display', array($this, 'wpvivid_white_label_display'));
        add_filter('wpvivid_white_label_display_ex', array($this, 'wpvivid_white_label_display_ex'));
        add_filter('wpvivid_white_laebl_display_pro_ex', array($this, 'wpvivid_white_laebl_display_pro_ex'));
        add_filter('wpvivid_white_label_string', array($this, 'wpvivid_white_label_string'));
        add_filter('wpvivid_white_label_slug', array($this, 'wpvivid_white_label_slug'));
        add_filter('wpvivid_white_label_website', array($this, 'wpvivid_white_label_website'));
        add_filter('wpvivid_white_label_screen_id', array($this, 'wpvivid_white_label_screen_id'));
        add_filter('wpvivid_white_label_remote_root_path', array($this, 'wpvivid_white_label_remote_root_path'));

        add_filter('wpvivid_white_label_plugin_name', array($this, 'wpvivid_white_label_plugin_name'));

        add_filter('wpvivid_backup_file_prefix', array($this, 'wpvivid_backup_file_prefix'), 10, 4);
        add_filter('wpvivid_white_label_file_prefix', array($this, 'wpvivid_white_label_file_prefix'));
        add_filter('wpvivid_white_label_email', array($this, 'wpvivid_white_label_email'));

        add_filter('wpvivid_white_label_hide_page', array($this, 'wpvivid_white_label_hide_page'));

        add_action('wp_ajax_wpvivid_set_white_label_setting', array($this, 'set_white_label_setting'));
        add_action('wp_ajax_wpvivid_reset_white_label', array($this, 'reset_white_label'));

        add_filter('wpvivid_fix_wpvivid_free', array($this, 'wpvivid_fix_wpvivid_free'));

        add_filter( 'all_plugins', array( $this, 'white_label_modify_plugin' ),99 );

        add_action('wpvivid_output_white_label_page',array($this,'output_white_label_page'));

        add_filter('wpvivid_access_white_label_slug', array($this, 'white_label_slug'));

        add_filter('wpvivid_export_setting_addon', array($this, 'export_setting_addon'), 11);

        //
        add_filter('wpvivid_get_dashboard_screens', array($this, 'get_dashboard_screens'), 9999);
        add_filter('wpvivid_get_staging_screens', array($this, 'get_staging_screens'), 1001);

        add_filter('wpvivid_show_sidebar',array($this,'show_sidebar'));
        add_filter('wpvivid_show_submit_ticket', array($this, 'show_submit_ticket'));
        add_filter('wpvivid_show_dashboard_addons', array($this, 'show_dashboard_addons'));

        add_filter('wpvivid_white_label_page_redirect', array($this, 'page_redirect'), 10, 2);

        add_filter('wpvivid_get_toolbar_menus',array($this,'get_toolbar_menus'),99);

        add_action( 'current_screen', array( $this, 'cpro_plugin_gettext' ) );

        add_filter('wpvivid_get_white_label_option', array($this, 'get_white_label_option'), 10, 2);
        //add_filter( 'site_transient_update_plugins', array( $this, 'white_label_update_plugins' ),99 ,2);

        //add_filter('init',array($this, 'check_pings_name'));
    }

    public function get_white_label_option($option_name, $default_value)
    {
        if(is_multisite())
        {
            if(!is_main_site())
            {
                switch_to_blog(get_main_site_id());
                $white_label_setting=get_option($option_name, $default_value);
                restore_current_blog();
            }
            else
            {
                $white_label_setting=get_option($option_name, $default_value);
            }
        }
        else
        {
            $white_label_setting=get_option($option_name, $default_value);
        }
        return $white_label_setting;
    }

    public function cpro_plugin_gettext( $current_screen ) {
        $white_label_setting=apply_filters('wpvivid_get_white_label_option', 'white_label_setting', false);
        if('update-core' === $current_screen->base && !empty($white_label_setting)){
            add_filter( 'gettext', array( $this, 'plugin_gettext_wpvivid' ), 20, 3 );
            add_filter( 'gettext', array( $this, 'plugin_gettext_wpvivid_addon' ), 20, 3 );
            add_filter( 'gettext', array( $this, 'plugin_gettext_staging' ), 20, 3 );
        }
    }

    public function plugin_gettext_wpvivid( $text, $original, $domain ) {

        if ( 'WPvivid Backup Plugin' === $original ) {
            $white_label_setting=apply_filters('wpvivid_get_white_label_option', 'white_label_setting', false);
            $text = sprintf('%s', $white_label_setting['white_label_display']);
        }

        return $text;
    }

    public function plugin_gettext_wpvivid_addon( $text, $original, $domain ) {

        if ( 'WPvivid Plugins Pro' === $original ) {
            $white_label_setting=apply_filters('wpvivid_get_white_label_option', 'white_label_setting', false);
            $text = sprintf('%s Pro', $white_label_setting['white_label_display']);
        }

        return $text;
    }

    public function plugin_gettext_staging( $text, $original, $domain ) {

        if ( 'WPvivid Staging' === $original ) {
            $white_label_setting=apply_filters('wpvivid_get_white_label_option', 'white_label_setting', false);
            $text = sprintf('%s Staging', $white_label_setting['white_label_display']);
        }

        return $text;
    }

    public function get_toolbar_menus($toolbar_menus)
    {
        if (isset($toolbar_menus['wpvivid_admin_menu']))
        {
            $toolbar_menus['wpvivid_admin_menu']['title'] = apply_filters('wpvivid_white_label_display', 'WPvivid Plugin');
        }
        return $toolbar_menus;
    }

    public function page_redirect($url, $param)
    {
        //$page = $url.'?page='.strtolower(sprintf('%s-license', apply_filters('wpvivid_white_label_slug', 'wpvivid'))).'&check_update=1';
        $url = 'admin.php?page='.apply_filters('wpvivid_white_label_plugin_name', $param);
        return $url;
    }

    public function show_sidebar($show)
    {
        $white_label_setting=apply_filters('wpvivid_get_white_label_option', 'white_label_setting', array());

        $show_sidebar= empty($white_label_setting['show_sidebar']) ? 'show' : $white_label_setting['show_sidebar'];

        if($show_sidebar=='show')
        {
            $show=true;
        }
        else
        {
            $show=false;
        }

        return $show;
    }

    public function show_submit_ticket($show)
    {
        $white_label_setting=apply_filters('wpvivid_get_white_label_option', 'white_label_setting', array());

        $show_submit_ticket= empty($white_label_setting['show_submit_ticket']) ? 'show' : $white_label_setting['show_submit_ticket'];

        if($show_submit_ticket=='show')
        {
            $show=true;
        }
        else
        {
            $show=false;
        }

        return $show;
    }

    public function show_dashboard_addons($show)
    {
        $white_label_setting=apply_filters('wpvivid_get_white_label_option', 'white_label_setting', array());

        $show_sidebar= empty($white_label_setting) ? 'show' : 'hide';

        if($show_sidebar=='show')
        {
            $show=true;
        }
        else
        {
            $show=false;
        }

        return $show;
    }

    public function white_label_slug($slug)
    {
        $white_label_setting=apply_filters('wpvivid_get_white_label_option', 'white_label_setting', array());
        if(isset($white_label_setting['access_white_label_page_slug']))
        {
            $slug=$white_label_setting['access_white_label_page_slug'];
        }
        else{
            $slug='wpvivid_white_label';
        }
        return $slug;
    }

    public function output_white_label_page()
    {
        $this->init_page();
    }

    /*public function white_label_update_plugins($value, $transient )
    {
        if($transient=='update_plugins')
        {
            $plugin_basename        = plugin_basename( WPVIVID_BACKUP_PRO_PLUGIN_DIR . 'wpvivid-backup-pro.php' );

            $update_cache = is_object( $value ) ? $value : new stdClass();
            if(isset($update_cache->response[ $plugin_basename ]))
            {
                //replace icon
            }
        }
        return $value;
    }*/

    public function white_label_modify_plugin( $all_plugins )
    {
        $white_label_setting=apply_filters('wpvivid_get_white_label_option', 'white_label_setting', false);
        if(!empty($white_label_setting)){
            $white_label_setting['white_label_display'] = empty($white_label_setting['white_label_display']) ? 'WPvivid Backup' : $white_label_setting['white_label_display'];
            $white_label_setting['white_label_website_protocol'] = empty($white_label_setting['white_label_website_protocol']) ? 'https' : $white_label_setting['white_label_website_protocol'];
            $white_label_setting['white_label_website'] = empty($white_label_setting['white_label_website']) ? 'wpvivid.com' : $white_label_setting['white_label_website'];
            $white_label_setting['white_label_author'] = empty($white_label_setting['white_label_author']) ? 'wpvivid.com' : $white_label_setting['white_label_author'];
            if ( isset( $all_plugins['wpvivid-backuprestore/wpvivid-backuprestore.php'] ) )
            {
                $all_plugins['wpvivid-backuprestore/wpvivid-backuprestore.php']['Name'] = sprintf('%s', $white_label_setting['white_label_display']);
                $all_plugins['wpvivid-backuprestore/wpvivid-backuprestore.php']['Description'] = 'Clone or copy WP sites then move or migrate them to new host (new domain), schedule backups, transfer backups to leading remote storage. All in one.';
                $all_plugins['wpvivid-backuprestore/wpvivid-backuprestore.php']['Author'] = $white_label_setting['white_label_author'];
                $all_plugins['wpvivid-backuprestore/wpvivid-backuprestore.php']['AuthorURI'] = $white_label_setting['white_label_website_protocol'].'://'.$white_label_setting['white_label_website'];
                $all_plugins['wpvivid-backuprestore/wpvivid-backuprestore.php']['Title'] = sprintf('%s', $white_label_setting['white_label_display']);
                $all_plugins['wpvivid-backuprestore/wpvivid-backuprestore.php']['AuthorName'] = $white_label_setting['white_label_website'];
            }

            if ( isset( $all_plugins['wpvivid-backup-pro/wpvivid-backup-pro.php'] ) )
            {
                $all_plugins['wpvivid-backup-pro/wpvivid-backup-pro.php']['Name'] = sprintf('%s Pro', $white_label_setting['white_label_display']);
                $all_plugins['wpvivid-backup-pro/wpvivid-backup-pro.php']['Description'] = sprintf('%s Pro works on top of the free version. It offers more advanced features for customizing WordPress website backup and migration.', $white_label_setting['white_label_display']);
                $all_plugins['wpvivid-backup-pro/wpvivid-backup-pro.php']['Author'] = $white_label_setting['white_label_author'];
                $all_plugins['wpvivid-backup-pro/wpvivid-backup-pro.php']['AuthorURI'] = $white_label_setting['white_label_website_protocol'].'://'.$white_label_setting['white_label_website'];
                $all_plugins['wpvivid-backup-pro/wpvivid-backup-pro.php']['Title'] = sprintf('%s Pro', $white_label_setting['white_label_display']);
                $all_plugins['wpvivid-backup-pro/wpvivid-backup-pro.php']['AuthorName'] = $white_label_setting['white_label_website'];
            }

            if ( isset( $all_plugins['wpvivid-staging/wpvivid-staging.php'] ) )
            {
                $all_plugins['wpvivid-staging/wpvivid-staging.php']['Name'] = sprintf('%s Staging', $white_label_setting['white_label_display']);
                $all_plugins['wpvivid-staging/wpvivid-staging.php']['Description'] = sprintf('%s Staging plugin allows you to easily create a staging site and publish a staging site to live site.', $white_label_setting['white_label_display']);
                $all_plugins['wpvivid-staging/wpvivid-staging.php']['Author'] = $white_label_setting['white_label_author'];
                $all_plugins['wpvivid-staging/wpvivid-staging.php']['AuthorURI'] = $white_label_setting['white_label_website_protocol'].'://'.$white_label_setting['white_label_website'];
                $all_plugins['wpvivid-staging/wpvivid-staging.php']['Title'] = sprintf('%s Pro', $white_label_setting['white_label_display']);
                $all_plugins['wpvivid-staging/wpvivid-staging.php']['AuthorName'] = $white_label_setting['white_label_website'];
            }

            if ( isset( $all_plugins['wpvivid-imgoptim/wpvivid-imgoptim.php'] ) )
            {
                $all_plugins['wpvivid-imgoptim/wpvivid-imgoptim.php']['Name'] = sprintf('%s Imgoptim Free', $white_label_setting['white_label_display']);
                $all_plugins['wpvivid-imgoptim/wpvivid-imgoptim.php']['Description'] = 'Optimize, compress and resize images in WordPress in bulk. Automatic image optimization, auto resize images upon upload.';
                $all_plugins['wpvivid-imgoptim/wpvivid-imgoptim.php']['Author'] = $white_label_setting['white_label_author'];
                $all_plugins['wpvivid-imgoptim/wpvivid-imgoptim.php']['AuthorURI'] = $white_label_setting['white_label_website_protocol'].'://'.$white_label_setting['white_label_website'];
                $all_plugins['wpvivid-imgoptim/wpvivid-imgoptim.php']['Title'] = sprintf('%s Imgoptim Free', $white_label_setting['white_label_display']);
                $all_plugins['wpvivid-imgoptim/wpvivid-imgoptim.php']['AuthorName'] = $white_label_setting['white_label_website'];
            }
        }
        return $all_plugins;
    }

    public function wpvivid_white_label_screen_ids($screen_ids)
    {
        foreach ($screen_ids as $index => $value)
        {
            if($value === 'wpvivid-backup_page_wpvivid-export-import'){
                $screen_ids[$index] = apply_filters('wpvivid_white_label_screen_id', 'wpvivid-backup_page_wpvivid-export-import');
            }
            else if($value === 'wpvivid-backup_page_wpvivid-staging'){
                $screen_ids[$index] = apply_filters('wpvivid_white_label_screen_id', 'wpvivid-backup_page_wpvivid-staging');
            }
            else if($value === 'wpvivid-backup_page_wpvivid-setting'){
                $screen_ids[$index] = apply_filters('wpvivid_white_label_screen_id', 'wpvivid-backup_page_wpvivid-setting');
            }
            else if($value === 'wpvivid-backup_page_wpvivid-debug'){
                $screen_ids[$index] = apply_filters('wpvivid_white_label_screen_id', 'wpvivid-backup_page_wpvivid-debug');
            }
            else if($value === 'wpvivid-backup_page_wpvivid-white-label'){
                $screen_ids[$index] = apply_filters('wpvivid_white_label_screen_id', 'wpvivid-backup_page_wpvivid-white-label');
            }
            else if($value === 'wpvivid-backup_page_wpvivid-tools'){
                $screen_ids[$index] = apply_filters('wpvivid_white_label_screen_id', 'wpvivid-backup_page_wpvivid-tools');
            }
            else if($value === 'wpvivid-backup_page_wpvivid-log'){
                $screen_ids[$index] = apply_filters('wpvivid_white_label_screen_id', 'wpvivid-backup_page_wpvivid-log');
            }
            else if($value === 'wpvivid-backup_page_wpvivid-pro'){
                $screen_ids[$index] = apply_filters('wpvivid_white_label_screen_id', 'wpvivid-backup_page_wpvivid-pro');
            }
        }
        return $screen_ids;
    }

    public function wpvivid_white_label_display($content)
    {
        $white_label_setting=apply_filters('wpvivid_get_white_label_option', 'white_label_setting', false);
        if(!empty($white_label_setting['white_label_display']))
        {
            $white_label_display = $white_label_setting['white_label_display'];
            $content = str_replace($content, $white_label_display, $content);
        }
        return $content;
    }

    public function wpvivid_white_label_display_ex($content)
    {
        $white_label_setting=apply_filters('wpvivid_get_white_label_option', 'white_label_setting', false);
        if(!empty($white_label_setting['white_label_display']))
        {
            $white_label_display = $white_label_setting['white_label_display'];
            $content = str_replace('WPvivid Backup', $white_label_display, $content);
        }
        return $content;
    }

    public function wpvivid_white_laebl_display_pro_ex($content)
    {
        $white_label_setting=apply_filters('wpvivid_get_white_label_option', 'white_label_setting', false);
        if(!empty($white_label_setting['white_label_display']))
        {
            $white_label_display = $white_label_setting['white_label_display'];
            $content = str_replace('WPvivid', $white_label_display, $content);
        }
        return $content;
    }

    public function wpvivid_white_label_string($content)
    {
        $white_label_setting=apply_filters('wpvivid_get_white_label_option', 'white_label_setting', false);
        if(!empty($white_label_setting['white_label_slug']))
        {
            $white_label_slug = $white_label_setting['white_label_slug'];
            $content = str_replace('WPvivid', $white_label_slug, $content);
        }
        return $content;
    }

    public function wpvivid_white_label_slug($slug)
    {
        $white_label_setting=apply_filters('wpvivid_get_white_label_option', 'white_label_setting', false);
        if(!empty($white_label_setting['white_label_slug']))
        {
            $white_label_slug = $white_label_setting['white_label_slug'];
            $slug = str_replace($slug, $white_label_slug, $slug);
            $slug = strtolower($slug);
        }
        return $slug;
    }

    public function wpvivid_white_label_website($website)
    {
        $white_label_setting=apply_filters('wpvivid_get_white_label_option', 'white_label_setting', false);
        if(!empty($white_label_setting['white_label_website']))
        {
            $white_label_display = $white_label_setting['white_label_website'];
            $website = str_replace($website, $white_label_display, $website);
        }
        return $website;
    }

    public function wpvivid_white_label_screen_id($screen_id)
    {
        $white_label_setting=apply_filters('wpvivid_get_white_label_option', 'white_label_setting', false);
        if(!empty($white_label_setting['white_label_display']) && !empty($white_label_setting['white_label_slug']))
        {
            $label_display = $white_label_setting['white_label_display'];
            $label_slug = $white_label_setting['white_label_slug'];
            $label_slug = strtolower($label_slug);

            $search = 'wpvivid';

            $string_array = preg_split('/_page_/', $screen_id);
            if(!empty($string_array)){
                $page_type = $string_array[0];
                $plugin_name = $string_array[1];

                $page_type = sanitize_title(str_replace($page_type, $label_display, $page_type));
                $plugin_name = str_replace($search, $label_slug, $plugin_name);
                $screen_id = $page_type.'_page_'.$plugin_name;
            }
        }
        return $screen_id;
    }

    public function wpvivid_white_label_plugin_name($plugin_name)
    {
        $white_label_setting=apply_filters('wpvivid_get_white_label_option', 'white_label_setting', false);
        if(!empty($white_label_setting['white_label_slug']))
        {
            $label_slug = $white_label_setting['white_label_slug'];
            $label_slug = strtolower($label_slug);

            if(preg_match('/wpvividstg/', $plugin_name))
            {
                $search = 'wpvividstg';
                $plugin_name = str_replace($search, $label_slug, $plugin_name);
            }
            else{
                $search = 'wpvivid';
                $plugin_name = str_replace($search, $label_slug, $plugin_name);
            }
        }
        return $plugin_name;
    }

    public function wpvivid_white_label_remote_root_path($root_path)
    {
        $white_label_setting=apply_filters('wpvivid_get_white_label_option', 'white_label_setting', false);
        if(!empty($white_label_setting['white_label_slug']))
        {
            $label_slug = $white_label_setting['white_label_slug'];
            $label_slug = strtolower($label_slug);
            $root_path = str_replace($root_path, $label_slug, $root_path);
        }
        return $root_path;
    }

    public function wpvivid_backup_file_prefix($file_prefix,$backup_prefix,$id,$start_time)
    {
        $white_label_setting=apply_filters('wpvivid_get_white_label_option', 'white_label_setting', false);
        if(!empty($white_label_setting['white_label_slug']))
        {
            $search = 'wpvivid';
            $label_slug = $white_label_setting['white_label_slug'];
            $label_slug = strtolower($label_slug);
            $id = str_replace($search, $label_slug, $id);

            $offset = get_option('gmt_offset');
            if (empty($backup_prefix))
                $file_prefix = $id . '_' . date('Y-m-d-H-i', $start_time + $offset * 60 * 60);
            else
                $file_prefix = $backup_prefix . '_' . $id . '_' . date('Y-m-d-H-i', $start_time + $offset * 60 * 60);
        }
        return $file_prefix;
    }

    public function wpvivid_white_label_file_prefix($match_string)
    {
        $white_label_setting=apply_filters('wpvivid_get_white_label_option', 'white_label_setting', false);
        if(!empty($white_label_setting['white_label_slug']))
        {
            $white_label_slug = $white_label_setting['white_label_slug'];
            $white_label_slug = strtolower($white_label_slug);
            $match_string = str_replace($match_string, $white_label_slug, $match_string);
        }
        return $match_string;
    }

    public function wpvivid_fix_wpvivid_free($backup_id)
    {
        $white_label_setting=apply_filters('wpvivid_get_white_label_option', 'white_label_setting', false);
        if(!empty($white_label_setting['white_label_slug']))
        {
            $white_label_slug = $white_label_setting['white_label_slug'];
            $white_label_slug = strtolower($white_label_slug);
            $backup_id = str_replace('wpvivid', $white_label_slug, $backup_id);
        }
        return $backup_id;
    }

    public function wpvivid_white_label_email($email)
    {
        $white_label_setting=apply_filters('wpvivid_get_white_label_option', 'white_label_setting', false);
        if(!empty($white_label_setting['white_label_support_email']))
        {
            $white_label_email = $white_label_setting['white_label_support_email'];
            $email = str_replace($email, $white_label_email, $email);
        }
        return $email;
    }

    public function wpvivid_white_label_hide_page($is_hide_page)
    {
        $white_label_setting=apply_filters('wpvivid_get_white_label_option', 'white_label_setting', false);
        if(isset($white_label_setting['white_label_hide_page'])){
            if($white_label_setting['white_label_hide_page']){
                $is_hide_page = true;
            }
            else{
                $is_hide_page = false;
            }
        }
        else{
            $is_hide_page = false;
        }
        return $is_hide_page;
    }

    public function init_page()
    {
        ?>
        <div class="wrap wpvivid-canvas">
            <div id="icon-options-general" class="icon32"></div>
            <h1><?php esc_attr_e( apply_filters('wpvivid_white_label_display', 'WPvivid').' Plugins - White Label', 'WpvividPlugins' ); ?></h1>
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <!-- main content -->
                    <div id="post-body-content">
                        <div class="meta-box-sortables ui-sortable">
                            <div class="wpvivid-backup">
                                <div class="wpvivid-welcome-bar wpvivid-clear-float">
                                    <div class="wpvivid-welcome-bar-left">
                                        <p><span class="dashicons dashicons-admin-generic wpvivid-dashicons-large wpvivid-dashicons-blue"></span><span class="wpvivid-page-title">White Label</span></p>
                                        <span class="about-description"><?php echo sprintf(__('This tab allows you to configure %s Pro white label settings.', 'wpvivid'), apply_filters('wpvivid_white_label_display', 'WPvivid Backup')); ?></span>
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
                                                    <!-- The content you need -->
                                                    <p>Clicking the date and time will redirect you to the WordPress General Settings page where you can change your timezone settings.</p>
                                                    <i></i> <!-- do not delete this line -->
                                                </div>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="wpvivid-nav-bar wpvivid-clear-float">
                                        <span class="dashicons dashicons-lightbulb wpvivid-dashicons-orange"></span>
                                        <span> To restore backups of a white-labeled website, the current website needs to be white labeled with the same brand name.</span>
                                    </div>
                                </div>

                                <div class="wpvivid-canvas wpvivid-clear-float">
                                    <?php
                                    $this->output_white_label();
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
        <?php
    }

    public function output_white_label()
    {
        $white_label_setting=apply_filters('wpvivid_get_white_label_option', 'white_label_setting', array());
        $white_label_display = empty($white_label_setting['white_label_display']) ? 'WPvivid Backup' : $white_label_setting['white_label_display'];
        $white_label_slug = empty($white_label_setting['white_label_slug']) ? WPVIVID_PRO_PLUGIN_SLUG : $white_label_setting['white_label_slug'];
        $white_label_support_email = empty($white_label_setting['white_label_support_email']) ? 'pro.support@wpvivid.com' : $white_label_setting['white_label_support_email'];
        $white_label_website_protocol = empty($white_label_setting['white_label_website_protocol']) ? 'https' : $white_label_setting['white_label_website_protocol'];
        $white_label_website = empty($white_label_setting['white_label_website']) ? 'wpvivid.com' : $white_label_setting['white_label_website'];
        $white_label_author = empty($white_label_setting['white_label_author']) ? 'wpvivid.com' : $white_label_setting['white_label_author'];

        $wpvivid_access_white_label_slug= empty($white_label_setting['access_white_label_page_slug']) ? 'wpvivid_white_label' : $white_label_setting['access_white_label_page_slug'];

        $show_sidebar= empty($white_label_setting['show_sidebar']) ? 'show' : $white_label_setting['show_sidebar'];
        if($show_sidebar=='show')
        {
            $show_sidebar_link='checked';
            $hide_sidebar_link='';
        }
        else
        {
            $show_sidebar_link='';
            $hide_sidebar_link='checked';
        }

        $show_submit_ticket=empty($white_label_setting['show_submit_ticket']) ? 'show' : $white_label_setting['show_submit_ticket'];
        if($show_submit_ticket=='show')
        {
            $show_submit_ticket_link='checked';
            $hide_submit_ticket_link='';
        }
        else
        {
            $show_submit_ticket_link='';
            $hide_submit_ticket_link='checked';
        }
        ?>
        <div class="wpvivid-one-coloum">
            <div class="wpvivid-element-space-bottom"><strong><?php _e('Plugin Name', 'wpvivid'); ?></strong></div>
            <div class="wpvivid-element-space-bottom">
                <input type="text" placeholder="WPvivid" option="white_label_setting" name="white_label_display" class="all-options" value="<?php esc_attr_e($white_label_display); ?>" onkeyup="value=value.replace(/[^a-zA-Z0-9_ ]/g,'')" onpaste="value=value.replace(/[^\a-\z\A-\Z0-9]/g,'')" />
            </div>
            <div class="wpvivid-element-space-bottom"><?php echo sprintf(__('Enter your preferred plugin name to replace %s on the plugin UI and WP dashboard.', 'wpvivid'), $white_label_display); ?></div>

            <div class="wpvivid-element-space-bottom"><strong><?php _e('Slug', 'wpvivid'); ?></strong></div>
            <div class="wpvivid-element-space-bottom">
                <input type="text" placeholder="WPvivid" option="white_label_setting" name="white_label_slug" class="all-options" value="<?php esc_attr_e($white_label_slug); ?>" onkeyup="value=value.replace(/[^a-zA-Z0-9-]/g,'')" onpaste="value=value.replace(/[^\a-\z\A-\Z0-9-]/g,'')" />
            </div>
            <div class="wpvivid-element-space-bottom"><?php echo sprintf(__('Enter your preferred slug to replace %s in all slugs, default storage directory paths, backup file names, default staging database names and table prefixes.', 'wpvivid'), $white_label_slug); ?></div>

            <div class="wpvivid-element-space-bottom"><strong><?php _e('Support Email', 'wpvivid'); ?></strong></div>
            <div class="wpvivid-element-space-bottom">
                <input type="text" placeholder="pro.support@wpvivid.com" option="white_label_setting" name="white_label_support_email" class="all-options" value="<?php esc_attr_e($white_label_support_email); ?>" />
            </div>
            <div class="wpvivid-element-space-bottom"><?php echo sprintf(__('Enter your support email to replace %s in the plugin\'s Debug tab.', 'wpvivid'), $white_label_support_email); ?></div>

            <div class="wpvivid-element-space-bottom"><strong><?php _e('Author', 'wpvivid'); ?></strong></div>
            <div class="wpvivid-element-space-bottom">
                <input type="text" placeholder="wpvivid.com" option="white_label_setting" name="white_label_author" class="all-options" value="<?php esc_attr_e($white_label_author); ?>" />
            </div>
            <div class="wpvivid-element-space-bottom"><?php echo sprintf(__('Enter your preferred author name of the plugin to replace %s.', 'wpvivid'), $white_label_author); ?></div>

            <div class="wpvivid-element-space-bottom"><strong><?php _e('Author URL', 'wpvivid'); ?></strong></div>
            <div class="wpvivid-element-space-bottom">
                <select option="white_label_setting" name="white_label_website_protocol" style="margin-bottom: 3px;">
                    <?php
                    if($white_label_website_protocol === 'http'){
                        $http_protocol  = 'selected';
                        $https_protocol = '';
                    }
                    else{
                        $http_protocol  = '';
                        $https_protocol = 'selected';
                    }
                    ?>
                    <option value="https" <?php esc_attr_e($https_protocol); ?>>https://</option>
                    <option value="http" <?php esc_attr_e($http_protocol); ?>>http://</option>
                </select>
                <input type="text" placeholder="wpvivid.com" option="white_label_setting" name="white_label_website" class="all-options" value="<?php esc_attr_e($white_label_website); ?>" />
            </div>
            <div class="wpvivid-element-space-bottom"><?php echo sprintf(__('Enter your service url to replace %s://%s.', 'wpvivid'), $white_label_website_protocol, $white_label_website); ?></div>

            <div class="wpvivid-element-space-bottom"><strong><?php _e('Documentation Links', 'wpvivid'); ?></strong></div>
            <div class="wpvivid-element-space-bottom">
                <fieldset>
                    <label class="wpvivid-radio" style="float:left; padding-right:1em;">
                        <input type="radio" option="white_label_setting" name="show_sidebar" value="show" <?php esc_attr_e($show_sidebar_link); ?> />Show links
                        <span class="wpvivid-radio-checkmark"></span>
                    </label>
                    <label class="wpvivid-radio" style="float:left; padding-right:1em;">Hide Links
                        <input type="radio" option="white_label_setting" name="show_sidebar" value="hide" <?php esc_attr_e($hide_sidebar_link); ?> />
                        <span class="wpvivid-radio-checkmark"></span>
                    </label>
                </fieldset>
                <div class="wpvivid-element-space-bottom"><?php _e('Show or hide links to WPvivid documentation and support in the sidebar.', 'wpvivid'); ?></div>
            </div>

            <div class="wpvivid-element-space-bottom"><strong><?php _e('Submit A Ticket Link', 'wpvivid'); ?></strong></div>
            <div class="wpvivid-element-space-bottom">
                <fieldset>
                    <label class="wpvivid-radio" style="float:left; padding-right:1em;">
                        <input type="radio" option="white_label_setting" name="show_submit_ticket" value="show" <?php esc_attr_e($show_submit_ticket_link); ?> />Show the link
                        <span class="wpvivid-radio-checkmark"></span>
                    </label>
                    <label class="wpvivid-radio" style="float:left; padding-right:1em;">Hide the link
                        <input type="radio" option="white_label_setting" name="show_submit_ticket" value="hide" <?php esc_attr_e($hide_submit_ticket_link); ?> />
                        <span class="wpvivid-radio-checkmark"></span>
                    </label>
                </fieldset>
                <div class="wpvivid-element-space-bottom"><?php _e('Show or hide the Submit A Ticket link in the sidebar.', 'wpvivid'); ?></div>
            </div>

            <div class="wpvivid-element-space-bottom"><strong><?php _e('White Label Settings Access URL', 'wpvivid'); ?></strong></div>
            <div class="wpvivid-element-space-bottom">
                <label>
                    <input type="text" placeholder="wpvivid_white_label" option="white_label_setting" name="access_white_label_page_slug" class="all-options" value="<?php esc_attr_e($wpvivid_access_white_label_slug); ?>" />
                    <span></span>
                </label>
            </div>
            <div class="wpvivid-element-space-bottom"><?php _e('Enter a slug and add it at the end of the url of your WPvivid plugin page to access the white label settings.', 'wpvivid'); ?></div>
            <div class="wpvivid-element-space-bottom"><?php echo 'Current access url is:'.apply_filters('wpvivid_get_admin_url', '') . 'admin.php?page='.strtolower($white_label_slug).'-dashboard&'.$wpvivid_access_white_label_slug.'=1'; ?></div>

            <div class="wpvivid-element-space-bottom"><strong><?php _e('Reset White Label', 'wpvivid'); ?></strong></div>
            <div class="wpvivid-element-space-bottom">
                <input class="button-primary" id="wpvivid_white_label_reset" type="submit" value="<?php esc_attr_e( 'Reset', 'wpvivid' ); ?>" />
                <span><?php _e('Clear your current white label settings to the default state.', 'wpvivid'); ?></span>
            </div>

            <input class="button-primary" id="wpvivid_white_label_save" type="submit" value="<?php esc_attr_e( 'Save Changes', 'wpvivid' ); ?>" />
        </div>

        <script>
            jQuery('#wpvivid_white_label_save').on('click', function(){
                var new_slug = jQuery('input[option=white_label_setting][name=white_label_slug]').val();
                var hide = false;
                if(jQuery('input[option=white_label_setting][name=white_label_hide_page]').prop('checked')){
                    hide = true;
                }
                else{
                    hide = false;
                }
                var setting_data = wpvivid_ajax_data_transfer_addon('white_label_setting');
                var ajax_data = {
                    'action': 'wpvivid_set_white_label_setting',
                    'setting': setting_data
                };
                jQuery('#wpvivid_white_label_save').css({'pointer-events': 'none', 'opacity': '0.4'});
                wpvivid_post_request_addon(ajax_data, function (data) {
                    try {
                        var jsonarray = jQuery.parseJSON(data);

                        jQuery('#wpvivid_white_label_save').css({'pointer-events': 'auto', 'opacity': '1'});
                        if (jsonarray.result === 'success') {
                            location.href = '<?php echo apply_filters('wpvivid_get_admin_url', '') . 'admin.php?page='; ?>' + new_slug.toLowerCase() + '<?php echo '-dashboard&'.apply_filters('wpvivid_access_white_label_slug', 'wpvivid_white_label')?>=1';
                        }
                        else {
                            alert(jsonarray.error);
                        }
                    }
                    catch (err) {
                        alert(err);
                        jQuery('#wpvivid_white_label_save').css({'pointer-events': 'auto', 'opacity': '1'});
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown) {
                    jQuery('#wpvivid_white_label_save').css({'pointer-events': 'auto', 'opacity': '1'});
                    var error_message = wpvivid_output_ajaxerror('saving white label settings', textStatus, errorThrown);
                    alert(error_message);
                });
            });

            jQuery('#wpvivid_white_label_reset').on('click', function(){
                var descript = '<?php _e('Are you sure you want to reset your white label settings?', 'wpvivid'); ?>';
                var ret = confirm(descript);
                if(ret === true)
                {
                    var new_slug = 'WPvivid';
                    var ajax_data = {
                        'action': 'wpvivid_reset_white_label'
                    };
                    jQuery('#wpvivid_white_label_reset').css({'pointer-events': 'none', 'opacity': '0.4'});
                    wpvivid_post_request_addon(ajax_data, function (data) {
                        try {
                            var jsonarray = jQuery.parseJSON(data);

                            jQuery('#wpvivid_white_label_reset').css({'pointer-events': 'auto', 'opacity': '1'});
                            if (jsonarray.result === 'success') {
                                location.href = '<?php echo apply_filters('wpvivid_get_admin_url', '') . 'admin.php?page='; ?>' + new_slug.toLowerCase() + '<?php echo '-dashboard&'.apply_filters('wpvivid_access_white_label_slug', 'wpvivid_white_label')?>=1';
                            }
                            else {
                                alert(jsonarray.error);
                            }
                        }
                        catch (err) {
                            alert(err);
                            jQuery('#wpvivid_white_label_reset').css({'pointer-events': 'auto', 'opacity': '1'});
                        }
                    }, function (XMLHttpRequest, textStatus, errorThrown) {
                        jQuery('#wpvivid_white_label_reset').css({'pointer-events': 'auto', 'opacity': '1'});
                        var error_message = wpvivid_output_ajaxerror('saving white label settings', textStatus, errorThrown);
                        alert(error_message);
                    });
                }
            });
        </script>
        <?php
    }

    public function reset_white_label()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security();
        try
        {
            delete_option('white_label_setting');
            $ret['result']=WPVIVID_PRO_SUCCESS;
            echo json_encode($ret);
        }
        catch (Exception $error)
        {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function set_white_label_setting()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security();
        try
        {
            if(isset($_POST['setting'])&&!empty($_POST['setting']))
            {
                $json_setting = $_POST['setting'];
                $json_setting = stripslashes($json_setting);
                $setting = json_decode($json_setting, true);
                if (is_null($setting))
                {
                    echo 'json decode failed';
                    die();
                }
                $ret = $this->check_white_label_option($setting);
                if($ret['result']!=WPVIVID_PRO_SUCCESS)
                {
                    echo json_encode($ret);
                    die();
                }

                $setting_data = array();
                $setting_data['white_label_display'] = $setting['white_label_display'];
                $setting_data['white_label_slug'] = $setting['white_label_slug'];
                $setting_data['white_label_support_email'] = $setting['white_label_support_email'];
                $setting_data['white_label_website_protocol'] = $setting['white_label_website_protocol'];
                $setting_data['white_label_website'] = $setting['white_label_website'];
                $setting_data['white_label_author'] = $setting['white_label_author'];
                $setting_data['access_white_label_page_slug'] = $setting['access_white_label_page_slug'];
                $setting_data['show_sidebar']= $setting['show_sidebar'];
                $setting_data['show_submit_ticket'] = $setting['show_submit_ticket'];

                //$old_options=get_option('white_label_setting',array());

                update_option('white_label_setting',$setting_data);

                //$this->change_file_data($setting_data,$old_options);

                do_action('wpvivid_action_white_label_edit_path', $setting['white_label_slug']);
                echo json_encode($ret);
            }
        }
        catch (Exception $error)
        {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function check_white_label_option($data)
    {
        $ret['result']=WPVIVID_PRO_FAILED;
        if(!isset($data['white_label_display']))
        {
            $ret['error']=__('The white label is required.', 'wpvivid');
            return $ret;
        }
        $data['white_label_display']=sanitize_text_field($data['white_label_display']);
        if(empty($data['white_label_display']))
        {
            $ret['error']=__('The white label is required.', 'wpvivid');
            return $ret;
        }

        if(!isset($data['white_label_slug']))
        {
            $ret['error']=__('The slug is required.', 'wpvivid');
            return $ret;
        }
        $data['white_label_slug']=sanitize_text_field($data['white_label_slug']);
        if(empty($data['white_label_slug']))
        {
            $ret['error']=__('The slug is required.', 'wpvivid');
            return $ret;
        }

        if(!isset($data['white_label_support_email']))
        {
            $ret['error']=__('The support email is required.', 'wpvivid');
            return $ret;
        }
        $data['white_label_support_email']=sanitize_text_field($data['white_label_support_email']);
        if(empty($data['white_label_support_email']))
        {
            $ret['error']=__('The support email is required.', 'wpvivid');
            return $ret;
        }

        if(!isset($data['white_label_website']))
        {
            $ret['error']=__('The website is required.', 'wpvivid');
            return $ret;
        }
        $data['white_label_website']=sanitize_text_field($data['white_label_website']);
        if(empty($data['white_label_website']))
        {
            $ret['error']=__('The website is required.', 'wpvivid');
            return $ret;
        }

        if(!isset($data['white_label_author']))
        {
            $ret['error']=__('The author is required.', 'wpvivid');
            return $ret;
        }
        $data['white_label_author']=sanitize_text_field($data['white_label_author']);
        if(empty($data['white_label_author']))
        {
            $ret['error']=__('The author is required.', 'wpvivid');
            return $ret;
        }

        if(!isset($data['access_white_label_page_slug']))
        {
            $ret['error']=__('The access url is required.', 'wpvivid');
            return $ret;
        }
        $data['access_white_label_page_slug']=sanitize_text_field($data['access_white_label_page_slug']);
        if(empty($data['access_white_label_page_slug']))
        {
            $ret['error']=__('The access url is required.', 'wpvivid');
            return $ret;
        }


        $ret['result']=WPVIVID_PRO_SUCCESS;
        return $ret;
    }

    public function export_setting_addon($json)
    {
        $default = array();
        $white_label_setting=apply_filters('wpvivid_get_white_label_option', 'white_label_setting', $default);
        $json['data']['white_label_setting'] = $white_label_setting;
        return $json;
    }

    public function get_dashboard_screens($screens)
    {
        $new_screens=array();

        $white_label_setting=apply_filters('wpvivid_get_white_label_option', 'white_label_setting', false);
        if(!empty($white_label_setting['white_label_display']) && !empty($white_label_setting['white_label_slug']))
        {
            $search = 'wpvivid';

            $white_label_prefix= strtolower($white_label_setting['white_label_display']);
            $white_label_prefix = str_replace(' ', '-', $white_label_prefix);
            foreach ($screens as $screen)
            {
                $label_slug = strtolower($white_label_setting['white_label_slug']);
                if($screen['is_top'])
                {
                    if(isset($_REQUEST['auto_backup'])&&$_REQUEST['auto_backup']==1)
                    {
                        $label_slug = str_replace($search, $label_slug, $screen['screen_id']);
                        $screen['screen_id']=$label_slug;
                    }
                    else
                    {
                        $label_slug = str_replace($search, $label_slug, $screen['menu_slug']);
                        $screen['screen_id']='toplevel_page_'.$label_slug;
                    }
                }
                else
                {
                    $label_slug = str_replace($search, $label_slug, $screen['menu_slug']);
                    $screen['screen_id']=$white_label_prefix.'_page_'.$label_slug;
                }
                $new_screens[]=$screen;
            }
            return $new_screens;
        }
        else
        {
            return $screens;
        }
    }

    public function get_staging_screens($screens)
    {
        $new_screens=array();

        $white_label_setting=apply_filters('wpvivid_get_white_label_option', 'white_label_setting', false);
        if(!empty($white_label_setting['white_label_display']) && !empty($white_label_setting['white_label_slug']))
        {
            $search = 'wpvividstg';

            $white_label_prefix= strtolower($white_label_setting['white_label_display']);
            $white_label_prefix = str_replace(' ', '-', $white_label_prefix);
            foreach ($screens as $screen)
            {
                $label_slug = strtolower($white_label_setting['white_label_slug']);
                $label_slug = str_replace($search, $label_slug, $screen['menu_slug']);

                if($screen['is_top'])
                {
                    $screen['screen_id']=$white_label_prefix.'_page_'.$label_slug;
                }
                else
                {
                    $screen['screen_id']=$white_label_prefix.'_page_'.$label_slug;
                }
                $new_screens[]=$screen;
            }
        }
        else
        {
            $search = 'wpvividstg';
            $replace = 'wpvivid';
            foreach ($screens as $screen)
            {
                $label_slug = str_replace($search, $replace, $screen['menu_slug']);

                if($screen['is_top'])
                {
                    $screen['screen_id']='wpvivid-plugin_page_'.$label_slug;
                }
                else
                {
                    if(preg_match('/_page_.*/',$screen['screen_id'],$matches))
                    {
                        $need_replace = $matches[0];
                        $label_prefix = str_replace($need_replace, '', $screen['screen_id']);
                        $screen['screen_id']=$label_prefix.'_page_'.$label_slug;
                    }
                }
                $new_screens[]=$screen;
            }
        }
        return $new_screens;
    }

    /*public function change_file_data($options,$old_options=array())
    {
        $plugin='wpvivid-backup-pro/wpvivid-backup-pro.php';
        $file=WP_PLUGIN_DIR . '/' . $plugin;
        $new_plugin_name=$options['white_label_display'];
        $old_plugin_name=isset($old_options['white_label_display'])?$old_options['white_label_display']:'WPvivid Plugins Pro';

        $old_lines='* Plugin Name:       '.$old_plugin_name;
        $new_lines='* Plugin Name:       '.$new_plugin_name;
        $default_lines='* Plugin Name:       WPvivid Plugins Pro';
        $file_contents = file_get_contents($file);

        $file_contents = str_replace($old_lines,$new_lines,$file_contents);
        $file_contents = str_replace($default_lines,$new_lines,$file_contents);

        file_put_contents($file,$file_contents);
    }

    public function check_pings_name()
    {
        $plugin='wpvivid-backup-pro/wpvivid-backup-pro.php';

        $plugin_headers = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
        if(isset($plugin_headers['Name']))
        {
            $options=get_option('white_label_setting',array());
            $plugin_name=isset($options['white_label_display'])?$options['white_label_display']:'WPvivid Plugins Pro';

            if($plugin_name!=$plugin_headers['Name'])
            {
                $this->change_file_data($options);
            }
        }

    }*/
}