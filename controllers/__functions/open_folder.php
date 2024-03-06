<?php
/*example
 *foreach(open_folder("{$this->root->flow}/pages/content/{$this->layout->url->page}") as $file){
    $file->getFilename() -- filename.
    $file->getPathname() -- path to the file
    $file->getPathInfo() -- SplFileInfo object for the path
    $file->getRealPath() -- canonicalized absolute pathname; see realpath() for more info and the difference to Pathname

    if($file->isFile()){
        $file_script[] = preg_replace("/\\\\/","/",$file->getRealPath());
    }
 *}
 * readmore documentations;
 * https://www.php.net/manual/en/class.recursiveiteratoriterator.php
 */
function open_folder($location){
    return new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($location),
        \RecursiveIteratorIterator::LEAVES_ONLY
    );
}