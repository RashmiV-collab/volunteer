<?php

namespace WPDataAccess\Data_Apps;

use  WPDataAccess\WPDA ;
class WPDA_Explorer_Container
{
    public function __construct()
    {
        if ( !current_user_can( 'manage_options' ) ) {
            throw new \Exception( __( 'ERROR: Not authorized', 'wp-data-access' ) );
        }
        wp_enqueue_media();
    }
    
    public function show()
    {
        $script_url = plugin_dir_url( __DIR__ ) . '../assets/dist/main.js';
        ?>

			<div class="wpda-pp-container">
				<div
					class="pp-container-explorer"
				></div>
			</div>
			<script>
				window.PP_APP_CONFIG = {
					appDebug: <?php 
        echo  ( 'on' === WPDA::get_option( WPDA::OPTION_PLUGIN_DEBUG ) ? 'true' : 'false' ) ;
        ?>
				}
			</script>
			<script type="module" src="<?php 
        echo  esc_attr( $script_url ) ;
        ?>"></script>

			<?php 
        WPDA_Admin_Container::ccs();
    }

}