<?php

/**
 * WPvivid addon: yes
 * Addon Name: wpvivid-role-cap-addons
 * Description: Pro
 * Version: 2.2.21
 * Need_init: yes
 * Interface Name: WPvivid_Role_Cap_addon_ex
 */
if (!defined('WPVIVID_BACKUP_PRO_PLUGIN_DIR'))
{
    die;
}

class WPvivid_Role_Cap_addon_ex
{
    public $main_tab;

    public function __construct()
    {
        add_action('wp_ajax_wpvivid_allowed_manage_caps', array($this, 'allowed_manage_caps'));
        //
        add_action('wp_ajax_wpvivid_enable_user_role_caps', array($this, 'enable_user_role_caps'));
        add_action('wp_ajax_wpvivid_edit_user_role_caps', array($this, 'edit_user_role_caps'));
        //
        add_action('wp_ajax_wpvivid_edit_role_caps', array($this, 'edit_role_caps'));
        add_action('wp_ajax_wpvivid_save_user_role_caps', array($this, 'save_user_role_caps'));
        //
        add_action('wp_ajax_wpvivid_save_role_caps', array($this, 'save_role_caps'));
        //filters
        add_filter('wpvivid_ajax_check_security', array($this, 'ajax_check_security'), 20);

        add_filter('wpvivid_is_user_super_admin',array($this, 'is_user_super_admin'));
        add_filter('wpvivid_current_user_can',array($this, 'current_user_can'),10,2);
        add_filter('wpvivid_menu_capability',array($this, 'menu_capability'),10,2);
        add_filter('wpvivid_current_user_show_toolbar', array($this, 'current_user_show_toolbar'), 20, 2);
        //
        add_filter('wpvivid_get_dashboard_menu', array($this, 'get_dashboard_menu'), 20, 2);
        add_filter('wpvivid_get_dashboard_screens', array($this, 'get_dashboard_screens'), 20);
    }

    public function get_dashboard_menu($submenus,$parent_slug)
    {
        $display = apply_filters('wpvivid_get_menu_capability_addon', 'menu_role_capabilities');
        if($display)
        {
            $submenu['parent_slug'] = $parent_slug;
            $submenu['page_title'] = apply_filters('wpvivid_white_label_display', 'Roles & Capabilities');
            $submenu['menu_title'] = 'Roles & Capabilities';

            $submenu['capability'] = apply_filters("wpvivid_menu_capability","administrator","wpvivid-capabilities");

            $submenu['menu_slug'] = strtolower(sprintf('%s-capabilities', apply_filters('wpvivid_white_label_slug', 'wpvivid')));
            $submenu['index'] = 14;
            $submenu['function'] = array($this, 'init_page');
            $submenus[$submenu['menu_slug']] = $submenu;
        }

        return $submenus;
    }

    public function get_dashboard_screens($screens)
    {
        $screen['menu_slug']='wpvivid-capabilities';
        $screen['screen_id']='wpvivid-plugin_page_wpvivid-capabilities';
        $screen['is_top']=false;
        $screens[]=$screen;
        return $screens;
    }

    public function is_user_super_admin($is_super_admin)
    {
        $allowed_manage_caps=get_option('wpvivid_allowed_manage_caps',false);
        if($allowed_manage_caps===false)
        {
            return true;
        }
        else
        {
            $user = wp_get_current_user();
            if($user->ID==$allowed_manage_caps)
            {
                return true;
            }
            else
            {
                return false;
            }
        }
    }

    public function current_user_can($can,$capability)
    {
        $super_admin=get_option('wpvivid_allowed_manage_caps',false);
        if($super_admin!==false)
        {
            $user = wp_get_current_user();
            if($user->ID==$super_admin)
            {
                return true;
            }
            else
            {
                if(current_user_can($capability))
                {
                    return true;
                }
                else
                {
                    return false;
                }
            }
        }
        else
        {
            return true;
        }
    }

    public function current_user_show_toolbar($show_toolbar, $capability)
    {
        $super_admin=get_option('wpvivid_allowed_manage_caps',false);
        if($super_admin!==false)
        {
            $user = wp_get_current_user();
            if($user->ID==$super_admin)
            {
                $show_toolbar=true;
            }
            else
            {
                if(current_user_can($capability))
                {
                    $show_toolbar=true;
                }
                else
                {
                    $show_toolbar=false;
                }
            }
        }
        else
        {
            if(is_admin()&&current_user_can('administrator'))
            {
                $show_toolbar=true;
            }
            else
            {
                if(current_user_can($capability))
                {
                    $show_toolbar=true;
                }
                else
                {
                    $show_toolbar=false;
                }
            }
        }
        return $show_toolbar;
    }

