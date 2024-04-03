<?php defined('BASEPATH') OR exit('No direct script access allowed');



spl_autoload_register(function ($className) {
    // The base path is libraries folder
    $baseDir = APPPATH . 'libraries/';

    // change Namespace to path
    $class = str_replace('\\', '/', $className) . '.php';

    //  require_once file
    $file = $baseDir . $class;
    if (file_exists($file)) {
        require_once $file;
    }
});
