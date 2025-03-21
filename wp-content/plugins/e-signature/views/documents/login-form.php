<input type="hidden" id="esig-invite-hash" value="<?php echo $GLOBALS['slv_data']->invite_hash; ?>">
<input type="hidden" id="esig-document-checksum" value="<?php echo $GLOBALS['slv_data']->checksum; ?>">


<div class="esig-access-form" id="slv-login-form">


    <div class="esig-slv-row">

        <div id="password-reset">
            <h4><?php echo sprintf(__("Login to %s", "esig"), WP_E_Sig()->setting->get_company_name()); ?></h4>
        </div>
        <div class="esig-verified-logo">
            <a href="https://www.approveme.com/wordpress-audit-trail-e-signature-certificate/?utm_source=wpesignplugin&utm_medium=access-code&utm_campaign=audit-trail" target="_blank" rel="nofollow">
                <img src="<?php echo ESIGN_ASSETS_DIR_URI; ?>/images/verified-approveme-gray.svg" id="esig-verify" alt="" width="110px">
            </a>
        </div>
    </div>

    <div class="row esig-space"></div>

    <div class="esig-documentation">
        <div>
            <img src="<?php echo ESIGN_ASSETS_DIR_URI; ?>/images/doc.png" width="110px" height="100px">
        </div>
        <div id="document-section">
            <img src="<?php echo ESIGN_ASSETS_DIR_URI; ?>/images/lock.png" alt="Audit Lock" width="8" height="12" id="lock-icon" class="lock-icon-head"><b class="document-section-title"><?php _e('Protected Document', 'esig'); ?></b><br>
            <span id="document_text"><b><?= sprintf(__('Document ID #%s is a protected document.Please create a unique & secure passwords which will be required to access this document.', 'esig'), Esig_Slv_Settings::short_unique_document_id($GLOBALS['slv_data']->checksum)); ?></b></span>
        </div>
    </div>
    <div class="row esig-space"> </div>

    <!-------------------------------Login Form------------------------------------->
    <!-------------------------------Login Form------------------------------------->
    <!-------------------------------Login Form------------------------------------->
    <!-------------------------------Login Form------------------------------------->

    <div class="row esig-slv-login-form" id="esig-login-form">
        <div class="esig-slv-form-details">

            <span id="access-email" class="email-text" style=""><b><?php _e('Email Address:', 'esig'); ?></b></span>
            <input type="text" id="esig-email-address" class="input-field">
            <div id="br-field"></div>
            <div class="row esig-space"></div>

            <span class="access-text">

                <b>
                    <?php
                    if (Esig_Slv_Settings::is_access_code_used($GLOBALS['slv_data']->document_id, $GLOBALS['slv_data']->email_address)) {
                        _e('<span id="access-error-text">Enter your password:</span>', 'esig');
                    } else {
                        _e('<span id="access-error-text">Enter your Access Code:</span>', 'esig');
                    }
                    ?>
                </b>
            </span>
            <input type="password" id="esig-access-code" class="input-field" align="right">

            <div id="esig-show-error" class="esig-error-box">

            </div>

            <div class="row esig-space-login"></div>
            <div align="right" id="access_login">



                <?php
                if (Esig_Slv_Settings::is_access_code_used($GLOBALS['slv_data']->document_id, $GLOBALS['slv_data']->email_address)) :
                ?>
                    <a href="#" id="forget_access_password">
                        <?php _e('I forgot my password', 'esig'); ?>
                    </a>
                <?php endif; ?>





                <button class="access_code_login" id="esig-access-code-login"><span class="esig-icon-doorkey login"></span> <span id="login_text"><?php _e('Login', 'esig'); ?></span></button>
            </div>

        </div>
    </div>

    <!--------------------------------Password Set Form------------------------------------------->
    <!--------------------------------Password Set Form------------------------------------------->
    <!--------------------------------Password Set Form------------------------------------------->
    <!--------------------------------Password Set Form------------------------------------------->


    <div class="row esig-password-set-form" id="esig-password-set-form" style="display: none;">
        <div class="esig-slv-form-details ">
            <div class="esig-slv-create-pass"><?php _e("The document senders requires that you create a unique password to access this document in the future.  Please create a secure document password below.", "esig"); ?></div>
            <span id="access-email" class="password-text"><b><?php _e('Create a password:', 'esig'); ?></b></span>
            <input type="password" id="esig-slv-password" class="input-field"><br><br>
            <span class="password-text"><b><?php _e('<span id="access-error-textt">Confirm your password:</span>', 'esig'); ?></b></span>
            <input type="password" id="esig-slv-confirm-password" class="input-field" align="right">

            <div id="esig-set-error" class="esig-error-box"></div>
            <div class="row esig-space-login"></div>

            <div align="right" id="access_login">

                <button class="access_code_login" id="esig-slv-set-password"><span class="esig-icon-doorkey login"></span> <span class="set-login"><?php _e('Login', 'esig'); ?></span></button>

            </div>

        </div>
    </div>