    public function menu_capability($capability,$menu)
    {
        if (current_user_can('administrator'))
        {
            $super_admin=get_option('wpvivid_allowed_manage_caps',false);
            if($super_admin!==false)
            {
                $user = wp_get_current_user();
                if($user->ID==$super_admin)
                {
                    return 'administrator';
                }

                if($menu=="wpvivid-capabilities")
                {
                    return "wpvivid-can-manage-capabilities";
                }
                else if($menu=="wpvivid-dashboard")
                {
                    return "wpvivid-can-use-plugins";
                }
                else if($menu=="wpvivid-imgoptim")
                {
                    if( apply_filters('wpvivid_current_user_can',true,'wpvivid-can-use-cdn'))
                    {
                        return "wpvivid-can-use-cdn";
                    }
                    else if( apply_filters('wpvivid_current_user_can',true,'wpvivid-can-use-lazy-load'))
                    {
                        return "wpvivid-can-use-lazy-load";
                    }
                    else
                    {
                        return "wpvivid-can-use-image-optimization";
                    }
                }
                else if($menu=="wpvivid-setting")
                {
                    return "wpvivid-can-setting";
                }
                else if($menu=="wpvivid-backup-and-restore")
                {
                    return "wpvivid-can-mange-backup";
                }
                else if($menu=="wpvivid-remote")
                {
                    return "wpvivid-can-mange-remote";
                }
                else if($menu=="wpvivid-debug")
                {
                    return "wpvivid-can-backup";
                }
                else if($menu=="wpvivid-export-import")
                {
                    return "wpvivid-can-use-export";
                }
                else if($menu=="wpvivid-import-site")
                {
                    return "wpvivid-can-import-site";
                }
                else if($menu=="wpvivid-backup")
                {
                    if(isset($_REQUEST['auto_backup'])&&$_REQUEST['auto_backup']==1)
                    {
                        return "wpvivid-rollback";
                    }
                    else
                    {
                        return "wpvivid-can-backup";
                    }
                }
                else if($menu=="wpvivid-schedule")
                {
                    return "wpvivid-can-use-schedule";
                }
                else if($menu=="wpvivid-image-cleaner")
                {
                    return "wpvivid-can-use-image-cleaner";
                }
                else if($menu=="wpvivid-export-site")
                {
                    return "wpvivid-can-export-site";
                }
                else if($menu=="wpvivid-rollback")
                {
                    return "wpvivid-rollback";
                }
                //
            }

            return $capability;
        }
        else
        {
            if($menu=="wpvivid-dashboard")
            {
                return "wpvivid-can-use-plugins";
            }
            else if($menu=="wpvivid-imgoptim")
            {
                if( apply_filters('wpvivid_current_user_can',true,'wpvivid-can-use-cdn'))
                {
                    return "wpvivid-can-use-cdn";
                }
                else if( apply_filters('wpvivid_current_user_can',true,'wpvivid-can-use-lazy-load'))
                {
                    return "wpvivid-can-use-lazy-load";
                }
                else
                {
                    return "wpvivid-can-use-image-optimization";
                }
            }
            else if($menu=="wpvivid-setting")
            {
                return "wpvivid-can-setting";
            }
            else if($menu=="wpvivid-backup-and-restore")
            {
                return "wpvivid-can-mange-backup";
            }
            else if($menu=="wpvivid-remote")
            {
                return "wpvivid-can-mange-remote";
            }
            else if($menu=="wpvivid-debug")
            {
                return "wpvivid-can-backup";
            }
            else if($menu=="wpvivid-export-import")
            {
                return "wpvivid-can-use-export";
            }
            else if($menu=="wpvivid-import-site")
            {
                return "wpvivid-can-import-site";
            }
            else if($menu=="wpvivid-backup")
            {
                if(isset($_REQUEST['auto_backup'])&&$_REQUEST['auto_backup']==1)
                {
                    return "wpvivid-rollback";
                }
                else
                {
                    return "wpvivid-can-backup";
                }
            }
            else if($menu=="wpvivid-schedule")
            {
                return "wpvivid-can-use-schedule";
            }
            else if($menu=="wpvivid-image-cleaner")
            {
                return "wpvivid-can-use-image-cleaner";
            }
            else if($menu=="wpvivid-export-site")
            {
                return "wpvivid-can-export-site";
            }
            else if($menu=="wpvivid-rollback")
            {
                return "wpvivid-rollback";
            }

            return $capability;
        }
    }

    public function init_page()
    {
        ?>
        <div class="wrap wpvivid-canvas">
            <div id="icon-options-general" class="icon32"></div>
            <h1><?php esc_attr_e( apply_filters('wpvivid_white_label_display', 'WPvivid').' Plugins - Roles & Capabilities', 'wpvivid' ); ?></h1>
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <!-- main content -->
                    <div id="post-body-content">
                        <div class="meta-box-sortables ui-sortable">
                            <div class="wpvivid-backup">
                                <div class="wpvivid-welcome-bar wpvivid-clear-float">
                                    <div class="wpvivid-welcome-bar-left">
                                        <p><span class="dashicons dashicons-groups wpvivid-dashicons-large wpvivid-dashicons-blue"></span><span class="wpvivid-page-title">Roles & Capabilities</span></p>
                                        <span class="about-description">Manage user access to <?php echo apply_filters('wpvivid_white_label_display', 'WPvivid'); ?> pro based on user roles.</span>
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
                                        <span class="dashicons dashicons-editor-help wpvivid-dashicons-orange"></span>
                                        <span><strong>By default, only users with the <u>administrator</u> users role can access <?php echo apply_filters('wpvivid_white_label_display', 'WPvivid'); ?> Plugin Pro.</strong></span>
                                    </div>
                                </div>

                                <div class="wpvivid-canvas wpvivid-clear-float">
                                    <?php
                                    if(!class_exists('WPvivid_Tab_Page_Container_Ex'))
                                        include_once WPVIVID_BACKUP_PRO_PLUGIN_DIR . 'includes/class-wpvivid-tab-page-container-ex.php';
                                    $this->main_tab=new WPvivid_Tab_Page_Container_Ex();
                                    $this->main_tab->is_parent_tab=0;
                                    $tabs=array();

                                    $args['span_class']='dashicons dashicons-groups';
                                    $args['span_style']='color:#007cba; padding-right:0.5em;margin-top:0.1em;';
                                    $args['div_style']='display:block;';
                                    $args['is_parent_tab']=0;
                                    $tabs['roles_caps']['title']='Roles & Capabilities';
                                    $tabs['roles_caps']['slug']='roles_caps';
                                    $tabs['roles_caps']['callback']=array($this, 'output_caps');
                                    $tabs['roles_caps']['args']=$args;

                                    $args['span_class']='dashicons dashicons-admin-generic';
                                    $args['span_style']='color:grey;padding-right:0.5em;margin-top:0.1em;';
                                    $args['div_style']='';
                                    $args['hide']=1;
                                    $args['can_delete']=1;
                                    $args['redirect']='roles_caps';
                                    $tabs['edit_roles']['title']='Edit Capabilities';
                                    $tabs['edit_roles']['slug']='edit_roles';
                                    $tabs['edit_roles']['callback']=array($this, 'output_edit_roles');
                                    $tabs['edit_roles']['args']=$args;

                                    $args['span_class']='dashicons dashicons-admin-generic';
                                    $args['span_style']='color:grey;padding-right:0.5em;margin-top:0.1em;';
                                    $args['div_style']='';
                                    $args['hide']=1;
                                    $args['can_delete']=1;
                                    $args['redirect']='roles_caps';
                                    $tabs['edit_user']['title']='Edit Capabilities';
                                    $tabs['edit_user']['slug']='edit_user';
                                    $tabs['edit_user']['callback']=array($this, 'output_edit_user');
                                    $tabs['edit_user']['args']=$args;

                                    if(!empty($tabs))
                                    {
                                        foreach ($tabs as $key=>$tab)
                                        {
                                            $this->main_tab->add_tab($tab['title'],$tab['slug'],$tab['callback'], $tab['args']);
                                        }

                                        $this->main_tab->display();
                                    }
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
            jQuery(document).ready(function ($)
            {
                <?php
                if(isset($_REQUEST['tabs']))
                {
                ?>
                jQuery( document ).trigger( '<?php echo $this->main_tab->container_id; ?>-show',[ '<?php echo $_REQUEST['tabs'];?>', '<?php echo $_REQUEST['tabs'];?>' ]);
                <?php
                }

                if(isset($_REQUEST['role_cap']))
                {
                ?>
                jQuery( document ).trigger( '<?php echo $this->main_tab->container_id ?>-show',[ 'role_cap', 'role_cap' ]);
                <?php
                }
                ?>
            });
        </script>
        <?php
    }

