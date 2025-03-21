<?php
/**
 * Event Zoom Form
 */
if ( ! defined( 'ABSPATH' ) ) exit; ?>

<?php do_action('event_manager_guests_guest_start'); ?>

<?php echo !empty($guest_lists_dashboard_message) ? $guest_lists_dashboard_message : ''; ?>

<div id="event_manager_guests" class="event-manager-guest-lists">
    
    <div class="wpem-main wpem-event-guest-lists-wrapper">

        <div class="wpem-event-guest-lists-body">

            <form class="wpem-event-guest-lists wpem-form-wrapper" method="POST">

                <div class="wpem-event-guest-lists-header">
                    <h2 class="wpem-form-title wpem-heading-text"><?php _e( 'Add Guest', 'wp-event-manager-guests' ); ?></h2> 
                </div>
        
                <div class="wpem-event-guest-lists-field">

                    <?php do_action('event_manager_guests_guest_form_fields_start'); ?>

                    <fieldset class="wpem-form-group">
                        <label for="event_id"><?php _e( 'Select event', 'wp-event-manager-guests' ); ?><span class="require-field">*</span></label>
                        <select name="event_id" id="event_id" required>
                            <option value=""><?php _e( 'Select event', 'wp-event-manager-guests' ); ?></option>
                            <?php foreach ( $events as $event ) : ?>
                                <option value="<?php echo esc_attr( $event->ID ); ?>" <?php selected( $event_id, $event->ID ); ?>><?php echo esc_html( $event->post_title ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </fieldset>

                    <fieldset class="wpem-form-group">
                        <label for="group_id"><?php _e( 'Select Group', 'wp-event-manager-guests' ); ?><span class="require-field">*</span></label>
                        <div class="field">
                            <select name="guest_lists_group" id="group_id" required>
                                <option value=""><?php _e( 'Select Group', 'wp-event-manager-guests' ); ?></option>
                                <?php if ( !empty($groups) ) : ?>
                                    <?php foreach( $groups as $my_group ) : ?>
                                        <option value="<?php echo esc_attr( $my_group->id ); ?>" <?php selected( $group_id, $my_group->id ); ?>><?php echo esc_html( $my_group->group_name ); ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </fieldset>

                    <?php foreach( $fields as $name => $field ) : ?>
                        <?php if ( !empty($group_fields) ) : ?>
                            <?php if ( in_array($name, $group_fields) ) : ?>
                                <fieldset class="wpem-form-group fieldset-<?php echo esc_attr( $name ); ?>">
                                    <label for="<?php esc_attr_e( $name ); ?>"><?php echo $field['label'] . apply_filters( 'submit_event_form_required_label', $field['required'] ? '<span class="require-field">*</span>' : ' <small>' . __( '(optional)', 'wp-event-manager-guests' ) . '</small>', $field ); ?></label>
                                    <div class="field <?php echo $field['required'] ? 'required-field' : ''; ?>">
                                        <?php get_event_manager_template( 'form-fields/' . $field['type'] . '-field.php', array( 'key' => $name, 'field' => $field ) ); ?>
                                    </div>
                                </fieldset>
                            <?php endif; ?>
                        <?php else : ?>
                            <fieldset class="wpem-form-group fieldset-<?php echo esc_attr( $name ); ?>">
                                <label for="<?php esc_attr_e( $name ); ?>"><?php echo $field['label'] . apply_filters( 'submit_event_form_required_label', $field['required'] ? '<span class="require-field">*</span>' : ' <small>' . __( '(optional)', 'wp-event-manager-guests' ) . '</small>', $field ); ?></label>
                                <div class="field <?php echo $field['required'] ? 'required-field' : ''; ?>">
                                    <?php get_event_manager_template( 'form-fields/' . $field['type'] . '-field.php', array( 'key' => $name, 'field' => $field ) ); ?>
                                </div>
                            </fieldset>
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <?php do_action('event_manager_guests_guest_form_fields_end'); ?>

                </div>

                <div class="wpem-form-footer">
                   <!--  <input type="hidden" name="action" value="show_guest_lists" /> -->
                    <!-- <input type="hidden" name="user_id" value="<?php echo $user_id; ?>" /> -->

                    <button type="submit" class="wpem-theme-button" onclick="wp_event_manager_add_guest" name="wp_event_manager_add_guest" value="<?php esc_attr_e( 'Save Guest', 'wp-event-manager-guests' ); ?>"><?php _e('Save Guest','wp-event-manager-guests');?></button>

                    <?php wp_nonce_field( 'event_manager_add_guest' ); ?>
                </div>

            </form>
        </div>

    </div>

</div>

<?php do_action('event_manager_guests_guest_end'); ?>
