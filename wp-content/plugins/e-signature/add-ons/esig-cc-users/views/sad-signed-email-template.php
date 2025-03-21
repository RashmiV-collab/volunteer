<div style="margin:0 auto;background-color:#e4e8eb;padding:25px 10px;">
    <div style="background-color:#fff;padding:20px">

        <div style="font-size:14px;font-family:Helvetica,Arial,sans-serif;line-height:24px;color:initial;" align="left">

            <p><?php _e('Hi', 'esig'); ?> <?php echo esigHtml($data->user_info->first_name); ?>,</p>

            <p><?php echo sprintf(__("<b> %s </b> - which was requested by %s has just been signed by %s (%s). <br><br> "
                    . "You were CC'd on this document, so you can access the signed PDF below.", "esig"), $data->doc->document_title, $data->owner_email, $data->signers->signer_name, $data->signers->signer_email); ?><br>

                <hr />

            <div style="padding:10px;">

                <?php
                $background_color_bg = apply_filters('esig-invite-button-background-color', '#0083c5', esigget('wpUserId', $data));
                $background_color = !empty($background_color_bg) ?  $background_color_bg : '#0083c5';
                ?>
                <a href="<?php echo $data->signed_link; ?>" style="width:100%;background-color:<?php echo $background_color; ?>;color:#ffffff;padding:15px 20px;font-size:14px;font-family:sans-serif;text-decoration:none;padding-left: 3%;padding-right: 3%;">
                    <?php _e('View Signed Document', 'esig'); ?> </a>


            </div>

            <hr />
            <p> <span style="display:inherit;"> <?php echo __("Thanks!", "esig"); ?></span>
                <span style="display:inherit;"> <?php echo  $data->owner_name; ?> </span>
                <span> <?php echo $data->organization_name; ?> </span>
            </p>
        </div>

    </div>
</div>