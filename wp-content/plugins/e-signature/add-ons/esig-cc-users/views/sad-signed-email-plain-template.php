<div style="margin:0 auto;background-color:#e4e8eb;padding:25px 10px;">
    <div style="background-color:#fff;padding:20px">

        <div style="font-size:14px;font-family:Helvetica,Arial,sans-serif;line-height:24px;color:initial;" align="left">

            <p><?php echo sprintf(__("<b> %s </b> - which was requested by %s has just been signed by %s (%s). <br><br> "
                    . "You were CC'd on this document, so you can access the signed PDF below.", "esig"), $data->doc->document_title, $data->owner_email, $data->signers->signer_name, $data->signers->signer_email); ?><br>

                <hr />

            <div style="padding:10px;">
            
                <?php echo $data->signed_link; ?>


            </div>

            <hr />
            <p> <span style="display:inherit;"> <?php echo __("Thanks!", "esig"); ?></span>
                <span style="display:inherit;"> <?php echo  $data->owner_name; ?> </span>
                <span> <?php echo $data->organization_name; ?> </span>
            </p>
        </div>

    </div>
</div>