<?php
/**
 * WPEM_Emails_Admin class.
 */
class WPEM_Emails_Admin {
    /**
	 * Constructor
	 */
	public function __construct() {
		include( 'wpem-email-template-table.php' );
		include( 'wpem-emails-notifications.php' );
	}
}
new WPEM_Emails_Admin();