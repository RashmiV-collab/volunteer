<div class="wpem-registration-info-block notice">   
    <div class="wpem-regi-main-info-block">

        <?php do_action('event_registration_dashboard_registration_status_overview_detail_before');?>
        <div class=" wpem-regi-block-wrapper">
            <div class="wpem-regi-block-wrap">
                <div class="wpem-regi-info-blocks">
                    <div class="wpem-regi-info-block-icon">
                        <i class="wpem-icon-users wpem-regi-icon-t-regi"></i>
                    </div>
                    <div class="wpem-regi-info-block-info">
                        <div class="wpem-regi-info-block-title"><?php _e($total_registrations, 'wp-event-manager-registrations');  ?></div>
                        <div class="wpem-regi-info-block-desc"><?php _e("Total Registration", 'wp-event-manager-registrations');?></div>
                    </div>
                </div>
                <div class="wpem-regi-info-blocks">
                    <div class="wpem-regi-info-block-icon">
                        <i class="wpem-icon-user-plus wpem-regi-icon-new"></i>
                    </div>
                    <div class="wpem-regi-info-block-info">
                        <div class="wpem-regi-info-block-title"><?php _e($total_new_registrations, 'wp-event-manager-registrations');  ?></div>
                        <div class="wpem-regi-info-block-desc"><?php _e("New", 'wp-event-manager-registrations');?></div>
                    </div>
                </div>
                <!--<div class="wpem-regi-info-blocks">
                    <div class="wpem-regi-info-block-icon">
                        <i class="wpem-icon-user-check wpem-regi-icon-confirm"></i>
                    </div>
                    <div class="wpem-regi-info-block-info">
                        <div class="wpem-regi-info-block-title"><?php _e($total_confirm_registrations, 'wp-event-manager-registrations');  ?></div>
                        <div class="wpem-regi-info-block-desc"><?php _e("Confirm", 'wp-event-manager-registrations');?></div>
                    </div>
                </div> 
                <div class="wpem-regi-info-blocks">
                    <div class="wpem-regi-info-block-icon">
                        <i class="wpem-icon-hour-glass wpem-regi-icon-waiting"></i>
                    </div>
                    <div class="wpem-regi-info-block-info">
                        <div class="wpem-regi-info-block-title"><?php _e($total_waiting_registrations, 'wp-event-manager-registrations');  ?></div>
                        <div class="wpem-regi-info-block-desc"><?php _e("Waiting", 'wp-event-manager-registrations');?></div>
                    </div>
                </div>-->
                <div class="wpem-regi-info-blocks">
                    <div class="wpem-regi-info-block-icon">
                        <i class="wpem-icon-user-minus wpem-regi-icon-cancelled"></i>
                    </div>
                    <div class="wpem-regi-info-block-info">
                        <div class="wpem-regi-info-block-title"><?php _e($total_cancelled_registrations, 'wp-event-manager-registrations');  ?></div>
                        <div class="wpem-regi-info-block-desc"><?php _e("Cancelled", 'wp-event-manager-registrations');?></div>
                    </div>
                </div>
                <!--<div class="wpem-regi-info-blocks">
                    <div class="wpem-regi-info-block-icon">
                        <i class="wpem-icon-box-add wpem-regi-icon-archived"></i>
                    </div>
                    <div class="wpem-regi-info-block-info">
                        <div class="wpem-regi-info-block-title "><?php _e($total_archived_registrations, 'wp-event-manager-registrations');  ?></div>
                        <div class="wpem-regi-info-block-desc"><?php _e("Archived", 'wp-event-manager-registrations');?></div>
                    </div>
                </div>-->

                <?php if(isset($_REQUEST['event_id']) && !empty($_REQUEST['event_id'])) : ?>
                    <div class="wpem-regi-info-blocks">
                        <div class="wpem-regi-info-block-icon">
                            <i class="wpem-icon-checkbox-checked wpem-regi-icon-checkin"></i>
                        </div>
                        <div class="wpem-regi-info-block-info">
                            <div class="wpem-regi-info-block-title "><?php _e(get_total_checkedin_by_event_id(), 'wp-event-manager-registrations');  ?></div>
                            <div class="wpem-regi-info-block-desc"><?php _e("Total Checkin", 'wp-event-manager-registrations');?></div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php do_action('event_registration_dashboard_registration_status_overview_detail_after');?>
    </div>
</div>