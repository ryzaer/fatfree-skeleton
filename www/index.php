<?php 
$vendor = '../vendor/autoload.php';
if(file_exists($vendor)){
    require_once $vendor;
    build::base('app/setup.ini')->run();
}else{
    die('no vendor detect! please install composer');
}