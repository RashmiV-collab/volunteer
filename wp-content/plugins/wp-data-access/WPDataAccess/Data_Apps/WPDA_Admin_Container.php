<?php

namespace WPDataAccess\Data_Apps;

use  WPDataAccess\WPDA ;
class WPDA_Admin_Container
{
    private  $dbs = '' ;
    private  $tbl = '' ;
    public function __construct( $dbs, $tbl )
    {
        if ( !current_user_can( 'manage_options' ) ) {
            throw new \Exception( __( 'ERROR: Not authorized', 'wp-data-access' ) );
        }
        $this->dbs = $dbs;
        $this->tbl = $tbl;
        wp_enqueue_media();
    }
    
    public function show()
    {
        if ( !current_user_can( 'manage_options' ) ) {
            return;
        }
        $script_url = plugin_dir_url( __DIR__ ) . '../assets/dist/main.js';
        ?>

			<div class="wpda-pp-container">
				<div
					class="pp-container"
					data-source="{ 'dbs': '<?php 
        echo  esc_attr( $this->dbs ) ;
        ?>', 'tbl': '<?php 
        echo  esc_attr( $this->tbl ) ;
        ?>' }"
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
        self::ccs();
    }
    
    public static function ccs()
    {
        // TODO - Variable --explorer-padding is defined in main.css but has no effect in WordPress.
        // Looks like main.css is not loaded from explorer shortcode, only from admin shortcode.
        ?>

			<style>
                :root {
                    --explorer-padding: 16px;
                }

                .wpda-pp-container .pp-container-explorer .MuiTreeItem-content {
                    padding: var(--explorer-padding);
                }

                .wpda-pp-container p,
				.wpda-pp-container .pp-container-explorer ul,
                .wpda-pp-container .pp-container-explorer li {
					padding: 0 !important;
                    margin: 0 !important;
				}
                .wpda-pp-container .pp-container-explorer ul[role=tree] {
					margin: 0 0 0 17px !important;
				}
                .wpda-pp-container .pp-container-explorer > ul[role=tree] {
                    margin: 0 0 0 0 !important;
                }

                .wpda-pp-container .entry-content thead th, .entry-content tr th,
                .wpda-pp-container-explorer .entry-content thead th, .entry-content tr th {
					color: inherit;
				}

                .wpda-pp-container .pp-container .dataTables_wrapper .dt-bulk-actions select,
                .wpda-pp-container .pp-container .dataTables_wrapper .dataTables_length select {
					padding: 0 24px 0 8px;
                }

                .wpda-pp-container .pp-container .dataTables_wrapper .dt-bulk-actions select option,
                .wpda-pp-container .pp-container .dataTables_wrapper .dataTables_length select option {
                    padding: 0 24px 0 8px;
                }

                .wpda-pp-container .pp-container table thead tr th.pp-actions > span,
                .wpda-pp-container .pp-container table tbody tr td.pp-actions > span,
                .wpda-pp-container .pp-container table tfoot tr th.pp-actions > span {
                    margin-top: -1px;
                }

                .wpda-pp-container .pp-form input[type=date],
                .wpda-pp-container .pp-form input[type=datetime-local],
                .wpda-pp-container .pp-form input[type=datetime],
                .wpda-pp-container .pp-form input[type=email],
                .wpda-pp-container .pp-form input[type=month],
                .wpda-pp-container .pp-form input[type=number],
                .wpda-pp-container .pp-form input[type=password],
                .wpda-pp-container .pp-form input[type=search],
                .wpda-pp-container .pp-form input[type=tel],
                .wpda-pp-container .pp-form input[type=text],
                .wpda-pp-container .pp-form input[type=time],
                .wpda-pp-container .pp-form input[type=url],
                .wpda-pp-container .pp-form input[type=week] {
                    border: 0;
					padding: 16.5px 14px;
					box-shadow: none;
                }

                #pp-setting-drawer-root .MuiPaper-root.MuiDrawer-paper input {
                    border: 0;
                    padding: 16.5px 14px;
                    box-shadow: none;
				}

                #pp-setting-drawer.MuiPaper-root.MuiDrawer-paper input[type="checkbox"].disabled,
                #pp-setting-drawer.MuiPaper-root.MuiDrawer-paper input[type="checkbox"].disabled:checked::before,
                #pp-setting-drawer.MuiPaper-root.MuiDrawer-paper input[type="checkbox"]:disabled,
                #pp-setting-drawer.MuiPaper-root.MuiDrawer-paper input[type="checkbox"]:disabled:checked::before,
                #pp-setting-drawer.MuiPaper-root.MuiDrawer-paper input[type="radio"].disabled,
                #pp-setting-drawer.MuiPaper-root.MuiDrawer-paper input[type="radio"].disabled:checked::before,
                #pp-setting-drawer.MuiPaper-root.MuiDrawer-paper input[type="radio"]:disabled,
                #pp-setting-drawer.MuiPaper-root.MuiDrawer-paper input[type="radio"]:disabled:checked::before {
					opacity: 0;
				}

                .wpda-pp-container .pp-container .pp-table table {
					margin: 0 !important;
					border: 0 !important;
				}

                .wpda-pp-container .pp-container-explorer {
					margin-bottom: 30px;
				}
			</style>

			<?php 
    }

}