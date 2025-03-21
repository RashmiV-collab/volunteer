<div id="esig-update-popup" style="display:none;">


	<div class="esig-dialog-header">
		<div class="esig-alert">
			<span class="esig-icon-esig-alert"></span>
		</div>
		<h3><?php _e('This update has failed.', 'esig'); ?></h3>

		<p class="esig-updater-text"><?php

										$esig_user = new WP_E_User();

										$wpid = get_current_user_id();

										$users = $esig_user->getUserByWPID($wpid);
										echo $users->first_name . ",";

										?>


			<?php _e('It looks like your site is having an issue installing your add-ons from the ApproveMe.com server. Sorry about that! </br>

You can try this update again and if you receive this same message, use the button below to contact our support team so we can get it solved together. Thanks! ', 'esig'); ?> 👋 </p>
	</div>


	<!-- esig updater button section -->
	<div class="esig-updater-button">
		<span> <a href="https://wpe.approveme.com/article/171-still-need-help" class="button" id="esig-primary-dgr-btn"> <?php _e('EMAIL SUPPORT NOW', 'esig'); ?> </a></span>
	</div>

</div>