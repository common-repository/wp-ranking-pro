<?php

class WP_Ranking_PRO_Validator
{
    private $rules;

    public function __construct($rules) {
        $this->rules = $rules;
    }

    public function validate($params) {
        $normalized_params = array();

        foreach ($this->rules as $name => $rule) {
            $param = isset($params[$name])
                ? $params[$name]
                : null ;

            $normalized_params[$name] = call_user_func(array($this, '_validate_' . $rule['type']), $rule, $param);
        }

        return $normalized_params;
    }

    private function _validate_string($rule, $param) {
        $normalized_param = is_null($param)
            ? $rule['default_value']
            : $param ;

        if ($rule['min'] <= mb_strlen($normalized_param) && mb_strlen($normalized_param) <= $rule['max']) {
            # valid
        }
        else {
            $normalized_param = mb_substr($normalized_param, 0, $rule['max']);
        }

        return $normalized_param;
    }

    private function _validate_integer($rule, $param) {
        $normalized_param = is_null($param)
            ? $rule['default_value']
            : intval($param) ;

        if ($rule['min'] <= $normalized_param && $normalized_param <= $rule['max']) {
            # valid
        }
        else {
            $normalized_param = $rule['min'];
        }

        return $normalized_param;
    }

    private function _validate_selection($rule, $param) {
        $normalized_param = is_null($param)
            ? $rule['default_value']
            : $param ;

        if (in_array($normalized_param, $rule['selection'])) {
            # valid
        }
        else {
            $normalized_param = $rule['default_value'];
        }

        return $normalized_param;
    }
}
