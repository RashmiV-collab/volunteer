<?php
    $edit_id = isset($_GET['edit']) ? absint($_GET['edit']) : false;
    $email_template = array();
    if($edit_id){
        $email_template = $wpdb->get_results("SELECT * FROM $table_name WHERE `id`= $edit_id LIMIT 1", ARRAY_A);
        $email_template = isset($email_template[0]) ? $email_template[0] : $email_template;
    }
?>

<div class="wp-event-emails-email-content-wrapper">	
    <div class="admin-setting-left">			     	
        <div class="white-background">
            <div class="wp-event-email-template wp-event-add-email-template">
                <div class="wrap">
                    <h2><?php _e('Add new template','wp-event-manager-emails');?></h2>
                
                    <div class="wp_event_manager_meta_data">
                        <p class="form-field">
                            <label><b><?php _e('Template Name','wp-event-manager-emails');?></b></label>
                            <input type="text" name="wpem-email-template-name" value="<?php echo isset($email_template['name']) ? $email_template['name'] : '';?>" placeholder="Template name">
                        </p>
                        <p class="form-field">
                            <label><b><?php _e('Post Type','wp-event-manager-emails');?>Post Type</b></label>
                            <select name="wpem-email-template-type">
                                <?php if(isset($post_types)){
                                    foreach($post_types as $key=>$post_type){ ?>
                                        <option value="<?php echo $key;?>" <?php echo (isset($email_template['type']) && $email_template['type'] == $key ) ? 'selected="selected"' : '';?>> <?php echo $post_type;?></option>
                                        <?php
                                    }
                                } ?>
                            </select>
                        </p>
                        <p class="form-field">
                            <label><b><?php _e('Status before','wp-event-manager-emails');?></b></label>
                            <select name="wpem-email-template-status-before">
                                <?php if(isset($post_types)){
                                    foreach($statuses as $group_key=>$status){ ?>
                                        <optgroup label="<?=$group_key;?>" id="<?=$group_key;?>">
                                        <?php
                                        foreach($status as $key=> $value){ ?>
                                            <option value="<?php echo $key;?>" <?php echo (isset($email_template['status_before']) && $email_template['status_before'] == $key ) ? 'selected="selected"' : '';?>><?php echo $value;?></option>
                                        <?php } ?>
                                        </optgroup>
                                        <?php
                                    }
                                } ?>
                            </select>
                        </p>
                        <p class="form-field">
                            <label><b><?php _e('Status after','wp-event-manager-emails');?></b></label>
                            <select name="wpem-email-template-status-after">
                                <?php if(isset($post_types)){
                                    foreach($statuses as $group_key=>$status){?>
                                        <optgroup label="<?=$group_key;?>" id="<?=$group_key;?>">
                                            <?php foreach($status as $key=> $value){ ?>
                                                <option value="<?php echo $key;?>" <?php echo ( isset($email_template['status_after']) && $email_template['status_after'] == $key ) ? 'selected="selected"' : '';?>><?php echo $value;?></option>
                                            <?php } ?>
                                        </optgroup>
                                    <?php }
                                } ?>
                            </select>
                        </p>
                        <p class="form-field form-feild-w-100">
                            <label><b><?php _e('Email Subject','wp-event-manager-emails');?></b></label>
                            <input type="text" name="wpem-email-template-subject" value="<?php echo isset($email_template['subject']) ? $email_template['subject'] : '';?>" placeholder="Subject"></p>
                        <p class="form-field form-feild-w-100">
                            <label><b><?php _e('Email Content','wp-event-manager-emails');?></b></label>
                            <textarea name="wpem-email-template-email-content" cols="71" rows="5"><?php echo isset($email_template['body']) ? $email_template['body'] : '';?></textarea>
                        </p> 
                        <p class="form-field">
                            <label><b><?php _e('To','wp-event-manager-emails');?></b></label>
                            <input type="text" name="wpem-email-template-to" value="<?php echo isset($email_template['to']) ? $email_template['to'] : '';?>" placeholder="To">
                        </p>
                        <p class="form-field">
                            <label><b><?php _e('CC','wp-event-manager-emails');?></b></label>
                            <input type="text" name="wpem-email-template-cc" value="<?php echo isset($email_template['cc']) ? $email_template['cc'] : '';?>" placeholder="CC">
                        </p>   
                        <p class="form-field">
                            <label><b><?php _e('From','wp-event-manager-emails');?></b></label>
                            <input type="text" name="wpem-email-template-from" value="<?php echo isset($email_template['from']) ? $email_template['from'] : '';?>" placeholder="from">
                        </p>
                        <p class="form-field">
                            <label><b><?php _e('Reply to','wp-event-manager-emails');?></b></label>
                            <input type="text" name="wpem-email-template-replyto" value="<?php echo isset($email_template['reply_to']) ? $email_template['reply_to'] : '';?>" placeholder="Reply to">
                        </p>
                        <p class="form-field">
                            <label><b><?php _e('Active','wp-event-manager-emails');?></b></label>
                            <select name="wpem-email-template-active">
                                <option value="1" <?php echo (isset( $email_template['active'] ) && $email_template['active'] == true) ? 'selected="selected"' : '';?>>Active</option>
                                <option value="0" <?php echo ($email_template['active'] == false) ? 'selected="selected"' : '';?>>Deactive</option>
                            </select>
                        </p>
                        <?php if($edit_id){ ?> 
                            <input type="hidden" name="wpem-email-template-edit" value="<?php echo $edit_id;?>"/>
                        <?php 
                        } ?>
                        
                    </div>
                    <p><input type="submit" class="button action button-primary" name="wpem-email-template-submit" /></p>
                </div>
            </div>
        </div>	<!--white-background-->		       
    </div>	<!--admin-setting-left--> 
</div>
<script type="text/javascript">
    jQuery( document ).ready(function() {
        jQuery('select[name="wpem-email-template-type"]').on('change',function(e){ 
            jQuery('select[name="wpem-email-template-status-before"] optgroup').prop('disabled',true);
            jQuery('select[name="wpem-email-template-status-after"] optgroup').prop('disabled',true);

            jQuery('select[name="wpem-email-template-status-before"] optgroup#'+this.value).prop('optgroup',false);
            jQuery('select[name="wpem-email-template-status-after"] optgroup#'+this.value).prop('optgroup',false);
        });
    });
</script>