    public function output_caps()
    {
        $user = wp_get_current_user();

        $allowed_manage_caps=get_option('wpvivid_allowed_manage_caps',false);
        if($allowed_manage_caps===false)
        {
            $check_super_admin=" ";
            $show_admin_user_manage= ' display:none;';
            $current_allowed="0";
        }
        else
        {
            if($user->ID==$allowed_manage_caps)
            {
                $check_super_admin="checked";
                $show_admin_user_manage="";
                $current_allowed="1";
            }
            else
            {
                $check_super_admin=" ";
                $show_admin_user_manage= ' display:none;';
                $current_allowed="0";
            }
        }

        $login=$user->get("user_login");

        ?>
        <div>
            <p></p>
            <div>
                <strong><span>Role:</span><span> Administrator</span></strong>
                <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip wpvivid-tooltip-padding-top" style="padding-top: 0px;margin-right: 0px;">
                    <div class="wpvivid-bottom">
                        <!-- The content you need -->
                        <p>All administrators can access <?php apply_filters('wpvivid_white_label_display', 'WPvivid')?> plugins before setting super admin.</p>
                        <i></i>
                        <!-- do not delete this line -->
                    </div>
                </span>
            </div>
            <p></p>
            <label class="wpvivid-switch">
                <input id="wpvivid_allowed_manage_caps" type="checkbox" <?php echo $check_super_admin; ?> >
                <span class="wpvivid-slider wpvivid-round"></span>
            </label>
            <span>Set the </span><span>current <strong>administrator</strong>: </span><span style="color:#007cba;"><?php echo $login; ?></span><span> as the super administrator.</span>
            <div id="wpvivid_license_box" style="display:none;">
                <p>
                    <input id="wpvivid_license" type="text" placeholder="Please re-enter your license" style="width:300px;">
                    <input id="wpvivid_set_super_admin" type="submit" class="button action top-action" value="OK">
                </p>
            </div>
        </div>
        <div id="wpvivid_admin_user_manage" style="margin-top:1rem;<?php echo $show_admin_user_manage; ?>" >
            <table class="wp-list-table widefat fixed striped table-view-list users">
                <tbody id="wpvivid_user_role_list">
                <?php
                $users=get_users(array( 'role__in' => array('administrator')));
                if(!empty($users))
                {
                    foreach ($users as $user)
                    {
                        if($allowed_manage_caps!==false&&$user->ID==$allowed_manage_caps)
                        {
                            echo "<tr>";
                            echo "<td>";
                            echo '<span class="dashicons dashicons-businessman" style="color:#007cba; padding-right:0.5em;"></span>';
                        }
                        else
                        {
                            continue;
                        }

                        echo '<span>'.$user->user_login.' </span>';
                        echo '<span class="dashicons dashicons-awards wpvivid-dashicons-green"></span>';
                        echo "</td>";
                        echo '<td>';

                        echo '<label style="cursor: pointer;"><span class="dashicons dashicons-admin-generic wpvivid-dashicons-grey"></span>';
                        echo '<span class="row-title">Full access permissions</span></label>';
                        echo '</td>';
                        echo '</tr>';
                        break;
                    }

                    foreach ( $users as $user)
                    {
                        if($allowed_manage_caps!==false&&$user->ID==$allowed_manage_caps)
                        {
                            continue;
                        }

                        echo "<tr>";
                        echo "<td id='$user->ID'>";
                        if($this->is_user_enabled($user->ID))
                        {
                            echo '<span class="dashicons dashicons-businessman wpvivid-user-cap-icon" style="color:#007cba; padding-right:0.5em;"></span>';
                        }
                        else
                        {
                            echo '<span class="dashicons dashicons-businessman wpvivid-user-cap-icon" style="color:grey; padding-right:0.5em;"></span>';
                        }

                        echo '<span>'.$user->user_login.' </span>';
                        /*
                        if($allowed_manage_caps!==false&&$user->ID==$allowed_manage_caps)
                        {
                            echo '<span class="dashicons dashicons-awards wpvivid-dashicons-green"></span>';
                        }
                        else
                        {
                            echo '<label class="wpvivid-switch">';
                            if(isset($user_list[$user->ID]))
                            {
                                echo '<input class="wpvivid-enable-user-cap" type="checkbox" checked data-id="'.$user->ID.'" >';
                            }
                            else
                            {
                                echo '<input class="wpvivid-enable-user-cap" type="checkbox" data-id="'.$user->ID.'" >';
                            }

                            echo '<span class="wpvivid-slider wpvivid-round"></span>';
                            echo '</label>';
                        }*/
                        echo "</td>";
                        echo '<td>';

                        echo '<span class="dashicons dashicons-admin-generic wpvivid-dashicons-grey"></span>';
                        echo '<span><a class="row-title wpvivid-edit-user-cap" style="cursor: pointer;" data-id="'.$user->ID.'">Edit</a></span>';
                        echo '</td>';
                        echo '</tr>';
                    }
                }
                ?>
                </tbody>
            </table>
        </div>

        <script>
            var wpvivid_current_allowed="<?php echo $current_allowed; ?>";
            jQuery('#wpvivid_set_super_admin').click(function()
            {
                var allowed='0';
                var license=jQuery('#wpvivid_license').val();
                if(jQuery('#wpvivid_allowed_manage_caps').prop('checked'))
                {
                    allowed = '1';
                }
                else {
                    allowed = '0';
                }
                var ajax_data= {
                    'action': 'wpvivid_allowed_manage_caps',
                    'allowed':allowed,
                    'license':license
                };

                jQuery('#wpvivid_set_super_admin').css({'pointer-events': 'none', 'opacity': '0.4'});
                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    jQuery('#wpvivid_set_super_admin').css({'pointer-events': 'auto', 'opacity': '1'});
                    var jsonarray = jQuery.parseJSON(data);
                    if(jsonarray.result === "success")
                    {
                        location.reload();
                    }
                    else
                    {
                        alert(jsonarray.error);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    jQuery('#wpvivid_set_super_admin').css({'pointer-events': 'auto', 'opacity': '1'});
                    var error_message = wpvivid_output_ajaxerror('Set current administrator', textStatus, errorThrown);
                    alert(error_message);
                });
            });

