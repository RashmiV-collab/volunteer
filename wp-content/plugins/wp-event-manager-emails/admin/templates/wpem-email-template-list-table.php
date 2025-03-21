<div class="wp-event-emails-email-content-wrapper">	
	<div class="admin-setting-left">			     	
		<div class="white-background">
			<div class="wp-event-email-template">
				<div class="wrap">
					<h2><?php _e('Email Reminder/Templates','wp-event-manager-emails');?>
						<a href="<?php echo esc_url( add_query_arg( 'form', 'add-new' ) );?>" class="page-title-action">
							<?php _e('Add New','wp-event-manager-emails');?>
						</a>
					</h2>
				<?php $email_templates->display(); ?>
			</div>
		</div>	<!--white-background-->		       
	</div>	<!--admin-setting-left--> 
</div>