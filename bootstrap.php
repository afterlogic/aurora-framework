<?php
require_once __DIR__ . "/autoload.php";
require_once __DIR__ . "/../vendor/autoload.php";

use \Aurora\System\Api;

use Pimple\Container;
use \Illuminate\Database\Capsule\Manager as Capsule;
use \Aurora\System\Enums\DbType;

$capsule = new Capsule();

$oSettings = &Api::GetSettings();
if ($oSettings)
{
    $iDbType = $oSettings->DBType;
    $sDbHost = $oSettings->DBHost;
    $sDbName = $oSettings->DBName;
    $sDbLogin = $oSettings->DBLogin;
    $sDbPassword = $oSettings->DBPassword;
    $sDbPrefix = $oSettings->DBPrefix;

    $container = new Container();

    $container['db-config'] = [
        'driver'    => DbType::PostgreSQL === $iDbType ? 'pgsql' : 'mysql',
        'host'      => $sDbHost,
        'database'  => $sDbName,
        'username'  => $sDbLogin,
        'password'  => $sDbPassword,
        // 'charset'   => 'utf8',
        // 'collation' => 'utf8_unicode_ci',
        'prefix'    => $sDbPrefix,
    ];
    $capsule->addConnection($container['db-config']);

    //Make this Capsule instance available globally.
    $capsule->setAsGlobal();

    // Setup the Eloquent ORM.
    $capsule->bootEloquent();
}