            jQuery('#wpvivid_allowed_manage_caps').click(function()
            {
                var Obj = jQuery(this);
                if(Obj.prop('checked'))
                {
                    jQuery('#wpvivid_license_box').show();
                }
                else
                {
                    if(wpvivid_current_allowed=='0')
                    {
                        jQuery('#wpvivid_license_box').hide();
                        return;
                    }

                    var ajax_data= {
                        'action': 'wpvivid_allowed_manage_caps',
                        'allowed':'0',
                    };

                    wpvivid_post_request_addon(ajax_data, function (data)
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if(jsonarray.result === "success")
                        {
                            location.reload();
                        }
                        else
                        {
                            alert(jsonarray.error);
                        }
                    }, function (XMLHttpRequest, textStatus, errorThrown)
                    {
                        var error_message = wpvivid_output_ajaxerror('Set current administrator', textStatus, errorThrown);
                        alert(error_message);
                    });
                }
            });

            jQuery('#wpvivid_user_role_list').on('click', '.wpvivid-enable-user-cap', function()
            {
                var user=jQuery(this).data("id");
                var allowed='0';
                if(jQuery(this).prop('checked'))
                {
                    allowed = '1';
                }
                else {
                    allowed = '0';
                }

                var ajax_data= {
                    'action': 'wpvivid_enable_user_role_caps',
                    'user': user,
                    'allowed':allowed
                };
                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if(jsonarray.result === "success")
                        {
                        }
                        else
                        {
                            alert(jsonarray.error);
                        }
                    }
                    catch (err)
                    {
                        alert(err);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    var error_message = wpvivid_output_ajaxerror('Changing admin user caps', textStatus, errorThrown);
                    alert(error_message);
                });
            });

            jQuery('#wpvivid_user_role_list').on('click', '.wpvivid-edit-user-cap', function()
            {
                var user=jQuery(this).data("id");
                var ajax_data= {
                    'action': 'wpvivid_edit_user_role_caps',
                    'user': user
                };
                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if(jsonarray.result === "success")
                        {
                            jQuery('#wpvivid_edit_user_role_cap').html(jsonarray.html);
                            jQuery( document ).trigger( '<?php echo $this->main_tab->container_id ?>-show',[ 'edit_user', 'roles_caps' ]);
                        }
                        else
                        {
                            alert(jsonarray.error);
                        }
                    }
                    catch (err)
                    {
                        alert(err);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    var error_message = wpvivid_output_ajaxerror('Edit user caps', textStatus, errorThrown);
                    alert(error_message);
                });
            });
        </script>
        <?php
        $this->output_roles_caps();
    }

    public function output_roles_caps()
    {
        global $wp_roles;
        $roles = $wp_roles->roles;
        $edit_roles=array();
        $author_roles=array();
        $contributor_roles=array();
        $subscriber_roles=array();
        $other_roles=array();
        foreach ($roles as $key=>$role)
        {
            if($role['name']=='Editor')
            {
                $edit_roles[$key]=$role;
            }
            else if($role['name']=='Author'||$role['name']=='Shop manager')
            {
                $author_roles[$key]=$role;
            }
            else if($role['name']=='Contributor')
            {
                $contributor_roles[$key]=$role;
            }
            else if($role['name']=='Subscriber'||$role['name']=='Customer'||$role['name']=='Affiliate')
            {
                $subscriber_roles[$key]=$role;
            }
            else if($role['name']=='Administrator')
            {
                continue;
            }
            else
            {
                $other_roles[$key]=$role;
            }
        }

        if(!empty($edit_roles))
        {
            ?>
            <div style="padding-bottom:1rem;border-bottom:1px solid #ccc;">
                <p></p>
                <div>
                    <strong><span>Role:</span> <span>Editor</span></strong>
                </div>
                <div style="margin-top:1rem;">
                    <table class="wp-list-table widefat fixed striped roles">
                        <tbody id="the-list">
                        <?php
                        foreach ($edit_roles as $key=>$role)
                        {
                            ?>
                            <tr>
                                <td id="<?php echo $key; ?>">
                                    <?php
                                    if($this->is_role_enabled($key))
                                    {
                                        echo '<span class="dashicons dashicons-businessman wpvivid-role-cap-icon" style="color:#007cba; padding-right:0.5em;"></span>';
                                    }
                                    else
                                    {
                                        echo '<span class="dashicons dashicons-businessman wpvivid-role-cap-icon" style="color:grey; padding-right:0.5em;"></span>';
                                    }
                                    ?>
                                    <span><?php echo $role['name']; ?></span>
                                </td>
                                <td>
                                    <span class="dashicons dashicons-admin-generic wpvivid-dashicons-grey"></span>
                                    <span><a class="row-title wpvivid-edit-role-cap" style="cursor: pointer;" data-id="<?php echo $key; ?>" >Edit</a></span>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php
        }

        if(!empty($author_roles))
        {
            ?>
            <div style="padding-bottom:1rem;border-bottom:1px solid #ccc;">
                <p></p>
                <div>
                    <strong><span>Role:</span> <span>Author</span></strong>
                </div>
                <div style="margin-top:1rem;">
                    <table class="wp-list-table widefat fixed striped roles">
                        <tbody id="the-list">
                        <?php
                        foreach ($author_roles as $key=>$role)
                        {
                            ?>
                            <tr>
                                <td id="<?php echo $key; ?>">
                                    <?php
                                    if($this->is_role_enabled($key))
                                    {
                                        echo '<span class="dashicons dashicons-businessman wpvivid-role-cap-icon" style="color:#007cba; padding-right:0.5em;"></span>';
                                    }
                                    else
                                    {
                                        echo '<span class="dashicons dashicons-businessman wpvivid-role-cap-icon" style="color:grey; padding-right:0.5em;"></span>';
                                    }
                                    ?>
                                    <span><?php echo $role['name']; ?></span>
                                </td>
                                <td>
                                    <span class="dashicons dashicons-admin-generic wpvivid-dashicons-grey"></span>
                                    <span><a class="row-title wpvivid-edit-role-cap" style="cursor: pointer;" data-id="<?php echo $key; ?>" >Edit</a></span>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php
        }

        if(!empty($contributor_roles))
        {
            ?>
            <div style="padding-bottom:1rem;border-bottom:1px solid #ccc;">
                <p></p>
                <div>
                    <strong><span>Role:</span> <span>Contributor</span></strong>
                </div>
                <div style="margin-top:1rem;">
                    <table class="wp-list-table widefat fixed striped roles">
                        <tbody id="the-list">
                        <?php
                        foreach ($contributor_roles as $key=>$role)
                        {
                            ?>
                            <tr>
                                <td id="<?php echo $key; ?>">
                                    <?php
                                    if($this->is_role_enabled($key))
                                    {
                                        echo '<span class="dashicons dashicons-businessman wpvivid-role-cap-icon" style="color:#007cba; padding-right:0.5em;"></span>';
                                    }
                                    else
                                    {
                                        echo '<span class="dashicons dashicons-businessman wpvivid-role-cap-icon" style="color:grey; padding-right:0.5em;"></span>';
                                    }
                                    ?>
                                    <span><?php echo $role['name']; ?></span>
                                </td>
                                <td>
                                    <span class="dashicons dashicons-admin-generic wpvivid-dashicons-grey"></span>
                                    <span><a class="row-title wpvivid-edit-role-cap" style="cursor: pointer;" data-id="<?php echo $key; ?>" >Edit</a></span>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php
        }

        if(!empty($subscriber_roles))
        {
            ?>
            <div style="padding-bottom:1rem;border-bottom:1px solid #ccc;">
                <p></p>
                <div>
                    <strong><span>Role:</span> <span>Subscriber</span></strong>
                </div>
                <div style="margin-top:1rem;">
                    <table class="wp-list-table widefat fixed striped roles">
                        <tbody id="the-list">
                        <?php
                        foreach ($subscriber_roles as $key=>$role)
                        {
                            ?>
                            <tr>
                                <td id="<?php echo $key; ?>">
                                    <?php
                                    if($this->is_role_enabled($key))
                                    {
                                        echo '<span class="dashicons dashicons-businessman wpvivid-role-cap-icon" style="color:#007cba; padding-right:0.5em;"></span>';
                                    }
                                    else
                                    {
                                        echo '<span class="dashicons dashicons-businessman wpvivid-role-cap-icon" style="color:grey; padding-right:0.5em;"></span>';
                                    }
                                    ?>
                                    <span><?php echo $role['name']; ?></span>
                                </td>
                                <td>
                                    <span class="dashicons dashicons-admin-generic wpvivid-dashicons-grey"></span>
                                    <span><a class="row-title wpvivid-edit-role-cap" style="cursor: pointer;" data-id="<?php echo $key; ?>" >Edit</a></span>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php
        }

        if(!empty($other_roles))
        {
            ?>
            <div style="padding-bottom:1rem;border-bottom:1px solid #ccc;">
                <p></p>
                <div>
                    <strong><span>Role:</span> <span>Others</span></strong>
                </div>
                <div style="margin-top:1rem;">
                    <table class="wp-list-table widefat fixed striped roles">
                        <tbody id="the-list">
                        <?php
                        foreach ($other_roles as $key=>$role)
                        {
                            ?>
                            <tr>
                                <td id="<?php echo $key; ?>">
                                    <?php
                                    if($this->is_role_enabled($key))
                                    {
                                        echo '<span class="dashicons dashicons-businessman wpvivid-role-cap-icon" style="color:#007cba; padding-right:0.5em;"></span>';
                                    }
                                    else
                                    {
                                        echo '<span class="dashicons dashicons-businessman wpvivid-role-cap-icon" style="color:grey; padding-right:0.5em;"></span>';
                                    }
                                    ?>
                                    <span><?php echo $role['name']; ?></span>
                                </td>
                                <td>
                                    <span class="dashicons dashicons-admin-generic wpvivid-dashicons-grey"></span>
                                    <span><a class="row-title wpvivid-edit-role-cap" style="cursor: pointer;" data-id="<?php echo $key; ?>" >Edit</a></span>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php
        }

        ?>
        <div style="margin-top:1rem;">
            <span><span class="dashicons dashicons-awards wpvivid-dashicons-green"></span><span>A super admin who can edit user access to <?php echo apply_filters('wpvivid_white_label_display', 'WPvivid')?> pro.</span></span>
            <span><span class="dashicons dashicons-businessman wpvivid-dashicons-blue"></span><span>Full or partial access to <?php echo apply_filters('wpvivid_white_label_display', 'WPvivid')?> pro.</span></span>
            <span><span class="dashicons dashicons-businessman wpvivid-dashicons-grey"></span><span>No access to <?php echo apply_filters('wpvivid_white_label_display', 'WPvivid')?> pro.</span></span>
        </div>
        <script>
            jQuery('.wpvivid-edit-role-cap').click(function()
            {
                var role=jQuery(this).data("id");
                var ajax_data= {
                    'action': 'wpvivid_edit_role_caps',
                    'role': role
                };
                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if(jsonarray.result === "success")
                        {
                            jQuery('#wpvivid_edit_role_cap').html(jsonarray.html);
                            jQuery( document ).trigger( '<?php echo $this->main_tab->container_id ?>-show',[ 'edit_roles', 'roles_caps' ]);
                        }
                        else
                        {
                            alert(jsonarray.error);
                        }
                    }
                    catch (err)
                    {
                        alert(err);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    var error_message = wpvivid_output_ajaxerror('Edit roles', textStatus, errorThrown);
                    alert(error_message);
                });
            });
        </script>
        <?php
    }

    public function is_role_enabled($key)
    {
        global $wp_roles;
        $role_cap=$wp_roles->get_role($key);
        $cap_list=$this->get_wpvivid_caps();

        $all=false;
        foreach ($cap_list as $key=>$cap)
        {
            if ($role_cap->has_cap($key))
            {
                $all=true;
                break;
            }
        }
        return $all;
    }

    public function is_user_enabled($user_id)
    {
        $user = get_user_by('ID',$user_id);
        $cap_list=$this->get_wpvivid_caps();

        $enabled=false;
        foreach ($cap_list as $key=>$cap)
        {
            if ($user->has_cap($key))
            {
                $enabled=true;
                break;
            }
        }
        return $enabled;
    }

    public function output_edit_roles()
    {
        ?>
        <div id="wpvivid_edit_role_cap">
        </div>
        <script>
            //
            jQuery('#wpvivid_edit_role_cap').on('click', '#wpvivid_cap_select_all', function(){
                var Obj = jQuery(this);
                if(Obj.prop('checked'))
                {
                    jQuery('input:checkbox[name=wpvivid-role-caps]').each(function()
                    {
                        jQuery(this).prop('checked', true);
                    });
                }
                else
                {
                    jQuery('input:checkbox[name=wpvivid-role-caps]').each(function()
                    {
                        jQuery(this).prop('checked', false);
                    });
                }
            });

            jQuery('#wpvivid_edit_role_cap').on('click', '#wpvivid_submit_role_cap', function(){
                var Obj = jQuery(this);
                var role = Obj.closest('div').attr('role');
                var cap_option = {};

                jQuery('input:checkbox[name=wpvivid-role-caps]').each(function()
                {
                    var value = jQuery(this).val();
                    if(jQuery(this).prop('checked'))
                    {
                        cap_option[value]=1;
                    }
                    else
                    {
                        cap_option[value]=0;
                    }
                });
                var caps=JSON.stringify(cap_option);
                var ajax_data= {
                    'action': 'wpvivid_save_role_caps',
                    'role': role,
                    'caps': caps
                };
                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    Obj.css({'pointer-events': 'auto', 'opacity': '1'});
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if(jsonarray.result === "success")
                        {
                            alert('Role capabilities have been changed successfully.');
                            if(jsonarray.enabled)
                            {
                                jQuery('td[id='+role+']').find('.wpvivid-role-cap-icon').css('color', '#007cba');
                            }
                            else
                            {
                                jQuery('td[id='+role+']').find('.wpvivid-role-cap-icon').css('color', 'grey');
                            }
                        }
                        else
                        {
                            alert(jsonarray.error);
                        }
                    }
                    catch (err)
                    {
                        alert(err);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    Obj.css({'pointer-events': 'auto', 'opacity': '1'});
                    var error_message = wpvivid_output_ajaxerror('Edit role caps', textStatus, errorThrown);
                    alert(error_message);
                });
            });
        </script>
        <?php
    }

    public function output_edit_user()
    {
        ?>
        <div id="wpvivid_edit_user_role_cap">
        </div>
        <script>
            jQuery('#wpvivid_edit_user_role_cap').on('click', '#wpvivid_user_cap_select_all', function(){
                var Obj = jQuery(this);
                if(Obj.prop('checked'))
                {
                    jQuery('input:checkbox[name=wpvivid-caps]').each(function()
                    {
                        jQuery(this).prop('checked', true);
                    });
                }
                else
                {
                    jQuery('input:checkbox[name=wpvivid-caps]').each(function()
                    {
                        jQuery(this).prop('checked', false);
                    });
                }
            });

            jQuery('#wpvivid_edit_user_role_cap').on('click', '#wpvivid_submit_cap', function()
            {
                var Obj = jQuery(this);
                Obj.css({'pointer-events': 'none', 'opacity': '0.4'});

                var user = Obj.closest('div').attr('user');
                var cap_option = {};

                jQuery('input:checkbox[name=wpvivid-caps]').each(function()
                {
                    var value = jQuery(this).val();
                    if(jQuery(this).prop('checked'))
                    {
                        cap_option[value]=1;
                    }
                    else
                    {
                        cap_option[value]=0;
                    }
                });
                var caps=JSON.stringify(cap_option);
                var ajax_data= {
                    'action': 'wpvivid_save_user_role_caps',
                    'user': user,
                    'caps': caps
                };
                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    Obj.css({'pointer-events': 'auto', 'opacity': '1'});
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if(jsonarray.result === "success")
                        {
                            alert('User capabilities have been changed successfully.');
                            if(jsonarray.enabled)
                            {
                                jQuery('td[id='+user+']').find('.wpvivid-user-cap-icon').css('color', '#007cba');
                            }
                            else
                            {
                                jQuery('td[id='+user+']').find('.wpvivid-user-cap-icon').css('color', 'grey');
                            }
                        }
                        else
                        {
                            alert(jsonarray.error);
                        }
                    }
                    catch (err)
                    {
                        alert(err);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    Obj.css({'pointer-events': 'auto', 'opacity': '1'});
                    var error_message = wpvivid_output_ajaxerror('Edit user caps', textStatus, errorThrown);
                    alert(error_message);
                });
            });
        </script>
        <?php
    }

    public function allowed_manage_caps()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('administrator');

        if(!isset( $_POST['allowed']))
        {
            die();
        }

        $allowed=$_POST['allowed'];
        $caps=$this->get_wpvivid_caps();
        $user = wp_get_current_user();

        if($allowed=='1')
        {
            if(isset($_POST['license']))
            {
                if(empty($_POST['license']))
                {
                    $ret['result']='failed';
                    $ret['error']='A license is required.';
                    echo json_encode($ret);
                    die();
                }

                $license=$_POST['license'];
            }
            else
            {
                $ret['result']='failed';
                $ret['error']='Retrieving user information failed. Please try again later.';
                echo json_encode($ret);
                die();
            }

            $server=new WPvivid_Dashboard_Connect_server();

            $ret=$server->login($license,true);
            if($ret['result']=='success')
            {
                if($ret['status']['check_active'])
                {
                    $info['token']=$ret['user_info'];
                    update_option('wpvivid_pro_user',$info);
                    update_option('wpvivid_dashboard_info',$ret['status']);
                    update_option('wpvivid_last_update_time',time());
                    update_option('wpvivid_last_login_time',time());
                }
                else
                {
                    $ret['result']='failed';
                    $ret['error']='Your site is not activated,please go to the license page to activate';
                    echo json_encode($ret);
                    die();
                }
            }
            else
            {
                echo json_encode($ret);
            }

            $user->add_cap('wpvivid-can-manage-capabilities');

            foreach ($caps as $key => $cap)
            {
                $user->add_cap($key);
            }

            update_option('wpvivid_allowed_manage_caps',$user->ID);
        }
        else
        {
            $user->remove_cap('wpvivid-can-manage-capabilities');

            foreach ($caps as $key => $cap)
            {
                $user->remove_cap($key);
            }

            delete_option('wpvivid_allowed_manage_caps');
        }

        $ret['result']='success';
        echo json_encode($ret);
        die();
    }

    public function get_wpvivid_caps()
    {
        $cap_list=array();

        $cap_can_use_wpvivid['slug']='wpvivid-can-use-plugins';
        $cap_can_use_wpvivid['display']='Access '.apply_filters('wpvivid_white_label_display', 'WPvivid').' Plugins from Admin Menu';
        $cap_can_use_wpvivid['menu_slug']=strtolower(sprintf('%s-dashboard', apply_filters('wpvivid_white_label_slug', 'wpvivid')));
        $cap_can_use_wpvivid['index']=1;
        $cap_can_use_wpvivid['icon']='<span class="dashicons dashicons-menu wpvivid-dashicons-grey"></span>';
        $cap_list['wpvivid-can-use-plugins']=$cap_can_use_wpvivid;

        $cap_can_show_toolbar['slug']='wpvivid-can-show-toolbar';
        $cap_can_show_toolbar['display']='Access '.apply_filters('wpvivid_white_label_display', 'WPvivid').' Plugins from admin Bar';
        $cap_can_show_toolbar['menu_slug']=strtolower(sprintf('%s-can-show-toolbar', apply_filters('wpvivid_white_label_slug', 'wpvivid')));
        $cap_can_show_toolbar['index']=2;
        $cap_can_show_toolbar['icon']='<span class="dashicons dashicons-menu wpvivid-dashicons-grey"></span>';
        $cap_list['wpvivid-can-show-toolbar']=$cap_can_show_toolbar;

        $cap_can_install_plugin['slug']='wpvivid-can-install-plugins';
        $cap_can_install_plugin['display']='Install '.apply_filters('wpvivid_white_label_display', 'WPvivid').' addons';
        $cap_can_install_plugin['menu_slug']=strtolower(sprintf('%s-installer', apply_filters('wpvivid_white_label_slug', 'wpvivid')));
        $cap_can_install_plugin['index']=3;
        $cap_can_install_plugin['icon']='<span class="dashicons dashicons-admin-plugins wpvivid-dashicons-grey"></span>';
        $cap_list['wpvivid-can-install-plugins']=$cap_can_install_plugin;

        $cap_can_setting['slug']='wpvivid-can-setting';
        $cap_can_setting['display']='Settings';
        $cap_can_setting['menu_slug']=strtolower(sprintf('%s-setting', apply_filters('wpvivid_white_label_slug', 'wpvivid')));
        $cap_can_setting['index']=18;
        $cap_can_setting['icon']='<span class="dashicons dashicons-admin-generic wpvivid-dashicons-grey"></span>';
        $cap_list['wpvivid-can-setting']=$cap_can_setting;

        $cap_list=apply_filters('wpvivid_get_role_cap_list',$cap_list);

        return $cap_list;
    }

    public function ajax_check_security($check)
    {
        if(current_user_can('administrator'))
        {
            return true;
        }
        else
        {
            $list=$this->get_wpvivid_caps();
            foreach ($list as $key=>$cap)
            {
                if(current_user_can($key))
                {
                    return true;
                }
            }
            return false;
        }
    }

    public function enable_user_role_caps()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('administrator');
        try
        {
            if(isset($_POST['user']))
            {
                if(!isset( $_POST['allowed']))
                {
                    die();
                }

                $allowed=$_POST['allowed'];

                if(!isset( $_POST['user'])||empty($_POST['user']))
                {
                    $ret['result']='failed';
                    $ret['error']='User name is required.';
                    echo json_encode($ret);
                    die();
                }
                $user_name = $_POST['user'];
                $user = get_user_by('ID',$user_name);
                if($user===false)
                {
                    $ret['result']='failed';
                    $ret['error']='User name not found.';
                    echo json_encode($ret);
                    die();
                }

                if(!user_can($user->ID,'administrator'))
                {
                    $ret['result']='failed';
                    $ret['error']='User must be an administrator.';
                    echo json_encode($ret);
                    die();
                }

                $caps=$this->get_wpvivid_caps();
                if($allowed=='1')
                {
                    foreach ($caps as $key => $cap)
                    {
                        $user->add_cap($key);
                    }
                }
                else
                {
                    foreach ($caps as $key => $cap)
                    {
                        $user->remove_cap($key);
                    }
                }

                $ret['result'] = 'success';
                echo json_encode($ret);
            }
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function edit_user_role_caps()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('administrator');
        try
        {
            if(isset($_POST['user']))
            {
                if(!isset( $_POST['user'])||empty($_POST['user']))
                {
                    $ret['result']='failed';
                    $ret['error']='User name is required.';
                    echo json_encode($ret);
                    die();
                }
                $user_name = $_POST['user'];
                $user = get_user_by('ID',$user_name);
                if($user===false)
                {
                    $ret['result']='failed';
                    $ret['error']='User name not found.';
                    echo json_encode($ret);
                    die();
                }

                if(!user_can($user->ID,'administrator'))
                {
                    $ret['result']='failed';
                    $ret['error']='User must be an administrator.';
                    echo json_encode($ret);
                    die();
                }

                $caps=$this->get_wpvivid_caps();

                $cap_list=array();
                foreach ($caps as $cap)
                {
                    $cap_list[]=$cap;
                }

                usort($cap_list, function ($a, $b)
                {
                    if ($a['index'] == $b['index'])
                        return 0;

                    if ($a['index'] > $b['index'])
                        return 1;
                    else
                        return -1;
                });

                $html = '';
                $all_checked="checked";
                foreach ($cap_list as $cap)
                {

                    $html .= '<tr style="border: 2px solid rgb(0, 103, 153); box-sizing: border-box; display: table-row;">';
                    $html .= '<td>'.$cap['icon'].'<strong>'.$cap['display'].'</strong></td>';
                    $html .= '<td><span class="screen-reader-text">Allowed</span>';
                    if ($user->has_cap($cap['slug'])) {
                        $html .= '<input type="checkbox" style="margin-left:8px;" name="wpvivid-caps" value="' . $cap['slug'] . '" checked="checked">';
                    }
                    else {
                        $html .= '<input type="checkbox" style="margin-left:8px;" name="wpvivid-caps" value="' . $cap['slug'] . '">';
                        $all_checked="";
                    }
                    $html .= '</td>';
                    $html .= '</tr>';
                }

                ob_start();
                ?>
                <p>
                    <span class="dashicons dashicons-edit"></span>
                    <strong><span> Edit Capabilities for User: </span><span style="color:#007cba;"><?php echo $user->get('user_login'); ?></span></strong>
                </p>
                <table id="wpvivid_caps_list" class="wp-list-table widefat plugins striped">
                    <thead>
                    <tr>
                        <th>Modules</th>
                        <th>
                            <input id="wpvivid_user_cap_select_all" type="checkbox" style="margin-top: 2px;" <?php echo $all_checked;?> />
                            <span>Grant</span>
                        </th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php echo $html; ?>
                    </tbody>
                </table>
                <br>
                <div user="<?php echo $user->ID; ?>">
                    <input class="button-primary" id="wpvivid_submit_cap" type="submit" value="Save Changes" />
                </div>
                <?php
                $ret['html'] = ob_get_clean();
                $ret['result'] = 'success';
                echo json_encode($ret);
            }
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function edit_role_caps()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('administrator');
        try
        {
            if(isset($_POST['role']))
            {
                $role = $_POST['role'];
                global $wp_roles;
                $role_cap=$wp_roles->get_role($role);
                $caps=$this->get_wpvivid_caps();

                $cap_list=array();
                foreach ($caps as $cap)
                {
                    $cap_list[]=$cap;
                }

                usort($cap_list, function ($a, $b)
                {
                    if ($a['index'] == $b['index'])
                        return 0;

                    if ($a['index'] > $b['index'])
                        return 1;
                    else
                        return -1;
                });

                $html = '';
                $all_checked='checked';
                foreach ($cap_list as $cap)
                {
                    $html .= '<tr style="border: 2px solid rgb(0, 103, 153); box-sizing: border-box; display: table-row;">';
                    $html .= '<td>'.$cap['icon'].'<strong>'.$cap['display'].'</strong></td>';
                    $html .= '<td><span class="screen-reader-text">Allowed</span>';
                    if ($role_cap->has_cap($cap['slug']))
                    {
                        $html .= '<input type="checkbox" style="margin-left:8px;" name="wpvivid-role-caps" value="' . $cap['slug'] . '" checked="checked">';
                    }
                    else {
                        $html .= '<input type="checkbox" style="margin-left:8px;" name="wpvivid-role-caps" value="' . $cap['slug'] . '">';
                        $all_checked='';
                    }
                    $html .= '</td>';
                    $html .= '</tr>';
                }

                ob_start();
                ?>
                <p>
                    <span class="dashicons dashicons-edit"></span>
                    <strong><span> Edit Capabilities for Role: </span><span style="color:#007cba;"><?php echo $role; ?></span></strong>
                </p>
                <table id="wpvivid_role_caps_list" class="wp-list-table widefat plugins striped">
                    <thead>
                    <tr>
                        <th>Modules</th>
                        <th>
                            <input id="wpvivid_cap_select_all" type="checkbox" style="margin-top: 2px;" <?php echo $all_checked;?> />
                            <span>Grant</span>
                        </th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php echo $html; ?>
                    </tbody>
                </table>
                <br>
                <div role="<?php echo $role; ?>">
                    <input class="button-primary" id="wpvivid_submit_role_cap" type="submit" value="Save Changes" />
                </div>
                <?php
                $ret['html'] = ob_get_clean();
                $ret['result'] = 'success';
                echo json_encode($ret);
            }
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function save_role_caps()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('administrator');
        try
        {
            $role = $_POST['role'];
            $json = stripslashes($_POST['caps']);
            $caps = json_decode($json, true);

            global $wp_roles;

            $role = $wp_roles->get_role($role);

            if (is_null($role))
            {
                $ret['result'] = 'failed';
                $ret['error'] = 'not found role';
            }
            else
            {
                $all_remove=true;

                foreach ($caps as $key => $cap)
                {
                    if ($cap)
                    {
                        $role->add_cap($key);
                        $all_remove=false;
                    } else {
                        $role->remove_cap($key);
                    }
                }

                $ret['enabled']=!$all_remove;
                $ret['result'] = 'success';
            }

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

    public function save_user_role_caps()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security('administrator');
        try
        {
            if(!isset( $_POST['user'])||empty($_POST['user']))
            {
                $ret['result']='failed';
                $ret['error']='User name is required.';
                echo json_encode($ret);
                die();
            }
            $user_name = $_POST['user'];

            $user = get_user_by('ID',$user_name);
            if($user===false)
            {
                $ret['result']='failed';
                $ret['error']='User name not found.';
                echo json_encode($ret);
                die();
            }

            $json = stripslashes($_POST['caps']);
            $caps = json_decode($json, true);

            $all_remove=true;
            foreach ($caps as $key => $cap)
            {
                if ($cap)
                {
                    $user->add_cap($key);
                    $all_remove=false;
                } else {
                    $user->remove_cap($key);
                }
            }


            $ret['result'] = 'success';
            $ret['enabled']=!$all_remove;
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
}