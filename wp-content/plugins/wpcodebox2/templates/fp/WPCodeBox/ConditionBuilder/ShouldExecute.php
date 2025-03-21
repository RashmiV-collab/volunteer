<?php

namespace WPCodeBox\ConditionBuilder;


class ShouldExecute
{

    public function shouldExecute($wpcb_conditions)
    {

        if ($wpcb_conditions) {
            $conditions_builder = new \WPCodeBox\ConditionBuilder\ConditionBuilder($wpcb_conditions);

            $result = $conditions_builder->is_satisfied();

            return $result;
        }


        return true;

    }

    public static function should_execute($wpcb_conditions) {

        $shouldExecute = new ShouldExecute();
        return $shouldExecute->shouldExecute(json_decode(base64_decode($wpcb_conditions), true));
    }

}
