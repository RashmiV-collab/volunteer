<?php

// phpcs:ignore Standard.Category.SniffName.ErrorCode
namespace WPDataAccess\Connection;

/**
 * This class extends WodPress class wpdb to enable remote connections and connections to local databases.
 */
class WPDADB_WPDB extends \wpdb
{
    /**
     * Indicator: ON = use SSL
     *
     * @var string
     */
    private  $ssl ;
    /**
     * SSL key
     *
     * @var string
     */
    private  $ssl_key ;
    /**
     * SSL certificate
     *
     * @var string
     */
    private  $ssl_cert ;
    /**
     * SSL CA
     *
     * @var string
     */
    private  $ssl_ca ;
    /**
     * SSL path
     *
     * @var string
     */
    private  $ssl_path ;
    /**
     * SSL cipher
     *
     * @var string
     */
    private  $ssl_cipher ;
    /**
     * Overwrite WPDADB_WPDB constructor
     *
     * Save schema_name
     *
     * @param string $dbuser Username (original wpdb parameter).
     * @param string $dbpassword Password (original wpdb parameter).
     * @param string $dbname Database schema name (original wpdb parameter).
     * @param string $dbhost Host name (original wpdb parameter).
     * @param string $ssl SSL on|off.
     * @param string $ssl_key SSL key.
     * @param string $ssl_cert SSL certificate.
     * @param string $ssl_ca SSL CA.
     * @param string $ssl_path SSL path.
     * @param string $ssl_cipher SSL cipher.
     */
    public function __construct(
        $dbuser,
        $dbpassword,
        $dbname,
        $dbhost,
        $ssl = 'OFF',
        $ssl_key = null,
        $ssl_cert = null,
        $ssl_ca = null,
        $ssl_path = null,
        $ssl_cipher = null
    )
    {
        $this->ssl = $ssl;
        $this->ssl_key = $ssl_key;
        $this->ssl_cert = $ssl_cert;
        $this->ssl_ca = $ssl_ca;
        $this->ssl_path = $ssl_path;
        $this->ssl_cipher = $ssl_cipher;
        if ( '' === trim( (string) $this->ssl_path ) ) {
            $this->ssl_path = null;
        }
        if ( '' === trim( (string) $this->ssl_cipher ) ) {
            $this->ssl_cipher = null;
        }
        parent::__construct(
            $dbuser,
            $dbpassword,
            $dbname,
            $dbhost
        );
    }
    
    /**
     * Is connection established?
     *
     * @return bool
     */
    public function is_connected()
    {
        return 0 === $this->dbh->connect_errno;
    }
    
