<?php
namespace minify;

class html
{
    public static function emit(string $html, array $options = []) : string
    {
        $minifier = new core\TinyHtmlMinifier($options);
        return preg_replace('/\>\s+\</','><',$minifier->minify($html));
    }
}