</div>



<!--------------------------------Password Reset Popup------------------------------------------->
<!--------------------------------Password Reset Popup------------------------------------------->
<!--------------------------------Password Reset Popup------------------------------------------->
<!--------------------------------Password Reset Popup------------------------------------------->

<div class="esig-access-form" id="reset-password-popup" style="display:none;">
    <div class="esig-slv-row" id="reset-head">
        <div id="password-reset">
            <b><?php _e("Password Reset", "esig"); ?></b>
        </div>
        <div>
            <a class="disabled" href="https://www.approveme.com/wordpress-audit-trail-e-signature-certificate/?utm_source=wpesignplugin&utm_medium=access-code&utm_campaign=audit-trail" target="_blank" rel="nofollow">
                <img src="<?php echo ESIGN_ASSETS_DIR_URI; ?>/images/verified-approveme-gray.svg" id="verify-logo" alt="" width="90px">
            </a>
        </div>
    </div>

    <div class="row esig-space"></div>

    <div class="esig-documentation">
        <div>
            <img src="<?php echo ESIGN_ASSETS_DIR_URI; ?>/images/doc.png" width="110px" height="100px">
        </div>
        <div id="document-section">
            <img src="<?php echo ESIGN_ASSETS_DIR_URI; ?>/images/lock.png" alt="Audit Lock" width="8" height="12" id="lock-icon" class="lock-icon-head"><b class="document-section-title"><?php _e('Protected Document', 'esig'); ?></b><br>
            <span id="document_text"><b><?= sprintf(__('Document ID #%s is a protected document.Please create a unique & secure passwords which will be required to access this document.', 'esig'), Esig_Slv_Settings::short_unique_document_id($GLOBALS['slv_data']->checksum)); ?></b></span>
        </div>
    </div>

    <div class="row esig-space"></div>

    <div class="row esig-password-reset-form">
        <div class="esig-slv-form-details" id="reset-password-email">

            <span id="access-email" class="email-text"><b><?php _e('<span id="access-error-texttt">Email Address:</span>', 'esig'); ?></b></span>
            <input type="text" id="esig-slv-reset-address" class="input-field">
            <div class="row esig-space-login"></div>


            <div id="esig-confirm-error" class="esig-error-box"></div>
        </div>
        <div id="access_login">
            <a href="#" id="slv-go-back" title="Show navigation"><?php _e('&#x2190 go back', 'esig'); ?></a>
            <!------      <button id="esig-slv-reset-password"><?php _e('Resett My Password', 'esig'); ?></button> ----->
            <button id="esig-slv-reset-password"><?php _e('Reset My Password', 'esig'); ?></button>
        </div>
    </div>
</div>

<!--------------------------------Password Reset Confirmation Popup--------------------------------------
                                                                    <!--------------------------------Password Reset Confirmation Popup------------------------------------------->
<!--------------------------------Password Reset Confirmation Popup------------------------------------------->
<!--------------------------------Password Reset Confirmation Popup------------------------------------------->

<div class="esig-access-form" id="slv_reset_confirmation" style="display:none;">
    <div class="esig-slv-row">
        <div id="password-reset" size="30px">
            <h4><?php echo  sprintf(__("Login to %s", "esig"), WP_E_Sig()->setting->get_company_name()); ?></h4>
        </div>
        <a href="https://www.approveme.com/wordpress-audit-trail-e-signature-certificate/?utm_source=wpesignplugin&utm_medium=access-code&utm_campaign=audit-trail/" target="_blank" rel="nofollow">
            <img src="<?php echo ESIGN_ASSETS_DIR_URI; ?>/images/verified-approveme-gray.svg" id="esig-verify" alt="" width="110px">
        </a>
    </div>
    <div class="row esig-space-con"></div>

    <div class="esig-documentationcon">

        <div class="col-lg-12" id="document-section">

            <span id="document_textcon"><?= sprintf(__('<p>If we found the document associated with the email address, you will find an email address from us in your inbox shortly.</p>
                                                                                   <p> Unsure which email you used to create your Slack account? please contact %s .</p>', 'esig'), Esig_Slv_Settings::get_email_address($GLOBALS['slv_data']->invite_hash)); ?></span>
        </div>
    </div><br>
</div>

<!------------------------Ajax to hide footer ------------------------>
<!------------------------Ajax to hide footer ------------------------>
<!------------------------Ajax to hide footer ------------------------>
<!------------------------Ajax to hide footer ------------------------>
<script type="text/javascript">
    var j = jQuery.noConflict();
    j(document).ready(function() {
        j("#esig-footer").hide();
    });
</script>
