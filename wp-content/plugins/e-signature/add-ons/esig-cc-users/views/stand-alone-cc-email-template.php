<div style="margin:0 auto;background-color:#e4e8eb;padding:25px 10px;">
    <div style="background-color:#fff;padding:20px">

        <div style="font-size:14px;font-family:Helvetica,Arial,sans-serif;line-height:24px;color:initial;" align="left">
            <p><?php

                _e('Hi', 'esig'); ?> <?php echo esigHtml($data->user_info->first_name); ?>,
            <p>
                <?php
                echo sprintf(__('You have been copied on <b>%s</b> by %s, which is a public document sent to collect a signature.', 'esig'), $data->doc->document_title, $data->owner_email); ?></p>


            <p> <?php _e("There's nothing you need to do. We will email you the final version once the document has been signed.", "esig"); ?></p>

            <hr>

            <div style="padding:10px;">

                <?php
                $background_color_bg = apply_filters('esig-invite-button-background-color', '#0083c5', esigget('wpUserId', $data));
                $background_color = !empty($background_color_bg) ?  $background_color_bg : '#0083c5';
                ?>
                <a href="<?php echo $data->signed_link; ?>" style="width:100%;background-color:<?php echo $background_color; ?>;color:#ffffff;padding:15px 20px;font-size:14px;font-family:sans-serif;text-decoration:none;">
                    <?php _e('View Document', 'esig'); ?> </a>


            </div>

            <hr>

            <?php _e('Thanks! ', 'esig'); ?> <br>
            <?php echo $data->owner_name ?><br>
            <?php echo $data->organization_name; ?></p>


        </div>

    </div>
</div>