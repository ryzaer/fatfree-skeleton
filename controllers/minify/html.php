<?php
namespace minify;

class html
{
    public static function emit(string $html, array $options = []) : string
    {
        $minifier = new core\TinyHtmlMinifier($options);
        return $minifier->minify($html);
    }
}