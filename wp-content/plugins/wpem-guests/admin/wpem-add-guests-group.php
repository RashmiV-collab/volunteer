<?php
if (!defined('ABSPATH'))
{
    exit;
}

/**
 * WPEM_Add_Guests_Group class.
 */
class WPEM_Add_Guests_Group {

    /**
     * Constructor
     */
    public function __construct()
    {
        if (!empty($_POST['add_guests_group']) && !empty($_POST['wpem_guests_group_nonce']) && wp_verify_nonce($_POST['wpem_guests_group_nonce'], 'add_guests_group'))
        {
            $this->save();
        }
    }

    /**
     * Output the form
     */
    public function form()
    {
        wp_enqueue_script( 'wpem-guests-admin');

        $fields      = get_event_guests_form_fields();
        $group_id    = isset($_GET['edit']) ? $_GET['edit'] : '';

        $group = [];
        if(!empty($group_id))
        {
            $group = get_event_guests_group($group_id);    
        }

        $group_id = isset($group->id) ? $group->id : '';
        $user_id = isset($group->user_id) ? $group->user_id : '';
        $event_id = isset($group->id) ? $group->event_id : '';
        $group_name = isset($group->group_name) ? $group->group_name : '';
        $group_description = isset($group->group_description) ? $group->group_description : '';
        $group_fields = isset($group->group_fields) ? json_decode($group->group_fields, true) : [];

        $args = [
            'post_type'   => 'event_listing',
            'post_status' => 'publish',
            'posts_per_page'    => -1,
            'author'      => get_current_user_id(),
        ];

        $events = get_posts($args);
        
        ?>
        
        <p><?php _e('Create OR Update a guest list group using the form below. The guest will be emailed their tickets.', 'wp-event-manager-guests'); ?></p>
        
        <table class="form-table">
            <tr>
                <th>
                    <label for="group_name"><?php _e('Group name', 'wp-event-manager-guests'); ?></label>
                </th>
                <td>
                    <input type="text" name="group_name" id="group_name" class="input-text regular-text" required value="<?php echo $group_name; ?>" />
                </td>
            </tr>
            <tr>
                <th>
                    <label for="group_description"><?php _e('Description', 'wp-event-manager-guests'); ?></label>
                </th>
                <td>
                    <textarea name="group_description" id="group_description" class="input-text regular-text"><?php echo $group_description; ?></textarea>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="event_id"><?php _e('Event Name', 'wp-event-manager-guests'); ?></label>
                </th>
                <td>
                    <select class="regular-text" name="event_id" id="event_id">
                        <option value=""><?php _e('Select event', 'wp-event-manager-guests'); ?></option>
                        <?php foreach ( $events as $event ) : ?>
                            <option value="<?php echo $event->ID; ?>" <?php selected($event_id, $event->ID) ?>><?php echo esc_html( $event->post_title ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="group_fields"><?php _e('Fields for guest information', 'wp-event-manager-guests'); ?></label>
                </th>
                <td>
                    <select class="regular-text event-manager-select-chosen" name="group_fields[]" id="group_fields" multiple="multiple">
                        <?php foreach ( $fields as $name => $field ) : ?>
                            <option value="<?php echo esc_attr( $name ); ?>" <?php echo in_array($name, $group_fields) ? 'selected' : ''; ?>><?php echo esc_html( $field['label'] ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        </table>

        <p class="submit">
            <?php if( isset($group_id) && !empty($group_id) ) : ?>
                <input type="submit" class="button button-primary" name="add_guests_group" value="<?php _e('Update group', 'wp-event-manager-guests'); ?>" />
            <?php else : ?>
                <input type="submit" class="button button-primary" name="add_guests_group" value="<?php _e('Add group', 'wp-event-manager-guests'); ?>" />
            <?php endif; ?>
            
        </p>
        
        <?php
    }

    /**
     * Save the new group
     */
    public function save()
    {
        $group_name        = isset($_POST['group_name']) ? $_POST['group_name'] : '';
        $group_description = isset($_POST['group_description']) ? $_POST['group_description'] : '';
        $group_fields      = isset($_POST['group_fields']) ? $_POST['group_fields'] : '';
        $event_id          = isset($_POST['event_id']) ? $_POST['event_id'] : '';
        $id                = isset($_REQUEST['edit']) ? $_REQUEST['edit'] : '';

        try
        {
            // Validate
            if (empty($group_name))
            {
                throw new Exception('<div class="error"><p>' . __('Group name is a required field.', 'wp-event-manager-guests') . '</p></div>');
            }

            $data = array(
                'id'                => $id,
                'user_id'           => get_current_user_id(),
                'event_id'          => $event_id,
                'group_name'        => $group_name,
                'group_description' => $group_description,
                'group_fields'      => $group_fields,
            );

            if (WPEM_Guests_Post_Types::save_guest_lists_group($data))
            {
                echo '<div class="updated"><p>' . __( 'Your Group is saved successfully.', 'wp-event-manager-guests' ) . '</p></div>';
            }
            else
            {
                throw new Exception('<div class="error"><p>' . __('Could not create the group.', 'wp-event-manager-guests') . '</p></div>');
            }
        }
        catch (Exception $e)
        {
            echo sprintf('<div class="error"><p>%s</p></div>', $e->getMessage());
        }
    }

}
