<?php

class WP_Ranking_PRO_Form
{
    private $html;
    private $dom;
    private $rules;

    public function __construct($html) {
        $this->html = $html;
    }

    public function build_validator_rules() {
        $dom = new DOMDocument();
        $dom->loadHTML(mb_convert_encoding($this->html, 'HTML-ENTITIES', 'UTF-8'));

        $this->dom = $dom;

        $nodes = $dom->getElementsByTagName('form');

        $this->rules = array();
        $this->_build_validator_rules($nodes);

        $rules = $this->rules;
        unset($this->rules);

        return $rules;
    }

    private function _build_validator_rules($nodes) {
        foreach ($nodes as $node) {
            switch ($node->nodeName) {
            case 'input':
                $this->_build_rule_by_input($node);
                break;

            case 'select':
                $this->_build_rule_by_select($node);
                break;

            case 'textarea':
                $this->_build_rule_by_textarea($node);
                break;

            default:
                if ($node->hasChildNodes()) {
                    $this->_build_validator_rules($node->childNodes);
                }
                break;
            }
        }
    }

    private function _build_rule_by_input($input_element) {
        $rules = array();

        $type = $input_element->getAttribute('type');

        if (empty($type)) {
            $type = 'text';
        }

        $name = $input_element->getAttribute('name');

        if (empty($name)) {
            # submitはnameが空でも許す。
            if ($type == 'submit') {
                return;
            }

            die('Empty name found');
        }

        switch ($type) {
        case 'text':
        case 'hidden':
            # input:text, :hiddenのnameの重複は駄目。
            if (isset($this->rules[$name])) {
                die("Duplicate defined name: $name");
            }
            else {
                $value = $input_element->getAttribute('value');

                # デフォルト値が数値なら数字型と見做す。
                if (ctype_digit($value)) {
                    $this->rules[$name] = array(
                        'type'          => 'integer',
                        'min'           => 0, # XXX
                        'max'           => 10000, # XXX
                        'default_value' => $value,
                    );
                }
                else {
                    $this->rules[$name] = array(
                        'type'          => 'string',
                        'min'           => 0,
                        'max'           => 4096,
                        'default_value' => $value,
                    );
                }
            }

            break;

            # 未実装
#        case 'checkbox':
#            break;

        case 'radio':
            $value = $input_element->getAttribute('value');

            if (isset($this->rules[$name])) {
                $this->rules[$name]['selection'][] = $value;
            }
            else {
                $this->rules[$name] = array(
                    'type'      => 'selection',
                    'selection' => array($value),
                );
            }

            # デフォルト(checked)の扱いは、ブラウザ風に、
            # * checkedが1つもない場合は、なし。
            # * checkedが複数ある場合は、最後のもの。
            # この方法だと、実装的にも凝ったことが不要。
            if ($input_element->hasAttribute('checked')) {
                $this->rules[$name]['default_value'] = $value;
            }

            break;

        }
    }

    private function _build_rule_by_select($select_element) {
        $name = $select_element->getAttribute('name');

        if (empty($name)) {
            die('Empty name found');
        }

        if (isset($this->rules[$name])) {
            die("Duplicate defined name: $name");
        }

        $selection = array();
        $selected  = null;

        # multiple関係特になにもしていない。
        # 複数のselectedにも未対応。
        foreach ($select_element->childNodes as $node) {
            if ($node->nodeName != 'option') {
                continue;
            }

            $value = $node->getAttribute('value');
            $selection[] = $value;
            
            if ($node->hasAttribute('selected') && is_null($selected)) {
                $selected = $value;
            }
        }

        if (!count($selection)) {
            die('Empty options');
        }

        if (is_null($selected)) {
            $selected = isset($selection[0])
                ? $selection[0]
                : '' ;
        }

        $this->rules[$name] = array(
            'type'          => 'selection',
            'selection'     => $selection,
            'default_value' => $selected,
        );
    }

    private function _build_rule_by_textarea($textarea_element) {
        $name = $textarea_element->getAttribute('name');

        if (empty($name)) {
            die('Empty name found');
        }

        if (isset($this->rules[$name])) {
            die("Duplicate defined name: $name");
        }

        $this->rules[$name] = array(
            'type'          => 'string',
            'min'           => 0,
            'max'           => 4096,
            'default_value' => $textarea_element->textContent,
        );
    }
}