    /**
     * Overwrite method
     *
     * This just adds a call to private method @ssl()
     *
     * @param bool $allow_bail Allow bail.
     *
     * @return bool
     */
    public function db_connect( $allow_bail = false )
    {
        $this->is_mysql = true;
        $client_flags = ( defined( 'MYSQL_CLIENT_FLAGS' ) ? MYSQL_CLIENT_FLAGS : 0 );
        /*
         * Set the MySQLi error reporting off because WordPress handles its own.
         * This is due to the default value change from `MYSQLI_REPORT_OFF`
         * to `MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT` in PHP 8.1.
         */
        mysqli_report( MYSQLI_REPORT_OFF );
        $this->dbh = mysqli_init();
        $host = $this->dbhost;
        $port = null;
        $socket = null;
        $is_ipv6 = false;
        $host_data = $this->parse_db_host( $this->dbhost );
        
        if ( $host_data ) {
            list( $host, $port, $socket, $is_ipv6 ) = $host_data;
            // phpcs:ignore - 8.1 proof
        }
        
        /*
         * If using the `mysqlnd` library, the IPv6 address needs to be enclosed
         * in square brackets, whereas it doesn't while using the `libmysqlclient` library.
         * @see https://bugs.php.net/bug.php?id=67563
         */
        if ( $is_ipv6 && extension_loaded( 'mysqlnd' ) ) {
            $host = "[{$host}]";
        }
        if ( 'on' === $this->ssl ) {
            mysqli_ssl_set(
                // phpcs:ignore
                $this->dbh,
                $this->ssl_key,
                $this->ssl_cert,
                $this->ssl_ca,
                $this->ssl_path,
                $this->ssl_cipher
            );
        }
        mysqli_report( MYSQLI_REPORT_STRICT );
        // phpcs:ignore
        try {
            
            if ( WP_DEBUG ) {
                
                if ( !mysqli_real_connect(
                    $this->dbh,
                    $host,
                    $this->dbuser,
                    $this->dbpassword,
                    null,
                    $port,
                    $socket,
                    $client_flags
                ) ) {
                    // phpcs:ignore
                    mysqli_report( MYSQLI_REPORT_OFF );
                    // phpcs:ignore
                    return false;
                }
            
            } else {
                // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
                
                if ( !@mysqli_real_connect(
                    $this->dbh,
                    $host,
                    $this->dbuser,
                    $this->dbpassword,
                    null,
                    $port,
                    $socket,
                    $client_flags
                ) ) {
                    // phpcs:ignore
                    mysqli_report( MYSQLI_REPORT_OFF );
                    // phpcs:ignore
                    return false;
                }
            
            }
        
        } catch ( \mysqli_sql_exception $e ) {
            mysqli_report( MYSQLI_REPORT_OFF );
            // phpcs:ignore
            return false;
        }
        mysqli_report( MYSQLI_REPORT_OFF );
        // phpcs:ignore
        
        if ( $this->dbh->connect_errno ) {
            $this->dbh = null;
            /*
             * It's possible ext/mysqli is misconfigured. Fall back to ext/mysql if:
             *  - We haven't previously connected, and
             *  - WP_USE_EXT_MYSQL isn't set to false, and
             *  - ext/mysql is loaded.
             */
            $attempt_fallback = true;
            
            if ( $this->has_connected ) {
                $attempt_fallback = false;
            } elseif ( defined( 'WP_USE_EXT_MYSQL' ) && !WP_USE_EXT_MYSQL ) {
                $attempt_fallback = false;
            } elseif ( !function_exists( 'mysql_connect' ) ) {
                $attempt_fallback = false;
            }
            
            
            if ( $attempt_fallback ) {
                $this->use_mysqli = false;
                return $this->db_connect( $allow_bail );
            }
        
        }
        
        
        if ( !$this->dbh && $allow_bail ) {
            wp_load_translations_early();
            // Load custom DB error template, if present.
            
            if ( file_exists( WP_CONTENT_DIR . '/db-error.php' ) ) {
                require_once WP_CONTENT_DIR . '/db-error.php';
                die;
            }
            
            $message = '<h1>' . __( 'Error establishing a database connection' ) . "</h1>\n";
            $message .= '<p>' . sprintf(
                /* translators: 1: wp-config.php, 2: Database host. */
                __( 'This either means that the username and password information in your %1$s file is incorrect or we can&#8217;t contact the database server at %2$s. This could mean your host&#8217;s database server is down.' ),
                '<code>wp-config.php</code>',
                '<code>' . htmlspecialchars( $this->dbhost, ENT_QUOTES ) . '</code>'
            ) . "</p>\n";
            $message .= "<ul>\n";
            $message .= '<li>' . __( 'Are you sure you have the correct username and password?' ) . "</li>\n";
            $message .= '<li>' . __( 'Are you sure you have typed the correct hostname?' ) . "</li>\n";
            $message .= '<li>' . __( 'Are you sure the database server is running?' ) . "</li>\n";
            $message .= "</ul>\n";
            $message .= '<p>' . sprintf(
                /* translators: %s: Support forums URL. */
                __( 'If you&#8217;re unsure what these terms mean you should probably contact your host. If you still need help you can always visit the <a href="%s">WordPress Support Forums</a>.' ),
                __( 'https://wordpress.org/support/forums/' )
            ) . "</p>\n";
            $this->bail( $message, 'db_connect_fail' );
            return false;
        } elseif ( $this->dbh ) {
            if ( !$this->has_connected ) {
                $this->init_charset();
            }
            $this->has_connected = true;
            $this->set_charset( $this->dbh );
            $this->ready = true;
            $this->set_sql_mode();
            $this->select( $this->dbname, $this->dbh );
            return true;
        }
        
        return false;
    }
    
    /**
     * Get connection error no
     *
     * @return mixed
     */
    public function get_connect_errno()
    {
        return $this->dbh->connect_errno;
    }
    
    /**
     * Get connection error message
     *
     * @return mixed
     */
    public function get_connect_error()
    {
        return $this->dbh->connect_error;
    }
    
    public function wpda_open_cursor( $query )
    {
        if ( defined( 'SAVEQUERIES' ) && SAVEQUERIES ) {
            $this->timer_start();
        }
        
        if ( !empty($this->dbh) && $this->use_mysqli ) {
            $this->result = mysqli_query( $this->dbh, $query );
        } elseif ( !empty($this->dbh) ) {
            $this->result = mysql_query( $query, $this->dbh );
        }
        
        $this->num_queries++;
        if ( defined( 'SAVEQUERIES' ) && SAVEQUERIES ) {
            $this->log_query(
                $query,
                $this->timer_stop(),
                $this->get_caller(),
                $this->time_start,
                array()
            );
        }
    }
    
    public function wpda_fetch_from_cursor()
    {
        
        if ( $this->use_mysqli && 'mysqli_result' === get_class( $this->result ) ) {
            return mysqli_fetch_object( $this->result );
        } elseif ( is_resource( $this->result ) ) {
            return mysql_fetch_object( $this->result );
        }
        
        return null;
    }

}