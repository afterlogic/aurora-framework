<?php

use Aurora\Api;

require_once __DIR__ . "/autoload.php";
require_once __DIR__ . "/../vendor/autoload.php";

$container = Api::GetContainer();

if (!function_exists('base_path')) {
    function base_path($dir = '')
    {
        return AU_APP_ROOT_PATH . $dir;
    }
}

class Eloquent extends \Illuminate\Database\Eloquent\Model {}
