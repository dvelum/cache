<?php
$dvelumRoot =  str_replace('\\', '/' ,  dirname(__FILE__, 3));
// should be without last slash
if ($dvelumRoot[strlen($dvelumRoot) - 1] == '/')
    $dvelumRoot = substr($dvelumRoot, 0, -1);

/*
 * Register composer autoload
 */
require $dvelumRoot . '/vendor/autoload.php';