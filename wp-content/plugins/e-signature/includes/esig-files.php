<?php

if(!class_exists("esigFiles")):

class esigFiles {

    protected static $instance = null;

    public static function instance(){
            // If the single instance hasn't been set, set it now.
            if (null == self::$instance) {
                self::$instance = new self;
            }

            return self::$instance;
    }

    public function tempPath(){

            $dir = path_join(WP_CONTENT_DIR, ".tmp-wpesign");
            $tempPath = apply_filters('esig_temp_dir', $dir);

            if (is_dir($tempPath)) return $tempPath;
            // checking for user privilege 
            //if (!current_user_can("install_plugins")) return false;
           // if (!current_user_can("edit_files")) return false;
            
            $folderCreated = $this->createDir($tempPath);

            return $folderCreated;
    }
    
    /**
     * Create temp dir (tmp-wpesign) if not exists
     *
     */
   private function createDir($path) {

            // checking for user privilege 
            //if (!current_user_can("install_plugins")) return false;
            //if (!current_user_can("edit_files")) return false;

            if(file_exists($path)) return $path ;

            if(!is_dir($path) && wp_is_writable(WP_CONTENT_DIR)) {
                mkdir($path, 0700, true);
                $error = new WP_Error("filecreation","WP E-Signature temp  ");
            }

            return $path;
    }


/**
 * Cron job to empty temp dir (tmp-wpesign)
 *
 */
   public static function esigTempDirEvents() {

       // if(!current_user_can("install_plugins")) return false;
       // if (!current_user_can("edit_files")) return false;

        $esigTempPath = self::instance()->tempPath();
        if(!is_dir($esigTempPath)) return false;
            $files = glob($esigTempPath .'/*'); // get all file names

            if(!is_array($files)) return false;
            
            foreach($files as $file){ // iterate files
                if(is_file($file)) {
                    unlink($file); // delete file
                }
            }

    }




/**
 * delete pdf files from esig temp dir (tmp-wpesign)
 *
 */

   public function deleteFile($uploadPath) {
       
        if (file_exists($uploadPath)) {
            return  unlink($uploadPath);
        }
        return false;
    }

/**
 *  E-signature store temp files 
 */

   public function store($fileName,$string)
    {
        if(!$fileName or empty($string)) return false ; 

        $dir = $this->tempPath();

        if (!is_dir($dir) or !is_readable($dir) or !wp_is_writable($dir)) return ; 
        // generate file with filename  
        $path = path_join($dir , $fileName) ; 

        if(file_exists($path)) return  $path; 
        
        if (file_put_contents($path, $string)) {
            if (chmod($path, 0600)) return true;
        }

        return false;
    }


}

endif;
