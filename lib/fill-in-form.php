<?php

class WP_Ranking_PRO_FillInForm
{
    private $dom = null;
    private $exclude_names;

    public function __construct() {
    }

    public function fill($html, $params, array $exclude_names = array()) {
        if (empty($html)) {
            return $html;
        }

        $this->exclude_names = $exclude_names; # XXX

        $html =
            '<html><head><meta http-equiv="content-type" content="text/html; charset=utf-8"></head><body>' .
            $html .
            '</body></html>' ;

        # NOTE: 一部空白(input後にselect, select後にselect, などと繋がる場合の間
        # の空白など)が削除されるので注意。preserveWhiteSpaceなどのオプションは
        # 無意味のようだ。input -> inputでも。単にインラインコンテンツを繋げてい
        # るのだろうか。謎。

        $dom = new DOMDocument();
        $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));

        $this->dom = $dom;

        $nodes = $dom->getElementsByTagName('body');
        $this->_walk_recursively($nodes, $params);

        $this->exclude_names = null; # XXX

        # PHP 5.3.6以降であれば、saveHTML()にNodeを渡すことでもっと楽に対応可能。
        $html = $dom->saveHTML();
        $html = preg_replace('/\A.*<body>\s*/s'  , '', $html, 1);
        $html = preg_replace('/\s*<\/body>.*\z/s', '', $html, 1);

        return $html;
    }

    private function _walk_recursively($nodes, $params) {
        foreach ($nodes as $node) {
            switch ($node->nodeName) {
            case 'input':
                $this->_fill_input($node, $params);
                break;

            case 'select':
                $this->_fill_select($node, $params);
                break;

            case 'textarea':
                $this->_fill_textarea($node, $params);
                break;

            default:
                if ($node->hasChildNodes()) {
                    $this->_walk_recursively($node->childNodes, $params);
                }
                break;
            }

        }
    }

    private function _fill_input($input_element, $params) {
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

        if (in_array($name, $this->exclude_names)) {
            return;
        }

        switch ($type) {
        case 'text':
        case 'hidden':
            if (isset($params[$name])) {
                $input_element->setAttribute('value', $params[$name]);
            }
            break;

        # unchecked checkbox問題には特に対処していないので注意。
        case 'checkbox':
            if (isset($params[$name])) {
                if ($input_element->getAttribute('value') === $params[$name]) {
                    $input_element->setAttribute('checked', 'checked');
                }
                else {
                    $input_element->removeAttribute('checked');
                }
            }
            break;

        case 'radio':
            if (isset($params[$name])) {
                if ($input_element->getAttribute('value') === $params[$name]) {
                    $input_element->setAttribute('checked', 'checked');
                }
                else {
                    $input_element->removeAttribute('checked');
                }
            }
            break;

        }
    }

    private function _fill_select($select_element, $params) {
        $name = $select_element->getAttribute('name');

        if (empty($name)) {
            die('Empty name found');
        }

        if (in_array($name, $this->exclude_names)) {
            return;
        }

        if (!isset($params[$name])) {
            return;
        }

        # multiple関係特になにもしていない。
        foreach ($select_element->childNodes as $node) {
            if ($node->nodeName != 'option') {
                continue;
            }

            if ($node->getAttribute('value') === $params[$name]) {
                $node->setAttribute('selected', 'selected');
            }
            else {
                $node->removeAttribute('selected');
            }
        }
    }

    private function _fill_textarea($textarea_element, $params) {
        $name = $textarea_element->getAttribute('name');

        if (empty($name)) {
            die('Empty name found');
        }

        if (in_array($name, $this->exclude_names)) {
            return;
        }

        if (!isset($params[$name])) {
            return;
        }

        foreach ($textarea_element->childNodes as $node) {
            $textarea_element->removeChild($node);
        }

        $textarea_element->appendChild($this->dom->createTextNode($params[$name]));
    }
}
