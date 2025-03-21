<?php

class WP_E_Validation extends WP_E_Model {

    public $esig_valid = false;

    public function __construct() {
        parent::__construct();
    }

    /**
     * Retrn sanitize string . 
     * @param unknown $string
     */
    public function esig_clean($string) {

        return sanitize_text_field($string);
    }

    /**
     * Sanitize a string destined to be a tooltip. Prevents XSS.
     * @param string $var
     * @return string
     */
    public function esig_sanitize_tooltip($var) {
        return wp_kses(html_entity_decode($var), array(
            'br' => array(),
            'em' => array(),
            'strong' => array(),
            'span' => array(),
            'ul' => array(),
            'li' => array(),
            'ol' => array(),
            'p' => array(),
        ));
    }

    /**
     * check the value is int 
     * @param int $var
     * @return bool
     */
    public function esig_valid_int($var) {
        return filter_var($var, FILTER_VALIDATE_INT);
    }

    /**
     * 
     * @param unknown $var
     * @return mixed|boolean
     */
    public function esig_valid_string($var) {


        $string = $this->esig_clean($var);

        $string = esc_js($string);

        if (!$this->esig_valid_int($string)) {

            return filter_var(htmlspecialchars(strip_tags($string)));
        } else {
            $this->esig_valid = true;

            return false;
        }
    }

    public function esig_valid_fullName($var) {
        $string = $this->esig_clean($var);
        $valid = false;
        // $string = esc_js($string);
        if (preg_match("/^[a-zA-ZàáâäãåąčćęèéêëėįìíîïłńòóôöõøùúûüųūÿýżźñçčšžÀÁÂÄÃÅĄĆČĖĘÈÉÊËÌÍÎÏĮŁŃÒÓÔÖÕØÙÚÛÜŲŪŸÝŻŹÑßÇŒÆČŠŽ∂ð ,.()'-]([-']?[a-zA-ZàáâäãåąčćęèéêëėįìíîïłńòóôöõøùúûüųūÿýżźñçčšžÀÁÂÄÃÅĄĆČĖĘÈÉÊËÌÍÎÏĮŁŃÒÓÔÖÕØÙÚÛÜŲŪŸÝŻŹÑßÇŒÆČŠŽ∂ð ,.()'-]+)*( [a-zA-ZàáâäãåąčćęèéêëėįìíîïłńòóôöõøùúûüųūÿýżźñçčšžÀÁÂÄÃÅĄĆČĖĘÈÉÊËÌÍÎÏĮŁŃÒÓÔÖÕØÙÚÛÜŲŪŸÝŻŹÑßÇŒÆČŠŽ∂ð ,.()'-]([-']?[a-zA-ZàáâäãåąčćęèéêëėįìíîïłńòóôöõøùúûüųūÿýżźñçčšžÀÁÂÄÃÅĄĆČĖĘÈÉÊËÌÍÎÏĮŁŃÒÓÔÖÕØÙÚÛÜŲŪŸÝŻŹÑßÇŒÆČŠŽ∂ð ,.()'-]+))+$/i", $string)) {
           $valid = true;
        }
        elseif(preg_match("/\p{Han}+/u", $string)){
            $valid= true;
        }
        elseif(preg_match("/\p{L}+/u", $string)){
            $valid= true;
        }
        return $valid;
        /* if (!preg_match("/^[a-zA-ZàáâäãåąčćęèéêëėįìíîïłńòóôöõøùúûüųūÿýżźñçčšžÀÁÂÄÃÅĄĆČĖĘÈÉÊËÌÍÎÏĮŁŃÒÓÔÖÕØÙÚÛÜŲŪŸÝŻŹÑßÇŒÆČŠŽ∂ð ,.'-]([-']?[a-zA-ZàáâäãåąčćęèéêëėįìíîïłńòóôöõøùúûüųūÿýżźñçčšžÀÁÂÄÃÅĄĆČĖĘÈÉÊËÌÍÎÏĮŁŃÒÓÔÖÕØÙÚÛÜŲŪŸÝŻŹÑßÇŒÆČŠŽ∂ð ,.'-]+)*( [a-zA-ZàáâäãåąčćęèéêëėįìíîïłńòóôöõøùúûüųūÿýżźñçčšžÀÁÂÄÃÅĄĆČĖĘÈÉÊËÌÍÎÏĮŁŃÒÓÔÖÕØÙÚÛÜŲŪŸÝŻŹÑßÇŒÆČŠŽ∂ð ,.'-]([-']?[a-zA-ZàáâäãåąčćęèéêëėįìíîïłńòóôöõøùúûüųūÿýżźñçčšžÀÁÂÄÃÅĄĆČĖĘÈÉÊËÌÍÎÏĮŁŃÒÓÔÖÕØÙÚÛÜŲŪŸÝŻŹÑßÇŒÆČŠŽ∂ð ,.'-]+))+$/i", $string)) {
          return false;
          } */
        
    }

