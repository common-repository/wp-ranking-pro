<?php

class WP_Ranking_PRO_TemplateEngine
{
    private $template;
    private $context;
    private $loop_stack = array();

    public function __construct($template = '') {
        $this->template = '?>' .  $template;
    }

    public function apply($params = array()) {
        $this->context = $params;

        ob_start();

        eval($this->template);

        $output = ob_get_clean();

        return preg_replace_callback(
            '/\[(\w+)\]/s',
            array($this, 'apply_callback'),
            $output
        );
    }

    private function include_template($name) {
        $template_file = $this->get_var($name);

        $template_string = file_get_contents($template_file);

        $template = new self($template_string);

        echo $template->apply($this->context);
    }

    private function begin_loop($name) {
        $this->loop_stack[] = $this->get_var($name);

        ob_start();
    }

    private function end_loop() {
        $block = ob_get_clean();

        $contexts = array_pop($this->loop_stack);

        foreach ($contexts as $context) {
            # 拡張性を考えると、PHP 5.3以降のnew static()を使うべきかも
            $template = new self($block);
            echo $template->apply($context);
        }
    }

    private function apply_callback($matches) {
        $name = $matches[1];

        return $this->get_var($name);
    }

    private function get_var($name) {
        if (is_object($this->context)) {
            return $this->context->$name;
        }
        else {
            return $this->context[$name];
        }
    }
}
