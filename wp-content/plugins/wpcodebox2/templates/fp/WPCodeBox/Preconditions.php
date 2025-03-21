<?php

namespace WPCodeBox;


class Preconditions
{

    public function check()
    {
        // Detect WPCB request and don't execute snippets
        if (isset($_GET['wpcb2_route'])) {
            if (!function_exists('getallheaders')) {
                function getallheaders()
                {
                    $headers = [];
                    foreach ($_SERVER as $name => $value) {
                        if (substr($name, 0, 5) == 'HTTP_') {
                            $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                        }
                    }
                    return $headers;
                }
            }

            $headers = array_change_key_case(getallheaders(), CASE_LOWER);

            $secret = $headers['x-wpcb-secret'];

            $secretKey = new SecretKey();

            if ($secretKey->checkSecretKey($secret)) {
                return false;
            }

        }

        return true;
    }
}
