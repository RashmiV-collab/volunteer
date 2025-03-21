<?php

class esigSifSetting {

    protected static $instance = null;

    /**
     * Returns an instance of this class.
     *
     * @since     0.1
     * @return    object    A single instance of this class.
     */
    public static function instance() {

        // If the single instance hasn't been set, set it now.
        if (null == self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }
    
    
    private function htaccess_exists(){
        
          $upload_path = $this->uploadDir();
	  return file_exists( $upload_path . '/.htaccess' );
        
    }
    
    private function htaccess_rules(){
        
                       $allowed_filetypes = apply_filters( 'esig_protected_directory_allowed_filetypes', array( 'jpg', 'jpeg', 'png', 'pdf' ) );
			$rules = "Options -Indexes\n";
			$rules .= "deny from all\n";
                        
                        if(!empty($allowed_filetypes)){
			$rules .= "<FilesMatch '\.(" . implode( '|', $allowed_filetypes ) . ")$'>\n";
			    $rules .= "Order Allow,Deny\n";
			    $rules .= "Allow from all\n";
			$rules .= "</FilesMatch>\n";
                        }
                       return $rules;              
    }
    
    public function checkProtection(){
        
             
                $uploadDir = $this->uploadDir();
               // Top level .htaccess file
		$rules = $this->htaccess_rules();
		if ( $this->htaccess_exists() ) {
			$contents = @file_get_contents( $uploadDir . '/.htaccess' );
			if ( $contents !== $rules || ! $contents ) {
				// Update the .htaccess rules if they don't match
				@file_put_contents( $uploadDir . '/.htaccess', $rules );
			}
		} elseif( wp_is_writable( $uploadDir ) ) {
			// Create the file if it doesn't exist
			@file_put_contents( $uploadDir . '/.htaccess', $rules );
		}

		// Top level blank index.php
		if ( ! file_exists( $uploadDir . '/index.php' ) && wp_is_writable( $uploadDir ) ) {
			@file_put_contents( $uploadDir . '/index.php', '<?php' . PHP_EOL . '// Silence is golden.' );
		}  
        
    }

    public function getDirPermission($path)
    {
        return substr(sprintf('%o', fileperms($path)), -4);
    }

    public function uploadDir() {
        
        $upload_dir_list = wp_upload_dir();

        $upload_dir = $upload_dir_list['basedir'] . '/esign';

        $upload_path = apply_filters("esig_file_upload_path", $upload_dir);

        if(!file_exists($upload_path))
        { 
            wp_mkdir_p($upload_path);
            // check for directory permission and set to 0700
            $directoryPermission = $this->getDirPermission($upload_path);
            if ($directoryPermission != 0700) {
                @chmod($upload_path, 0700);
            }
        }
    
        return $upload_path;
    }
    
    public function downloadLink($signatureHash,$url,$name){
        
         $downloadName = $this->getDownloadName($url);
         $esigToken = base64_encode(sprintf('%s:%s:%s',$signatureHash, $downloadName,$name));
         $downloadUrl = trailingslashit(WP_E_Sig()->setting->default_link()) . '?esig_action=ame_download_file&token=' . $esigToken;
         return $downloadUrl; 
    }

    public function getDownloadName($url)
    {
        $path = explode("/", $url);
        $fileName = end($path);
        $downloadName = substr($fileName, 0, (strrpos($fileName, ".")));
        return wp_hash($downloadName);
    }

    public function recordEvent($userId, $docId, $uploadUrl) {

        $docType = WP_E_Sig()->document->getDocumenttype($docId);
        $eventText = $fileSize = $fileCreateTime = '';
        $fileName = basename($uploadUrl);
        $path = $this->uploadDir() . $fileName;
        if ($fileName && file_exists($this->uploadDir() . $fileName)) {
            $fileSize = $this->formatSizeUnits(filesize($path));
            $fileCreateTime = date(get_option('date_format') . " " . get_option('time_format'), filemtime($path));
        }
        if ($docType == 'stand_alone') {
            $signer = WP_E_Sig()->user->getUserdetails($userId, $docId);
            $emailAddress = sanitize_email(ESIG_POST('esig-sad-email'));
            if (is_email($emailAddress) && $emailAddress == $signer->user_email) {
                $eventText = sprintf(__("%s : Uploaded %s %s %s", 'esig'), $signer->first_name, $fileName, $fileSize, $fileCreateTime);
            }
        } elseif ($docType == 'normal') {
            $signer = WP_E_Sig()->user->getUserdetails($userId, $docId);
            $inviteHash = sanitize_text_field(ESIG_POST('invite_hash'));
            $invite = WP_E_Sig()->invite->get_Invite_Hash($signer->user_id, $docId);
            if ($invite == $inviteHash) {
                $eventText = sprintf(__("%s : Uploaded %s %s %s", 'esig'), $signer->first_name, $fileName, $fileSize, $fileCreateTime);
            }
        }

        WP_E_Sig()->document->recordEvent($docId, 'sif_upload', $eventText, $date = null, esig_get_ip());
    }

    private function createFormat($date) {

        $dateConvert = date_create($date);
       
        if ($dateConvert) {
            $stringToDate= date_format($dateConvert, "Y-m-d");
            return $stringToDate;
        }

        $dateConvert = DateTime::createFromFormat('d/m/Y', $date);
        if ($dateConvert) {
            
             return $dateConvert->format('Y-m-d');
        }
        
        $dateConvert = DateTime::createFromFormat('m/d/Y', $date);
        if ($dateConvert) {
            return $dateConvert->format('Y-m-d');
        }

        return date("Y-m-d", strtotime($date));
    }

    private function formatSizeUnits($bytes) {
        if ($bytes >= 1073741824) {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, 2) . ' kB';
        } elseif ($bytes > 1) {
            $bytes = $bytes . ' bytes';
        } elseif ($bytes == 1) {
            $bytes = $bytes . ' byte';
        } else {
            $bytes = '0 bytes';
        }

        return $bytes;
    }

