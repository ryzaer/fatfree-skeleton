<?php
namespace parse;
#
#
# Parsedown
# http://parsedown.org
#
# (c) Emanuil Rusev
# http://erusev.com
#
# For the full license information, view the LICENSE file that was distributed
# with this source code.
#
#
class md extends markdown {
    private static $stmt;
    static function emit($arg){
        self::$stmt = self::$stmt ? self::$stmt : new markdown(); 
        $str = file_exists($arg) ? file_get_contents($arg) : $arg;
        return self::$stmt->text($arg); 
    }
}