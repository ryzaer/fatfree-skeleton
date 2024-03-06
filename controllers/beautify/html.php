<?php
namespace beautify;

class html {
    static function emit($args,$prms=[])
    {
        $prms = $prms ? $prms : array(
            'indent_inner_html' => false,
            'indent_char' => " ",
            'indent_size' => 2,
            'wrap_line_length' => 32786,
            'unformatted' => ['code', 'pre'],
            'preserve_newlines' => false,
            'max_preserve_newlines' => 32786,
            'indent_scripts'	=> 'normal' // keep|separate|normal
        );
        $html = new \beautify\core\htmlBeautifier($prms);
        return $html->beautify($args);
    }
}