    private function getMinDate($startDate) {

        if (empty($startDate) || $startDate == "undefined") {
            return false;
        }

        $currentDate = new DateTime(date("Y-m-d"));

        $datetime = $this->createFormat($startDate);

        $minDate = new DateTime($datetime);

        $interval = date_diff($currentDate, $minDate);
//echo $interval->y;
        return $interval->format('%R%yY%R%mM%R%dD');
    }

    private function getMaxDate($endDate) {

        if (empty($endDate) || $endDate == "undefined") {
            return false;
        }

        $currentDate = new DateTime(date("Y-m-d"));


        $newDateString = $this->createFormat($endDate);

        $maxDate = new DateTime($newDateString);

        $interval = date_diff($currentDate, $maxDate);

        return $interval->format('%R%yY%R%mM%R%dD');
    }

    public function getDateRange($startDate, $endDate) {
        $minDateQuery = $this->getMinDate($startDate);
        $maxDateQuery = $this->getMaxDate($endDate);
        $retText = '';
        if (!$minDateQuery) {
            //$retText = '{ minDate:"0", maxDate: "0" }';
            $minDate = "0";
        } else {
            $minDate = $minDateQuery;
        }

        if (!$maxDateQuery) {
            $retText = '{ minDate:"' . $minDate . '"}';
        } else {
            $retText = '{ minDate:"' . $minDate . '", maxDate: "' . $maxDateQuery . '" }';
        }

        return $retText;
    }

    /**
     * return a unique file name 
     * @since 1.5.6.9
     * @return string 
     */
    public function uniqueFileName()
    {
            $sifName = bin2hex(random_bytes(7));
            return uniqid($sifName);
    }

    /**
     * return a unique file name with sanitaization
     * @since 1.5.6.9
     * @return string 
     */

    public function generateUniqueFileName($fileExt, $up_path)
    {
    
        // Generate a file name first then check if that file name already exists. If it does, loop through this routine until we find a unique file name
        do {
            $file_name = sanitize_file_name( $this->uniqueFileName() . '.' . $fileExt);
            
        } while ( file_exists($up_path . "/" . $file_name) );

        // return filename, confirmed to be unique
        return $file_name;
    }

    /**
     * This method returns date of selected timezone settings 
     * @since 1.5.7.0
     * @return datetime 
     */

    public function getSignedDate($dateFormat)
    {
        // get timezone settings from e-signature settings  
        $timezone  = WP_E_Sig()->setting->get_generic("esig_timezone_string");
        // get date time format settings from wordpress optinos 
        // get new object for php timezone from timezone string settings 
        $siteTimezone = new DateTimeZone($timezone);
        // get new current date time with given date format with selected timezoe
        $dateTime = new DateTime("now",$siteTimezone);
        // return date time. 
        return $dateTime->format($dateFormat);
    } 

    /**
     * Get max file size for upload 
     * @param string $size 
     * @return string 
     */
    public function getMaxFileSize($size){
        // get max upload size from php.ini
        $max_upload = ini_get('upload_max_filesize');

        $limit = trim($max_upload);
        $unit = strtoupper(substr($limit, -1)); // Get the last character (unit)
        $numeric_limit = (int) substr($limit, 0, -1); // Get the numeric part
        
        switch ($unit) {
            case 'T':
                return $size * pow(1024, 4); // Terabytes
            case 'G':
                return $size * pow(1024, 3); // Gigabytes
            case 'M':
                return $size * pow(1024, 2); // Megabytes
            case 'MB':
                return $size * pow(1024, 2); // Megabytes
            case 'K':
                return $size * 1024; // Kilobytes
            default:
                return $size; // Bytes (no unit specified)
        }

        return $numeric_limit * pow(1024, 2); // Megabytes

    }
    
}
