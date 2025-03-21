<?php

namespace WPCodeBox\ConditionBuilder\Condition;


use WPCodeBox\ConditionBuilder\Condition;

class UserLoggedIn extends Condition
{
    const IS_LOGGED_IN = 0;

    const IS_LOGGED_OUT = 1;

    public function is_satisfied()
    {
        $condition_verb = $this->conditionData->get_condition_verb();

        if(!function_exists('is_user_logged_in')) {
            return false;
        }

        if($condition_verb['value'] === self::IS_LOGGED_IN) {

            return is_user_logged_in();
        }

        if($condition_verb['value'] === self::IS_LOGGED_OUT) {

            return !is_user_logged_in();
        }

        return false;
    }

}