    public function esig_valid_email($var) {
        $string = $this->esig_clean($var);
        if (is_email($string)) {
            return true;
        }
        return false;
    }

    public function valid_sif($var) {

        if (is_array($var)) {
            return $var;
        }
        if (seems_utf8($var)) {
            return $var;
        }
        $string = $this->esig_clean($var);
        return $string;
    }

    /**
     * Checking string json object 
     * @since 1.5.7.5
     */

    public function is_json($string)
    {
        return !empty($string) && is_string($string) && is_array(json_decode($string, true)) && json_last_error() == 0;
    }

    public function valid_json($string) 
    {
        if($this->validSignatureImage($string))
        {
            return true; 
        }
        
        return $this->is_json($string);
    }

    /**
     *  Check for valid base64 encoded string 
     * @since 1.5.8
     */

    public function validBase64($data)
    {
        $encodedData = explode("image/png;base64,",$data);
        
        // check for not empty array  
        if(empty($encodedData) && !is_array($encodedData))
        {
            return false;
        }
       
        // check for base64 image  
        if (base64_encode(base64_decode(esigget(1, $encodedData), true)) === esigget(1, $encodedData)) 
        {
            return true;
        }

        return false;
    }

    public function getImageArray($imageData){

     
                if( ini_get('allow_url_fopen') && function_exists("wp_getimagesize")){
                    $image_info = wp_getimagesize($imageData);
                    if($image_info) return $image_info;
                }
            
                $encodedData = explode("image/png;base64,",$imageData);

                if(!is_array($encodedData)) return false; 

                if(!function_exists("imagecreatefromstring") || !function_exists("imagepng")) return false;
                // Process image
                $image = imagecreatefromstring( base64_decode(esigget(1,$encodedData)) );
                
                if (!$image) {
                    return false;
                }
                
                $temFileName = get_temp_dir()  . uniqid(rand(), true) . '.png';
                
                imagepng($image,  $temFileName);
                imagedestroy($image);
                $info = getimagesize($temFileName);

                unlink($temFileName);
                
                return $info;
    }

    /**
     *  Return a valid signature png file 
     * @since 1.5.7.5
     */

    public function validSignatureImage($string)
    {
        // check for json string if it is json string return for old signature generation
        if($this->is_json($string))
        {
            return false; 
        }

        if(!$this->validBase64($string))
        {
            return false;
        }


        $image_info = $this->getImageArray($string);
       
        if(!$image_info && !is_array($image_info))
        {
            return false;
        }

        $imageWidth = esigget(0, $image_info);
        $imageHeight = esigget(1, $image_info); 
        
        if(!is_numeric($imageWidth) && $imageWidth < 100 && !is_numeric($imageHeight) && $imageHeight <20)
        {
            return false;
        }

        $imageMime = esigget("mime",$image_info);

        $acceptable_mimetypes=['image/png'];
        // you can write any validator below, you can check a full mime type or just an extension or file type
        if (!in_array($imageMime, $acceptable_mimetypes)) {
            throw new \Exception('Signature File mime type not acceptable');
        }

        $imageType = explode("/", $imageMime, 2);

        // check for image type png to return true 
        if($imageType[1] === "png")
        {
            return true;
        }

        return false; 
        
    }

}
