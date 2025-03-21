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

                <?php echo $data->signed_link; ?>

            </div>

            <hr>

            <p> <span style="display:inherit;"> <?php echo __("Thanks!", "esig"); ?></span>
                <span style="display:inherit;"> <?php echo  $data->owner_name; ?> </span>
                <span> <?php echo $data->organization_name; ?> </span>
            </p>

        </div>

    </div>
</div>