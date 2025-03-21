<?php
/**
 * Event Zoom Form
 */
if ( ! defined( 'ABSPATH' ) ) exit; ?>

<?php
$group_id = isset($group->id) ? $group->id : '';
$event_id = isset($group->event_id) ? $group->event_id : '';
$group_name = isset($group->group_name) ? $group->group_name : '';
$group_description = isset($group->group_description) ? $group->group_description : '';
$group_fields = isset($group->group_fields) ? json_decode($group->group_fields, true) : [];
?>

<?php do_action('event_manager_guests_group_start'); ?>

<?php echo !empty($group_dashboard_message) ? $group_dashboard_message : ''; ?>

<div id="event_manager_guests" class="event-manager-guest-lists">
    
    <div class="wpem-main wpem-event-guest-lists-wrapper">
        
        <div class="wpem-event-guest-lists-body">

            <form class="wpem-event-guest-lists wpem-form-wrapper" method="POST">
                <div class="wpem-event-guest-lists-header">
                    <h2 class="wpem-form-title wpem-heading-text"><?php _e( 'Update Group', 'wp-event-manager-guests' ); ?></h2> 
                </div>

                <div class="wpem-event-guest-lists-field">
                    <div class="wpem-row">

                        <?php do_action('event_manager_guests_group_form_fields_start'); ?>

                        <div class="wpem-col-md-12">
                            <div class="wpem-form-group">
                                <label for="group_name"><?php _e( 'Enter group name', 'wp-event-manager-guests' ); ?><span class="require-field">*</span></label>
                                <input type="text" name="group_name" id="group_name" required placeholder="<?php _e( 'Group name', 'wp-event-manager-guests' ); ?>" value="<?php echo $group_name; ?>" />
                                <small class="description"></small>
                            </div>                            
                        </div>

                        <div class="wpem-col-md-12">
                            <div class="wpem-form-group">
                                <label for="group_description"><?php _e( 'Enter group description', 'wp-event-manager-guests' ); ?></label>
                                <input type="text" name="group_description" id="group_description" placeholder="<?php _e( 'Group description', 'wp-event-manager-guests' ); ?>" value="<?php echo $group_description; ?>" />
                                <small class="description"></small>
                            </div>                            
                        </div>

                        <div class="wpem-col-md-12">
                            <div class="wpem-form-group">
                                <label for="event_id"><?php _e( 'Select event', 'wp-event-manager-guests' ); ?><span class="require-field">*</span></label>
                                <select name="event_id" id="event_id" required>
                                    <option value=""><?php echo _e( 'Select event', 'wp-event-manager-guests' ); ?></option>
                                    <?php foreach ( $events as $event ) : ?>
                                        <option value="<?php echo esc_attr( $event->ID ); ?>" <?php selected( $event_id, $event->ID ); ?>><?php echo esc_html( $event->post_title ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="description"></small>
                            </div>                            
                        </div>

                        <div class="wpem-col-md-12">
                            <div class="wpem-form-group">
                                <label for="group_fields"><?php _e( 'Select fields', 'wp-event-manager-guests' ); ?><span class="require-field">*</span></label>
                                <select name="group_fields[]" id="group_fields" class="event-manager-select-chosen" multiple="multiple" required>
                                	<?php foreach ( get_event_guests_form_fields() as $name => $field ) : ?>
                                		<option value="<?php echo esc_attr( $name ); ?>" <?php echo in_array($name, $group_fields) ? 'selected' : ''; ?> ><?php echo esc_html( $field['label'] ); ?></option>
                                	<?php endforeach; ?>
                                </select>
                                <small class="description"></small>
                            </div>                            
                        </div>
                        <div class="wpem-col-md-12">
                            <?php do_action('event_manager_guests_group_form_fields_end'); ?>
                        </div>
                        <div class="wpem-col-md-4">
                            <div class="wpem-form-group">

                                <input type="hidden" name="action" value="show_groups" />
                                
                                <button type="submit" class="wpem-theme-button" name="wp_event_manager_add_group" value="<?php esc_attr_e( 'Update Group', 'wp-event-manager-guests' ); ?>"><?php _e('Update Group','wp-event-manager-guests');?></button>

                                <?php wp_nonce_field( 'event_manager_add_group' ); ?>
                                
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

    </div>

</div>

<?php do_action('event_manager_guests_group_end'); ?>
