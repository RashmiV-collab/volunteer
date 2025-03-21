<?php

namespace FluentCrm\App\Api;

final class FCApi
{
    private $instance = null;

    public function __construct($instance)
    {
        $this->instance = $instance;
    }

    public function __call($method, $params)
    {
        try {
            $result = call_user_func_array([$this->instance, $method], $params);
            return $result;
        } catch (\Exception $e) {
            return null;
        }
    }
}
